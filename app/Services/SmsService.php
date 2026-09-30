<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SmsMessage;
use App\Support\Market;
use App\Support\OwnerAccess;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sms versturen via Smstools (api.smsgatewayapi.com). De sleutels staan in de
 * omgeving (SMSTOOLS_CLIENT_ID en SMSTOOLS_CLIENT_SECRET); zonder sleutels
 * bestaat de functie niet. Het account is van het platform en elke sms kost
 * geld: een administratie verstuurt uit haar gekochte tegoed
 * (SmsCreditService), de eigen administraties van het platform zonder tegoed
 * met een grens per maand.
 */
class SmsService
{
    /** Langer dan dit wordt een sms te duur en te lang om te lezen. */
    public const MAX_SEGMENTS = 3;

    /** De tekens die in een gewone sms passen (GSM 03.38); de rest schrijven we om. */
    private const GSM = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /** Deze tellen dubbel. */
    private const GSM_DOUBLE = '^{}\\[~]|€';

    /** @var array<int, bool> */
    private array $allowed = [];

    public function configured(): bool
    {
        return filled(config('services.smstools.client_id')) && filled(config('services.smstools.client_secret'));
    }

    /** Bestaat sms voor deze administratie? Los van de vraag of er tegoed is. */
    public function enabled(?Company $company): bool
    {
        return $company !== null && ! $company->is_demo && $this->configured() && Market::is('nl');
    }

    /**
     * Verstuurt deze administratie op kosten van het platform, zonder tegoed?
     * Dat geldt voor de eigen administraties van de eigenaar (of wie in
     * SMSTOOLS_COMPANIES staat); voor hen geldt alleen de grens per maand.
     */
    public function free(?Company $company): bool
    {
        if (! $this->enabled($company)) {
            return false;
        }

        return $this->allowed[$company->id] ??= $this->allows($company);
    }

    /** Kan deze administratie nu een sms versturen: gratis, of met tegoed? */
    public function available(?Company $company): bool
    {
        return $this->enabled($company)
            && ($this->free($company) || app(SmsCreditService::class)->balance($company) > 0);
    }

    /**
     * Voor de eigenaar: waarom sms nog niet werkt. Null als alles is ingesteld.
     */
    public function missing(): ?string
    {
        return match (true) {
            blank(config('services.smstools.client_id')) => 'SMSTOOLS_CLIENT_ID',
            blank(config('services.smstools.client_secret')) => 'SMSTOOLS_CLIENT_SECRET',
            default => null,
        };
    }

    /** De naam waaronder de sms binnenkomt: hooguit elf letters en cijfers. */
    public function sender(Company $company): string
    {
        $name = (string) (config('services.smstools.sender') ?: $company->name);
        // Zonder rechtsvorm past de naam vaker: "Creditline BV" wordt "Creditline".
        $name = preg_replace('/\b(b\.?\s?v\.?|n\.?\s?v\.?|v\.?\s?o\.?\s?f\.?)\s*$/iu', '', trim($name)) ?? $name;
        $name = preg_replace('/[^A-Za-z0-9]+/', '', Str::ascii($name)) ?? '';

        return substr($name, 0, 11) ?: 'Bericht';
    }

    /** Dezelfde tekst in tekens die in een gewone sms passen (ë wordt e, een lang streepje een kort). */
    public function plain(string $text): string
    {
        $text = str_replace(["\r\n", "\r", '“', '”', '„', '‘', '’', '–', '—', '…', "\u{00A0}"], ["\n", "\n", '"', '"', '"', "'", "'", '-', '-', '...', ' '], $text);
        $out = '';
        foreach (mb_str_split($text) as $char) {
            $out .= (mb_strpos(self::GSM, $char) !== false || mb_strpos(self::GSM_DOUBLE, $char) !== false)
                ? $char
                : Str::ascii($char);
        }

        return trim(preg_replace('/[ \t]+/', ' ', $out) ?? $out);
    }

    /** Uit hoeveel sms'en het bericht bestaat (en dus wat het kost). */
    public function segments(string $text): int
    {
        $length = 0;
        foreach (mb_str_split($this->plain($text)) as $char) {
            $length += mb_strpos(self::GSM_DOUBLE, $char) !== false ? 2 : 1;
        }

        return $length <= 160 ? 1 : (int) ceil($length / 153);
    }

    /** Hoeveel sms'en deze administratie nog kan versturen: het tegoed, of wat er deze maand nog mag. */
    public function remaining(Company $company): int
    {
        if (! $this->free($company)) {
            return $this->enabled($company) ? app(SmsCreditService::class)->balance($company) : 0;
        }

        $used = (int) SmsMessage::where('company_id', $company->id)
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('segments');

        return max(0, (int) config('services.smstools.monthly_limit') - $used);
    }

    /**
     * Verstuurt één sms en legt haar vast, ook als het mislukt.
     *
     * @throws \DomainException met een melding die de gebruiker kan lezen
     */
    public function send(Company $company, ?string $to, string $body, ?Model $subject = null, ?int $userId = null): SmsMessage
    {
        if (! $this->enabled($company)) {
            throw new \DomainException(__('Sms versturen staat voor deze administratie niet aan.'));
        }
        $free = $this->free($company);
        $number = PhoneNumber::mobile($to);
        if ($number === null) {
            throw new \DomainException(__('Er is geen mobiel nummer bekend; een sms kan alleen naar een mobiel nummer.'));
        }
        $body = $this->plain($body);
        if ($body === '') {
            throw new \DomainException(__('Het bericht is leeg.'));
        }
        $segments = $this->segments($body);
        if ($segments > self::MAX_SEGMENTS) {
            throw new \DomainException(__('Het bericht is te lang voor een sms. Maak het korter.'));
        }
        if ($this->remaining($company) < $segments) {
            throw new \DomainException($free
                ? __('De grens van :limit sms\'en per maand is bereikt.', ['limit' => (int) config('services.smstools.monthly_limit')])
                : __('Je sms-tegoed is niet genoeg voor dit bericht. Koop een bundel bij Instellingen, Sms.'));
        }

        $message = new SmsMessage([
            'company_id' => $company->id,
            'user_id' => $userId,
            'recipient' => $number,
            'sender' => $this->sender($company),
            'body' => $body,
            'segments' => $segments,
            'status' => 'failed',
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
        ]);

        try {
            $response = Http::baseUrl(rtrim((string) config('services.smstools.url'), '/'))
                ->withHeaders([
                    'X-Client-Id' => (string) config('services.smstools.client_id'),
                    'X-Client-Secret' => (string) config('services.smstools.client_secret'),
                ])
                ->acceptJson()->asJson()->timeout(15)
                ->post('/message/send', [
                    'message' => $body,
                    'to' => $number,
                    'sender' => $message->sender,
                    'reference' => $subject ? class_basename($subject) . '-' . $subject->getKey() : 'los',
                ]);
        } catch (\Throwable $e) {
            Log::error('Sms mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
            $message->fill(['error' => Str::limit($e->getMessage(), 250, '')])->save();

            throw new \DomainException(__('De sms kon niet worden verstuurd. Probeer het later opnieuw.'));
        }

        $id = $response->json('messageid');
        if ($response->successful() && filled($id)) {
            $message->fill(['status' => 'sent', 'provider_id' => Str::limit((string) $id, 80, '')])->save();
            // Alleen een verstuurde sms kost tegoed.
            if (! $free) {
                app(SmsCreditService::class)->spend($company, $message);
            }

            return $message;
        }

        $reason = (string) ($response->json('errorMsg') ?: ('HTTP ' . $response->status()));
        Log::error('Sms geweigerd', ['company' => $company->id, 'status' => $response->status(), 'reason' => $reason]);
        $message->fill(['error' => Str::limit($reason, 250, '')])->save();
        $this->warnOwner($reason);

        throw new \DomainException($free
            ? __('De sms is niet verstuurd: :reason', ['reason' => $reason])
            : __('De sms kon niet worden verstuurd. Er is geen tegoed afgeschreven; probeer het later opnieuw.'));
    }

    /**
     * Is het tegoed bij Smstools zelf op, dan kan geen enkele klant nog
     * versturen: de eigenaar krijgt daar bericht van, hooguit eens per zes uur.
     */
    private function warnOwner(string $reason): void
    {
        if (! preg_match('/credit|saldo|balance/i', $reason) || ! Cache::add('sms:owner-warned', true, now()->addHours(6))) {
            return;
        }

        try {
            foreach (OwnerAccess::emails() as $email) {
                Mail::raw(
                    "Smstools weigert sms'en: {$reason}\n\nKlanten kunnen nu geen sms versturen. Koop credits bij Smstools.",
                    fn ($mail) => $mail->to($email)->subject('Sms-tegoed bij Smstools is op'),
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Melding over sms-tegoed niet verstuurd', ['error' => $e->getMessage()]);
        }
    }

    private function allows(Company $company): bool
    {
        $list = trim((string) config('services.smstools.companies'));
        if ($list === '*') {
            return true;
        }
        if ($list !== '') {
            return in_array((string) $company->id, array_map('trim', explode(',', $list)), true);
        }

        // Niets aangewezen: alleen de administraties van de eigenaar van het platform.
        $owner = OwnerAccess::owner();
        if (! $owner) {
            return false;
        }

        return (int) $owner->company_id === (int) $company->id
            || DB::table('company_user')->where('user_id', $owner->id)->where('company_id', $company->id)->exists();
    }
}

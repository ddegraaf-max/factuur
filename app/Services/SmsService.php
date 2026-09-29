<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SmsMessage;
use App\Support\OwnerAccess;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sms versturen via Smstools (api.smsgatewayapi.com). De sleutels staan in de
 * omgeving (SMSTOOLS_CLIENT_ID en SMSTOOLS_CLIENT_SECRET); zonder sleutels
 * bestaat de functie niet. Het account is van het platform: elke sms kost
 * geld, dus alleen de administraties die daarvoor zijn aangewezen mogen
 * versturen, met een grens per maand.
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

    /** Mag deze administratie sms'en op kosten van het account? */
    public function available(?Company $company): bool
    {
        if (! $company || $company->is_demo || ! $this->configured()) {
            return false;
        }

        return $this->allowed[$company->id] ??= $this->allows($company);
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

    /** Hoeveel sms'en deze administratie deze maand nog mag versturen. */
    public function remaining(Company $company): int
    {
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
        if (! $this->available($company)) {
            throw new \DomainException(__('Sms versturen staat voor deze administratie niet aan.'));
        }
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
            throw new \DomainException(__('De grens van :limit sms\'en per maand is bereikt.', ['limit' => (int) config('services.smstools.monthly_limit')]));
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

            return $message;
        }

        $reason = (string) ($response->json('errorMsg') ?: ('HTTP ' . $response->status()));
        Log::error('Sms geweigerd', ['company' => $company->id, 'status' => $response->status(), 'reason' => $reason]);
        $message->fill(['error' => Str::limit($reason, 250, '')])->save();

        throw new \DomainException(__('De sms is niet verstuurd: :reason', ['reason' => $reason]));
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

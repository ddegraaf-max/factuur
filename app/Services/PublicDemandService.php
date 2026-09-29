<?php

namespace App\Services;

use App\Mail\PaymentDemandMail;
use App\Mail\PublicDemandMail;
use App\Models\PaymentDemand;
use App\Models\PaymentDemandFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Online aanmaning zonder account, vanaf de website.
 *
 *  1. De schuldeiser vult factuur en klant in. Er gaat alleen een mail naar
 *     hemzelf, met een link om zijn e-mailadres te bevestigen.
 *  2. De link toont de gegevens met een knop. Pas de knop bevestigt, zodat de
 *     linkscanner van een mailserver de aanmaning niet per ongeluk verstuurt.
 *  3. Na de bevestiging staat de pagina online, begint de termijn te lopen en
 *     krijgt de klant de mail (als zijn adres is ingevuld). De schuldeiser
 *     krijgt de link voor de klant en zijn eigen link om mee te kijken.
 *
 * Tot de bevestiging bestaat de aanmaning voor de buitenwereld niet. Zo kan
 * niemand uit naam van een ander aanmaningen laten versturen.
 */
class PublicDemandService
{
    /** Zo vaak kan de bevestigingsmail per aanmaning opnieuw worden gestuurd. */
    public const RESEND_MAX = 3;

    /** Onbevestigde aanmaningen verdwijnen na zoveel dagen. */
    public const PURGE_DAYS = 30;

    /** Rem op misbruik: per verbinding en per e-mailadres van de klant. */
    public const LIMIT = ['hour' => 5, 'day' => 15, 'debtor_day' => 3, 'reply_hour' => 10, 'resend_hour' => 5];

    public function __construct(private PaymentDemandService $demands) {}

    /** Mag deze verbinding nog een aanmaning maken? */
    public function mayCreate(Request $request): bool
    {
        return ! RateLimiter::tooManyAttempts('demand-hour:' . $request->ip(), self::LIMIT['hour'])
            && ! RateLimiter::tooManyAttempts('demand-day:' . $request->ip(), self::LIMIT['day']);
    }

    /** Heeft deze klant vandaag al een paar aanmaningen gekregen (van wie dan ook)? */
    public function debtorLimitReached(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        return PaymentDemand::withoutGlobalScope('company')
            ->whereNull('invoice_id')
            ->where('sent_to', mb_strtolower((string) $email))
            ->whereNotNull('confirmed_at')
            ->where('confirmed_at', '>=', now()->subDay())
            ->count() >= self::LIMIT['debtor_day'];
    }

    /**
     * Legt de aanmaning vast en mailt de schuldeiser de link om te bevestigen.
     *
     * @param  array<string, mixed>  $data  de gevalideerde velden van het formulier
     */
    public function create(array $data, ?UploadedFile $file, Request $request): PaymentDemand
    {
        $demand = $this->demands->make($data);
        $demand->forceFill([
            'token' => bin2hex(random_bytes(32)),
            'creditor_key' => bin2hex(random_bytes(24)),
            'creator_ip' => $request->ip(),
            'debtor_facts' => $this->facts($demand->debtor_kvk),
        ])->save();

        if ($file) {
            $demand->file()->create([
                'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => (int) $file->getSize(),
                'file_data' => base64_encode((string) file_get_contents($file->getRealPath())),
            ]);
        }

        RateLimiter::hit('demand-hour:' . $request->ip(), 3600);
        RateLimiter::hit('demand-day:' . $request->ip(), 86400);
        $this->demands->log($demand, 'created', __('Aanmaning gemaakt op de website; wacht op bevestiging van :email', ['email' => $demand->creditor_email]), $request, 'creditor');

        return $demand;
    }

    /** De mail met de bevestigingslink. */
    public function mailConfirm(PaymentDemand $demand): bool
    {
        try {
            Mail::to($demand->creditor_email)->send(new PublicDemandMail($demand, 'confirm', $this->demands->claim($demand)));
            $demand->increment('confirm_mails');

            return true;
        } catch (\Throwable $e) {
            Log::error('Bevestigingsmail van aanmaning mislukt', ['demand' => $demand->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Bevestigingsmail opnieuw sturen vanaf de wachtpagina.
     *
     * @return 'sent'|'limit'|'failed'
     */
    public function resend(PaymentDemand $demand, Request $request): string
    {
        // De eerste mail telt mee: daarna nog RESEND_MAX keer.
        if ($demand->confirm_mails > self::RESEND_MAX
            || RateLimiter::tooManyAttempts('demand-resend:' . $request->ip(), self::LIMIT['resend_hour'])
            || RateLimiter::tooManyAttempts('demand-resend-one:' . $demand->id, 1)) {
            return 'limit';
        }
        if (! $this->mailConfirm($demand)) {
            return 'failed';
        }

        RateLimiter::hit('demand-resend:' . $request->ip(), 3600);
        RateLimiter::hit('demand-resend-one:' . $demand->id, 60);
        $this->demands->log($demand, 'resent', __('Bevestigingsmail opnieuw verstuurd'), $request, 'creditor');

        return 'sent';
    }

    /**
     * De schuldeiser bevestigt: de aanmaning wordt actief, de termijn begint
     * vandaag en de mails gaan uit. Geeft false terug als ze al bevestigd was
     * (dubbel klikken): dan vertrekt er niets nog een keer.
     */
    public function confirm(PaymentDemand $demand, Request $request): bool
    {
        $deadline = $this->demands->standaloneDeadline($demand->debtor_type, $demand->term_days, $demand->sent_to);
        $first = PaymentDemand::withoutGlobalScope('company')->whereKey($demand->id)->where('status', 'pending')->update([
            'status' => 'sent',
            'confirmed_at' => now(),
            'confirm_ip' => $request->ip(),
            'sent_at' => now(),
            'deadline' => $deadline->toDateString(),
        ]) === 1;
        if (! $first) {
            return false;
        }

        $demand->refresh()->forgetInvoice();
        $this->demands->log($demand, 'confirmed', __('E-mailadres :email bevestigd; aanmaning actief', ['email' => $demand->creditor_email]), $request, 'creditor');

        if (filled($demand->sent_to)) {
            $this->mailDebtor($demand);
        }

        try {
            Mail::to($demand->creditor_email)->send(new PublicDemandMail($demand, 'ready', $this->demands->claim($demand)));
        } catch (\Throwable $e) {
            Log::error('Bericht aan schuldeiser over actieve aanmaning mislukt', ['demand' => $demand->id, 'error' => $e->getMessage()]);
        }

        return true;
    }

    private function mailDebtor(PaymentDemand $demand): void
    {
        $file = $demand->file()->first();
        $data = $file?->contents();

        try {
            Mail::to($demand->sent_to)->send(new PaymentDemandMail(
                $demand,
                $this->demands->claimAsSent($demand),
                $this->demands->pdf($demand)->output(),
                '',
                $data !== null ? ['name' => $file->filename, 'data' => $data, 'mime' => $file->mime_type] : null,
            ));
            $this->demands->log($demand, 'sent', __('Aanmaning gemaild naar :to; betalen zonder incassokosten kan tot en met :date', [
                'to' => $demand->sent_to, 'date' => $demand->deadline->translatedFormat('j F Y'),
            ]));
        } catch (\Throwable $e) {
            Log::error('Losse aanmaning mailen mislukt', ['demand' => $demand->id, 'error' => $e->getMessage()]);
            $this->demands->log($demand, 'failed', __('De mail naar :to kon niet worden verstuurd; stuur de link zelf', ['to' => $demand->sent_to]));
        }
    }

    /** Ruimt aanmaningen op die nooit zijn bevestigd. Geeft het aantal terug. */
    public function purgeUnconfirmed(): int
    {
        return PaymentDemand::withoutGlobalScope('company')
            ->whereNull('invoice_id')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(self::PURGE_DAYS))
            ->get()
            ->each->delete()
            ->count();
    }

    /**
     * Wat het handelsregister over de klant zegt, als het KvK-nummer is
     * ingevuld en de koppeling met de KvK aan staat. Alleen voor de schuldeiser.
     *
     * @return array<string, mixed>|null
     */
    private function facts(?string $kvk): ?array
    {
        $service = app(KvkService::class);
        if (blank($kvk) || strlen((string) $kvk) !== 8 || ! $service->enabled()) {
            return null;
        }

        try {
            $hit = $service->search((string) $kvk)[0] ?? null;

            return $hit
                ? ['found' => true, 'name' => $hit['name'] ?? null, 'city' => $hit['city'] ?? null]
                : ['found' => false];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** In één regel, voor het overzicht en de mail van de schuldeiser. */
    public function factsSummary(PaymentDemand $demand): ?string
    {
        $facts = $demand->debtor_facts;
        if (! is_array($facts)) {
            return null;
        }
        if (empty($facts['found'])) {
            return __('KvK-nummer :kvk is niet gevonden in het handelsregister', ['kvk' => $demand->debtor_kvk]);
        }

        return implode(' · ', array_filter([$facts['name'] ?? null, $facts['city'] ?? null, __('ingeschreven onder KvK :kvk', ['kvk' => $demand->debtor_kvk])]));
    }

    /**
     * Voorbeeld met verzonnen partijen, 44 dagen te laat. Wordt nergens bewaard.
     */
    public function sample(): PaymentDemand
    {
        $demand = $this->demands->make([
            'van_bedrijf' => 'Jouw Bedrijf B.V.', 'van_email' => 'facturen@jouwbedrijf.nl', 'van_kvk' => '12345678',
            'van_iban' => 'NL91 ABNA 0417 1643 00', 'van_adres' => "Dorpsstraat 1\n1431 AB Aalsmeer", 'van_telefoon' => '0297 12 34 56',
            'aan_naam' => 'Voorbeeld Klant B.V.', 'aan_email' => 'administratie@voorbeeldklant.nl', 'aan_adres' => "Kerkstraat 3\n1211 CK Hilversum", 'aan_kvk' => '87654321',
            'klant' => 'zakelijk', 'factuurnummer' => '2026-089', 'factuurdatum' => now()->subDays(58)->toDateString(),
            'vervaldatum' => now()->subDays(44)->toDateString(), 'bedrag' => 12400, 'rente' => true,
        ]);

        return $demand->forceFill([
            'token' => 'voorbeeld', 'creditor_key' => 'sleutel', 'status' => 'sent',
            'sent_at' => now(), 'confirmed_at' => now(), 'first_opened_at' => now(),
            'debtor_facts' => ['found' => true, 'name' => 'Voorbeeld Klant B.V.', 'city' => 'Hilversum'],
        ])->setRelation('file', new PaymentDemandFile(['filename' => 'factuur-2026-089.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 184320]));
    }
}

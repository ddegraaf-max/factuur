<?php

namespace App\Services\Fachora;

use App\Models\FachoraFile;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\User;
use App\Services\NipService;
use App\Services\SmsService;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Fachora Verified: de verificatiestappen van een bedrijf, wat automatisch
 * kan (register/btw via de biała lista, e-mail, telefoon per sms, voorwaarden,
 * bank/identiteit via Stripe Connect) en wat de eigenaar zelf afvinkt
 * (gebeld, polis gezien). Pas als alle verplichte stappen groen zijn, kan de
 * eigenaar goedkeuren; het keurmerk is nooit automatisch.
 */
class VerificationService
{
    public const LABELS = [
        'registry' => 'Bedrijf actief in het register (NIP)',
        'vat' => 'Btw-status en bankrekening (biała lista)',
        'email' => 'E-mailadres bevestigd',
        'phone' => 'Telefoonnummer bevestigd',
        'terms' => 'Regulamin en Fachora Standard geaccepteerd',
        'bank' => 'Identiteit en bankrekening (Stripe)',
        'insurance' => 'Verzekering (OC) zichtbaar',
    ];

    public function __construct(private Notifier $notifier, private ProtectService $protect) {}

    /** Welke stappen verplicht zijn: bank alleen als Protect bestaat. @return list<string> */
    public function required(): array
    {
        return array_values(array_unique(array_merge(Provider::REQUIRED_CHECKS, $this->protect->enabled() ? ['bank'] : [])));
    }

    /** Overzicht voor de schermen. @return list<array<string, mixed>> */
    public function overview(Provider $provider): array
    {
        $checks = $provider->checks()->get()->keyBy('kind');
        $required = $this->required();

        return array_map(fn (string $kind) => [
            'kind' => $kind,
            'label' => __(self::LABELS[$kind]),
            'status' => $checks[$kind]->status ?? 'pending',
            'note' => $checks[$kind]->note ?? null,
            'checked_at' => isset($checks[$kind]) ? $checks[$kind]->checked_at?->translatedFormat('j M Y H:i') : null,
            'required' => in_array($kind, $required, true),
        ], Provider::CHECKS);
    }

    public function ready(Provider $provider): bool
    {
        $passed = $provider->checks()->whereIn('status', ['passed', 'manual'])->pluck('kind')->all();

        return array_diff($this->required(), $passed) === [];
    }

    /** Het bedrijf vraagt het keurmerk aan: alle automatische stappen draaien, de eigenaar hoort ervan. */
    public function request(Provider $provider): void
    {
        if (! in_array($provider->status, ['claimed', 'pending'], true)) {
            throw new \DomainException(__('Alleen een geclaimd profiel kan verificatie aanvragen.'));
        }
        $this->runAutomatic($provider);
        $provider->forceFill(['status' => 'pending', 'verification_requested_at' => $provider->verification_requested_at ?: now()])->save();
        $this->notifier->provider($provider, 'verification_pending');
        $this->notifier->owner('verification_requested', [], null, $provider);
    }

    /** Alle stappen die zonder mens kunnen. Veilig om vaker te draaien. */
    public function runAutomatic(Provider $provider): void
    {
        $this->checkRegistry($provider);
        $this->set($provider, 'email', $provider->email_verified_at ? 'passed' : 'pending');
        if (! $this->isManual($provider, 'phone')) {
            $this->set($provider, 'phone', $provider->phone_verified_at ? 'passed' : 'pending');
        }
        $terms = $provider->terms_accepted_at && $provider->terms_version === (string) config('fachora.terms_version');
        $this->set($provider, 'terms', $terms ? 'passed' : 'pending');
        $this->refreshBankCheck($provider);
        if (! $this->isManual($provider, 'insurance')) {
            $future = $provider->insurance_until !== null && $provider->insurance_until->isFuture();
            $this->set($provider, 'insurance', $future ? 'passed' : 'pending', $provider->insurance_note);
        }
    }

    /** Register en btw via de biała lista (wl-api.mf.gov.pl). */
    public function checkRegistry(Provider $provider): void
    {
        if (blank($provider->nip)) {
            $this->set($provider, 'registry', 'pending', __('Geen NIP bekend; handmatig controleren.'));

            return;
        }
        if ($this->isManual($provider, 'registry')) {
            return;
        }

        try {
            $facts = app(NipService::class)->lookup($provider->nip);
        } catch (\Throwable $e) {
            $this->set($provider, 'registry', 'pending', __('Het register was niet bereikbaar; later opnieuw.'));

            return;
        }

        if ($facts === null) {
            $provider->forceFill(['vat_status' => 'none', 'vat_checked_at' => now()])->save();
            $this->set($provider, 'registry', 'pending', __('NIP staat niet in de btw-lijst (vrijgesteld of onbekend); handmatig controleren in CEIDG/KRS.'));
            $this->set($provider, 'vat', 'pending', __('Niet btw-geregistreerd'));

            return;
        }

        $provider->forceFill([
            'legal_name' => $provider->legal_name ?: $facts['name'],
            'regon' => $provider->regon ?: $facts['regon'],
            'registry_status' => 'active',
            'vat_status' => ProviderService::vatStatus($facts['vat_status']),
            'vat_checked_at' => now(),
        ])->save();
        $this->set($provider, 'registry', 'passed', $facts['name'], ['name' => $facts['name'], 'regon' => $facts['regon'], 'vat_status' => $facts['vat_status']]);
        $this->set($provider, 'vat', 'passed', __('Status: :status, :n rekening(en) gemeld', ['status' => $facts['vat_status'], 'n' => count($facts['accounts'])]), ['accounts' => count($facts['accounts'])]);
    }

    /** Bank/identiteit volgt uit Stripe Connect (payouts aan = KYC rond). */
    public function refreshBankCheck(Provider $provider): void
    {
        if ($this->isManual($provider, 'bank')) {
            return;
        }
        if (! $this->protect->enabled()) {
            $this->set($provider, 'bank', 'pending', __('Fachora Protect staat op deze omgeving nog uit.'));

            return;
        }
        $due = (array) ($provider->stripe_requirements['currently_due'] ?? []);
        $this->set($provider, 'bank', $provider->stripe_payouts_enabled ? 'passed' : 'pending',
            $provider->stripe_account_id ? ($due ? __('Stripe wacht nog op: :fields', ['fields' => implode(', ', array_slice($due, 0, 5))]) : null) : __('Nog niet gestart'));
    }

    /* ---------- stappen van het bedrijf zelf ---------- */

    /** Sms-code naar het mobiele nummer. Zonder sms-koppeling wordt de stap handmatig (de eigenaar belt). */
    public function sendPhoneCode(Provider $provider): bool
    {
        $sms = app(SmsService::class);
        if (! $sms->configured() || blank($provider->phone)) {
            return false;
        }
        if ($provider->phone_code_sent_at && $provider->phone_code_sent_at->gt(now()->subMinute())) {
            throw new \DomainException(__('Er is net een code verstuurd. Wacht een minuut.'));
        }
        $code = (string) random_int(100000, 999999);
        $provider->forceFill(['phone_code' => $code, 'phone_code_sent_at' => now()])->save();

        return $sms->platform($provider->phone, __('Fachora: Twój kod weryfikacyjny to :code', ['code' => $code]));
    }

    public function verifyPhone(Provider $provider, string $code): bool
    {
        $ok = filled($provider->phone_code) && hash_equals($provider->phone_code, trim($code))
            && $provider->phone_code_sent_at && $provider->phone_code_sent_at->gt(now()->subMinutes(30));
        if ($ok) {
            $provider->forceFill(['phone_verified_at' => now(), 'phone_code' => null])->save();
            $this->set($provider, 'phone', 'passed');
        }

        return $ok;
    }

    public function acceptTerms(Provider $provider): void
    {
        $provider->forceFill(['terms_accepted_at' => now(), 'terms_version' => (string) config('fachora.terms_version')])->save();
        $this->set($provider, 'terms', 'passed');
    }

    public function uploadInsurance(Provider $provider, UploadedFile $file, ?string $until): void
    {
        FachoraFile::fromUpload('provider', $provider->id, 'insurance', $file);
        $provider->forceFill(['insurance_until' => $until ?: null, 'insurance_note' => __('Polis geüpload, nog niet beoordeeld')])->save();
        $this->set($provider, 'insurance', 'pending', $provider->insurance_note);
    }

    /* ---------- eigenaar ---------- */

    /** De eigenaar vinkt een stap zelf af (gebeld, register gezien, polis gelezen). */
    public function manualPass(Provider $provider, string $kind, User $by, ?string $note = null): void
    {
        if (! in_array($kind, Provider::CHECKS, true)) {
            throw new \DomainException('Onbekende controle.');
        }
        $this->set($provider, $kind, 'manual', $note ?: __('Handmatig gecontroleerd door :name', ['name' => $by->name]), [], $by->id);
        if ($kind === 'phone') {
            $provider->forceFill(['phone_verified_at' => $provider->phone_verified_at ?: now()])->save();
        }
    }

    public function approve(Provider $provider, User $by): void
    {
        if (! in_array($provider->status, ['claimed', 'pending', 'suspended'], true)) {
            throw new \DomainException(__('Alleen een geclaimd, aangevraagd of geschorst profiel kan worden goedgekeurd.'));
        }
        if (! $this->ready($provider)) {
            throw new \DomainException(__('Nog niet alle verplichte controles zijn groen.'));
        }

        $provider->forceFill([
            'status' => 'verified',
            'status_reason' => null,
            'verified_at' => $provider->verified_at ?: now(),
            'suspended_at' => null,
            'suspended_until' => null,
            'optout_token' => $provider->optout_token ?: bin2hex(random_bytes(32)),
        ])->save();

        // Gratis administratie zolang het bedrijf Verified is.
        $provider->company?->forceFill(['trial_ends_at' => now()->addYears(5)])->save();

        Audit::log('verified', $provider, __('Fachora Verified toegekend door :name', ['name' => $by->name]), [], $provider->company_id);
        $this->notifier->provider($provider, 'verified');
    }

    public function suspend(Provider $provider, string $reason, ?string $until, ?User $by = null): void
    {
        $provider->forceFill(['status' => 'suspended', 'status_reason' => $reason, 'suspended_at' => now(), 'suspended_until' => $until ?: null])->save();
        $this->notifier->provider($provider, 'suspended', ['reason' => $reason, 'until' => $until]);
    }

    public function block(Provider $provider, string $reason, ?User $by = null): void
    {
        $provider->forceFill(['status' => 'blocked', 'status_reason' => $reason, 'blocked_at' => now()])->save();
        $this->notifier->provider($provider, 'blocked', ['reason' => $reason]);
    }

    /** Terug naar 'claimed' (of 'pending'): opnieuw beoordelen. */
    public function restore(Provider $provider): void
    {
        $provider->forceFill(['status' => $provider->verification_requested_at ? 'pending' : 'claimed', 'status_reason' => null, 'suspended_at' => null, 'suspended_until' => null, 'blocked_at' => null])->save();
    }

    /** Wekelijks: staat elk Verified bedrijf nog goed in het register, en is de polis nog geldig? */
    public function recheckAll(): int
    {
        $touched = 0;
        foreach (Provider::query()->verified()->orderBy('id')->get() as $provider) {
            $before = $provider->vat_status;
            if (filled($provider->nip) && ! $this->isManual($provider, 'registry')) {
                try {
                    $facts = app(NipService::class)->lookup($provider->nip);
                } catch (\Throwable $e) {
                    Log::info('Fachora: hercontrole register overgeslagen', ['provider' => $provider->id, 'error' => $e->getMessage()]);
                    continue;
                }
                if ($facts === null && $before === 'active') {
                    $this->suspend($provider, __('Het bedrijf staat niet meer in de btw-lijst; wij controleren het register.'), null);
                    $this->notifier->owner('provider_suspended', ['reason' => 'nip'], null, $provider);
                    $touched++;
                    continue;
                }
                if ($facts !== null) {
                    $provider->forceFill(['vat_status' => ProviderService::vatStatus($facts['vat_status']), 'vat_checked_at' => now()])->save();
                }
            }
            if ($provider->insurance_until !== null && $provider->insurance_until->isPast() && ! $this->isManual($provider, 'insurance')) {
                $this->set($provider, 'insurance', 'pending', __('Polis verlopen op :date', ['date' => $provider->insurance_until->format('d.m.Y')]));
                $touched++;
            } elseif ($provider->insurance_until !== null && $provider->insurance_until->lte(now()->addDays(30))) {
                // Verloopt binnen een maand: één keer waarschuwen (vastgelegd in de evidence van de controle).
                $check = $provider->checks()->where('kind', 'insurance')->first();
                if (empty($check?->evidence['expiry_warned'])) {
                    $this->notifier->provider($provider, 'insurance_expiring', ['until' => $provider->insurance_until->format('d.m.Y')]);
                    $this->set($provider, 'insurance', $check?->status ?? 'passed', $check?->note, array_merge((array) ($check?->evidence ?? []), ['expiry_warned' => now()->toDateString()]));
                    $touched++;
                }
            }
        }

        return $touched;
    }

    private function isManual(Provider $provider, string $kind): bool
    {
        return $provider->checks()->where('kind', $kind)->where('status', 'manual')->exists();
    }

    /** @param  array<string, mixed>  $evidence */
    private function set(Provider $provider, string $kind, string $status, ?string $note = null, array $evidence = [], ?int $by = null): ProviderCheck
    {
        return ProviderCheck::updateOrCreate(
            ['provider_id' => $provider->id, 'kind' => $kind],
            ['status' => $status, 'note' => $note ? mb_substr($note, 0, 300) : null, 'evidence' => $evidence ?: null, 'checked_at' => now(), 'checked_by' => $by],
        );
    }
}

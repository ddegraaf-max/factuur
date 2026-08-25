<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'is_demo', 'demo_expires_at',
        'trading_name', 'kvk_number', 'vat_number', 'iban', 'mollie_api_key',
        'email', 'phone', 'website',
        'address_line', 'postal_code', 'city', 'country', 'currency',
        'logo_path', 'logo_data', 'logo_scale', 'brand_color', 'accent_color', 'invoice_template', 'invoice_font',
        'stationery_data', 'stationery_margin_top', 'stationery_margin_bottom',
        'numbering_settings', 'price_mode', 'fiscal_year_start',
        'vat_period', 'ob_number', 'vat_reminder_enabled',
        'default_send_method', 'results_per_page',
        'copy_email', 'accountant_email', 'daily_notification_enabled', 'daily_notification_email',
        'reminder_settings', 'email_texts', 'thanks_mail_enabled', 'review_url',
        'default_payment_terms', 'default_hourly_rate', 'default_km_rate', 'invoice_footer', 'invoice_number_format',
        'quote_number_format', 'quote_valid_days',
        'trial_ends_at', 'trial_reminder_sent_at', 'trial_reminder_email_id', 'trial_ended_email_id',
        'subscription_status', 'subscription_ends_at', 'subscription_cancel_emailed_at',
        'stripe_customer_id', 'stripe_subscription_id',
        'plan', 'is_exempt',
    ];

    /**
     * Het logo (base64-data-URL, kan honderden KB's zijn) hoort niet in elke
     * Inertia-response: auth.company wordt op élke pagina meegestuurd. Waar het
     * logo echt nodig is (Huisstijl-pagina, portaal, e-mails) wordt het
     * expliciet opgevraagd via makeVisible() of directe attribuut-toegang.
     */
    // logo_data/stationery_data zijn te zwaar voor elke response; de Mollie-key
    // is geheim en mag nooit naar de browser (auth.company wordt op élke pagina gedeeld).
    // ob_number (omzetbelastingnummer) is bij eenmanszaken BSN-gebaseerd:
    // versleuteld opgeslagen en nooit naar de browser.
    protected $hidden = ['logo_data', 'stationery_data', 'mollie_api_key', 'ob_number'];

    protected $casts = [
        'mollie_api_key' => 'encrypted',
        'ob_number' => 'encrypted',
        'vat_reminder_enabled' => 'boolean',
        'is_demo' => 'boolean',
        'is_exempt' => 'boolean',
        'demo_expires_at' => 'datetime',
        'default_payment_terms' => 'integer',
        'quote_valid_days' => 'integer',
        'stationery_margin_top' => 'integer',
        'stationery_margin_bottom' => 'integer',
        'fiscal_year_start' => 'integer',
        'results_per_page' => 'integer',
        'logo_scale' => 'integer',
        'daily_notification_enabled' => 'boolean',
        'numbering_settings' => 'array',
        'reminder_settings' => 'array',
        'email_texts' => 'array',
        'thanks_mail_enabled' => 'boolean',
        'trial_ends_at' => 'datetime',
        'trial_reminder_sent_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'subscription_cancel_emailed_at' => 'datetime',
    ];

    public function users(): HasMany { return $this->hasMany(User::class); }

    /** Uniek inboek-adres voor het Postvak IN (bon-<token>@<inboekdomein>). */
    public function ensureInboundToken(): string
    {
        if (! $this->inbound_token) {
            do {
                $token = bin2hex(random_bytes(6)); // 12 tekens
            } while (static::where('inbound_token', $token)->exists());

            $this->forceFill(['inbound_token' => $token])->saveQuietly();
        }

        return $this->inbound_token;
    }

    public function inboundAddress(): ?string
    {
        $domain = config('services.inbound.domain');

        return $domain ? 'bon-' . $this->ensureInboundToken() . '@' . $domain : null;
    }

    /**
     * Het factuurtemplate dat daadwerkelijk gerenderd wordt. "stationery"
     * (eigen briefpapier) telt alleen als er ook echt briefpapier is
     * geüpload; anders vallen we terug op "modern".
     */
    public function resolvedInvoiceTemplate(): string
    {
        $template = $this->invoice_template;
        if ($template === 'stationery') {
            return $this->stationery_data ? 'stationery' : 'modern';
        }

        return in_array($template, ['modern', 'classic', 'minimal'], true) ? $template : 'modern';
    }

    /* ===================== CLAUDE-KOPPELING (MCP) ===================== */

    /** (Her)activeer de Claude-koppeling met een nieuw geheim; het oude vervalt. */
    public function rotateMcpToken(): string
    {
        do {
            $token = bin2hex(random_bytes(24)); // 48 tekens
        } while (static::where('mcp_token', $token)->exists());

        $this->forceFill(['mcp_token' => $token])->saveQuietly();

        return $token;
    }

    public function disableMcpToken(): void
    {
        $this->forceFill(['mcp_token' => null])->saveQuietly();
    }

    /** De geheime koppel-URL voor claude.ai (custom connector). */
    public function mcpUrl(): ?string
    {
        return $this->mcp_token
            ? rtrim(config('app.url'), '/') . '/mcp/' . $this->mcp_token
            : null;
    }
    /** Alle leden van deze administratie (lidmaatschappen, met rol per lid). */
    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps(); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function getResolvedNumberingAttribute(): array
    {
        $defaults = [
            'invoices'  => ['prefix' => '',   'start' => 1,     'current' => 0],
            'customers' => ['prefix' => 'KL', 'start' => 10000, 'current' => 0],
            'products'  => ['prefix' => 'P',  'start' => 1,     'current' => 0],
        ];
        return array_replace_recursive($defaults, $this->numbering_settings ?? []);
    }

    public function getResolvedRemindersAttribute(): array
    {
        $defaults = [
            'payment_term_reminder' => 2,
            'payment_term_warning'  => 1,
            'num_reminders'         => 2,
            'second_reminder_email' => 'first',
            'negative_outstanding'  => false,
            'reminder_delay'        => 0,
            'warning_delay'         => 0,
            'reminder_subject' => 'Herinnering: factuur {factuurnummer} is nog niet voldaan',
            'reminder_body' => "Beste {klant},\n\nUit onze administratie blijkt dat factuur {factuurnummer} van {factuurdatum} nog niet is voldaan. De vervaldatum was {vervaldatum}.\n\nWaarschijnlijk is dit aan uw aandacht ontsnapt. Wij verzoeken u vriendelijk het openstaande bedrag van {openstaand} binnen {termijn} dagen over te maken op {iban} t.n.v. {bedrijf}, onder vermelding van factuurnummer {factuurnummer}.\n\nHeeft u de betaling inmiddels gedaan? Dan kunt u deze herinnering als niet verzonden beschouwen.\n\nMet vriendelijke groet,\n{bedrijf}",
            'warning_subject' => 'Aanmaning: laatste verzoek tot betaling van factuur {factuurnummer}',
            'warning_body' => "Beste {klant},\n\nOndanks onze eerdere herinnering(en) staat factuur {factuurnummer} van {factuurdatum} nog steeds open. Het openstaande bedrag bedraagt {openstaand}.\n\nWij verzoeken u dringend dit bedrag binnen {termijn} dagen te voldoen op {iban} t.n.v. {bedrijf}, onder vermelding van factuurnummer {factuurnummer}. Blijft betaling uit, dan zijn wij genoodzaakt de vordering — verhoogd met de wettelijke incassokosten en rente — over te dragen aan onze incassopartner.\n\nMet vriendelijke groet,\n{bedrijf}",
        ];
        return array_replace($defaults, $this->reminder_settings ?? []);
    }

    /**
     * Eigen e-mailtekst (Instellingen → E-mailteksten), of null voor de
     * standaardtekst. Keys: invoice_subject, invoice_body, quote_subject,
     * quote_body. Zie App\Support\MailText voor de variabelen.
     */
    public function emailText(string $key): ?string
    {
        $value = trim((string) (($this->email_texts ?? [])[$key] ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * Het logo als ruwe bytes, klaar om als inline bijlage in een e-mail mee te
     * sturen. Een data:-URL rechtstreeks in een <img> werkt namelijk niet:
     * Gmail en Outlook strippen die weg, waardoor de klant een kapot plaatje
     * ziet. Via $message->embedData() wordt het een cid:-verwijzing, en dat
     * tonen alle mailprogramma's wel.
     *
     * @return array{data: string, mime: string, name: string}|null
     */
    public function logoBinary(): ?array
    {
        if (! $this->logo_data || ! str_starts_with($this->logo_data, 'data:')) {
            return null;
        }

        [$meta, $encoded] = array_pad(explode(',', $this->logo_data, 2), 2, null);
        if (! $encoded) {
            return null;
        }

        $mime = preg_match('#^data:([\w/+.-]+);base64$#', (string) $meta, $m) ? $m[1] : 'image/png';
        $bytes = base64_decode($encoded, true);

        if (! $bytes) {
            return null;
        }

        $extension = explode('/', $mime)[1] ?? 'png';

        return ['data' => $bytes, 'mime' => $mime, 'name' => 'logo.'.$extension];
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line,
            trim(($this->postal_code ?? '') . ' ' . ($this->city ?? '')),
        ])->filter()->implode(', ');
    }

    /* ===================== ABONNEMENT / PROEFPERIODE ===================== */

    /** Heeft een betaald abonnement dat nog loopt. */
    public function subscriptionActive(): bool
    {
        return $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isFuture();
    }

    /** Zit nog in de gratis proefperiode (en heeft (nog) geen lopend abonnement). */
    public function onTrial(): bool
    {
        return ! $this->subscriptionActive()
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /** Heeft toegang tot de app (vrijgesteld, proef of betaald). */
    public function hasAccess(): bool
    {
        return $this->is_exempt || $this->onTrial() || $this->subscriptionActive();
    }

    /**
     * Toegang tot de AI-functies (bonherkenning, offerte uit tekst, Postvak
     * IN-voorstellen). Zit in het Slim-abonnement; tijdens de proefperiode en
     * in de demo mag alles, zodat mensen de functies kunnen ervaren.
     */
    public function hasAiAccess(): bool
    {
        return $this->is_exempt
            || $this->is_demo
            || $this->onTrial()
            || ($this->subscriptionActive() && $this->plan === 'slim');
    }

    /** Tot wanneer loopt de toegang (proef of abonnement). */
    public function accessEndsAt(): ?Carbon
    {
        // Vrijgesteld = oneindig: er bestaat geen einddatum, ook niet als er
        // (nog) een abonnement of oude proefperiode aan het account hangt.
        if ($this->is_exempt) {
            return null;
        }
        if ($this->subscriptionActive()) {
            return $this->subscription_ends_at;
        }
        if ($this->onTrial()) {
            return $this->trial_ends_at;
        }

        return $this->subscription_ends_at ?? $this->trial_ends_at;
    }

    /** Aantal volledige dagen dat de toegang nog loopt (0 als verlopen). */
    public function daysLeft(): int
    {
        $end = $this->accessEndsAt();
        if (! $end || $end->isPast()) {
            return 0;
        }

        return (int) ceil(now()->floatDiffInDays($end));
    }

    /** Status voor de UI: 'exempt' | 'trialing' | 'active' | 'expired'. */
    public function accessStatus(): string
    {
        if ($this->is_exempt) {
            return 'exempt';
        }
        if ($this->subscriptionActive()) {
            return 'active';
        }
        if ($this->onTrial()) {
            return 'trialing';
        }

        return 'expired';
    }

    /* ===================== AI-GEBRUIK (fair use) ===================== */

    /** AI-acties van deze maand, uitgesplitst per soort. */
    public function aiUsageThisMonth(): array
    {
        $rows = AiUsageEvent::where('company_id', $this->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('kind, COUNT(*) AS c')
            ->groupBy('kind')
            ->pluck('c', 'kind');

        return [
            'receipt_scans' => (int) ($rows['receipt_scan'] ?? 0),
            'quote_parses' => (int) ($rows['quote_parse'] ?? 0),
            'total' => (int) $rows->sum(),
        ];
    }

    /** Maandlimiet voor AI-acties; null = onbeperkt (vrijgesteld of limiet uit). */
    public function aiMonthlyLimit(): ?int
    {
        if ($this->is_exempt) {
            return null;
        }
        $limit = (int) config('services.anthropic.monthly_limit', 250);

        return $limit > 0 ? $limit : null;
    }

    public function aiLimitReached(): bool
    {
        $limit = $this->aiMonthlyLimit();

        return $limit !== null && $this->aiUsageThisMonth()['total'] >= $limit;
    }

    /** Compacte samenvatting voor het frontend. */
    public function subscriptionSummary(): array
    {
        return [
            'status' => $this->accessStatus(),
            'has_access' => $this->hasAccess(),
            'days_left' => $this->daysLeft(),
            'ends_at' => optional($this->accessEndsAt())->toIso8601String(),
            'on_trial' => $this->onTrial(),
            'stripe_status' => $this->subscription_status,
            'has_subscription' => $this->subscription_ends_at !== null,
            'plan' => $this->plan ?? 'basis',
            'is_exempt' => (bool) $this->is_exempt,
            'has_ai' => $this->hasAiAccess(),
        ];
    }
}

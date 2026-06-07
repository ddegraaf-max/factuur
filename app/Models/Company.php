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
        'name', 'trading_name', 'kvk_number', 'vat_number', 'iban',
        'email', 'phone', 'website',
        'address_line', 'postal_code', 'city', 'country', 'currency',
        'logo_path', 'logo_data', 'logo_scale', 'brand_color', 'accent_color', 'invoice_template', 'invoice_font',
        'numbering_settings', 'price_mode', 'fiscal_year_start',
        'default_send_method', 'results_per_page',
        'copy_email', 'daily_notification_enabled', 'daily_notification_email',
        'reminder_settings',
        'default_payment_terms', 'invoice_footer', 'invoice_number_format',
        'trial_ends_at', 'trial_reminder_sent_at', 'trial_reminder_email_id',
        'subscription_status', 'subscription_ends_at',
        'stripe_customer_id', 'stripe_subscription_id',
    ];

    protected $casts = [
        'default_payment_terms' => 'integer',
        'fiscal_year_start' => 'integer',
        'results_per_page' => 'integer',
        'logo_scale' => 'integer',
        'daily_notification_enabled' => 'boolean',
        'numbering_settings' => 'array',
        'reminder_settings' => 'array',
        'trial_ends_at' => 'datetime',
        'trial_reminder_sent_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function users(): HasMany { return $this->hasMany(User::class); }
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
        ];
        return array_replace($defaults, $this->reminder_settings ?? []);
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

    /** Heeft toegang tot de app (proef of betaald). */
    public function hasAccess(): bool
    {
        return $this->onTrial() || $this->subscriptionActive();
    }

    /** Tot wanneer loopt de toegang (proef of abonnement). */
    public function accessEndsAt(): ?Carbon
    {
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

    /** Status voor de UI: 'trialing' | 'active' | 'expired'. */
    public function accessStatus(): string
    {
        if ($this->subscriptionActive()) {
            return 'active';
        }
        if ($this->onTrial()) {
            return 'trialing';
        }

        return 'expired';
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
        ];
    }
}

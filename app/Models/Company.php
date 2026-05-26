<?php

namespace App\Models;

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
        'logo_path', 'logo_data', 'brand_color', 'accent_color', 'invoice_template', 'invoice_font',
        'numbering_settings', 'price_mode', 'fiscal_year_start',
        'default_send_method', 'results_per_page',
        'copy_email', 'daily_notification_enabled', 'daily_notification_email',
        'reminder_settings',
        'default_payment_terms', 'invoice_footer', 'invoice_number_format',
    ];

    protected $casts = [
        'default_payment_terms' => 'integer',
        'fiscal_year_start' => 'integer',
        'results_per_page' => 'integer',
        'daily_notification_enabled' => 'boolean',
        'numbering_settings' => 'array',
        'reminder_settings' => 'array',
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
}

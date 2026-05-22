<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'name', 'type', 'contact_name', 'email', 'phone',
        'kvk_number', 'vat_number',
        'address_line', 'postal_code', 'city', 'country',
        'payment_terms', 'notes',
    ];

    protected $casts = [
        'payment_terms' => 'integer',
    ];

    protected static function booted(): void
    {
        // Auto-scope to current user's company
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('customers.company_id', auth()->user()->company_id);
            }
        });

        // Auto-fill company_id on create
        static::creating(function (Customer $customer) {
            if (! $customer->company_id && auth()->check()) {
                $customer->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return strtoupper($first . $last);
    }

    public function getOutstandingTotalAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->get()
            ->sum(fn ($i) => $i->total - $i->paid_total);
    }
}

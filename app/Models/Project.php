<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een project: de paraplu over de offertes, facturen, inkoopfacturen, uren,
 * ritten en uitvragen die bij één klus horen, met een voorcalculatie.
 */
class Project extends Model
{
    public const STATUSES = ['open', 'closed'];

    /** De kostensoorten van de calculatie, in de volgorde van het scherm. */
    public const KINDS = ['labour', 'material', 'subcontract', 'other'];

    protected $fillable = [
        'company_id', 'customer_id', 'number', 'name', 'status', 'location', 'starts_on', 'ends_on', 'description', 'agreed_price', 'auto_earlier',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'agreed_price' => 'decimal:2',
        'auto_earlier' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('projects.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Project $project) {
            if (! $project->company_id && auth()->check()) {
                $project->company_id = auth()->user()->company_id;
            }
            if (! $project->number) {
                $project->number = static::nextNumber((int) $project->company_id);
            }
        });
    }

    /** P-0001, P-0002, … per administratie. */
    public static function nextNumber(int $companyId): string
    {
        $last = static::withoutGlobalScope('company')->where('company_id', $companyId)
            ->where('number', 'like', 'P-%')
            ->orderByDesc('id')
            ->value('number');
        $n = $last && preg_match('/(\d+)$/', $last, $m) ? (int) $m[1] + 1 : 1;

        return 'P-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withoutGlobalScope('company');
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(ProjectBudgetLine::class)->orderBy('sort')->orderBy('id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->withoutGlobalScope('company');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->withoutGlobalScope('company');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class)->withoutGlobalScope('company');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class)->withoutGlobalScope('company');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class)->withoutGlobalScope('company');
    }

    public function tenderRounds(): HasMany
    {
        return $this->hasMany(TenderRound::class)->withoutGlobalScope('company');
    }

    /** De tijdslijn: onderdelen op volgorde van start. */
    public function planItems(): HasMany
    {
        return $this->hasMany(ProjectPlanItem::class)->orderByRaw('starts_on is null, starts_on')->orderBy('sort')->orderBy('id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function label(): string
    {
        return $this->number . ' · ' . $this->name;
    }
}

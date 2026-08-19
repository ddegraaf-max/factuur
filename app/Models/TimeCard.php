<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Strippenkaart: een vooraf betaald urentegoed voor één klant. Geschreven
 * uren worden automatisch afgeschreven zolang het tegoed toereikend is;
 * gedekte urenregels (time_card_id gezet) zijn afgehandeld en komen nooit
 * meer op een losse factuur. Past een regel niet meer in het resterende
 * tegoed, dan blijft hij gewoon factureerbaar — voorspelbaar en zonder
 * halve afschrijvingen.
 */
class TimeCard extends Model
{
    protected $fillable = [
        'company_id', 'customer_id', 'invoice_id',
        'name', 'total_minutes', 'price', 'valid_until',
    ];

    protected $casts = [
        'total_minutes' => 'integer',
        'price' => 'decimal:2',
        'valid_until' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('time_cards.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (TimeCard $card) {
            if (! $card->company_id && auth()->check()) {
                $card->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class)->withoutGlobalScope('company'); }
    public function entries(): HasMany { return $this->hasMany(TimeEntry::class); }

    public function usedMinutes(): int
    {
        return (int) $this->entries()->sum('minutes');
    }

    public function remainingMinutes(): int
    {
        return max(0, $this->total_minutes - $this->usedMinutes());
    }

    /** Verlopen kaarten schrijven niet meer af (bestaande dekkingen blijven staan). */
    public function isUsable(): bool
    {
        return ! $this->valid_until || ! $this->valid_until->isPast();
    }

    /**
     * (Her)beoordeel de dekking van een urenregel. Wordt aangeroepen bij het
     * schrijven, bewerken en bij het stoppen van de timer:
     *  - een gekoppelde regel die niet meer past (klant gewisseld, meer uren,
     *    kaart verlopen) wordt losgekoppeld;
     *  - een ongekoppelde regel wordt gedekt door de oudste kaart van de
     *    klant waar hij volledig in past.
     */
    public static function apply(TimeEntry $entry): void
    {
        // Verse databasestate: zo tellen ook databasedefaults (billable) mee
        // wanneer de aanroeper ze niet expliciet heeft gezet.
        $entry->refresh();

        // Bestaande koppeling nog geldig?
        if ($entry->time_card_id) {
            $card = static::withoutGlobalScope('company')->find($entry->time_card_id);
            $fits = $card
                && $card->customer_id === $entry->customer_id
                && $entry->billable
                && ! $entry->invoice_id
                && $card->isUsable()
                && ($card->total_minutes - (int) $card->entries()->whereKeyNot($entry->id)->sum('minutes')) >= $entry->minutes;

            if ($fits) {
                return;
            }
            $entry->forceFill(['time_card_id' => null])->saveQuietly();
        }

        if ($entry->invoice_id || ! $entry->billable || ! $entry->customer_id
            || $entry->minutes <= 0 || $entry->timer_started_at) {
            return;
        }

        $card = static::withoutGlobalScope('company')
            ->where('company_id', $entry->company_id)
            ->where('customer_id', $entry->customer_id)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->orderBy('id')
            ->get()
            ->first(fn ($c) => $c->remainingMinutes() >= $entry->minutes);

        if ($card) {
            $entry->forceFill(['time_card_id' => $card->id])->saveQuietly();
        }
    }
}

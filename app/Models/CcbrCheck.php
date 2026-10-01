<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén controle in het Centraal Curatele- en Bewindregister.
 *
 * Bewust géén onderdeel van de klantscore: de gebruiksvoorwaarden staan alleen
 * toe dat je handelspartijen informeert over de curatele of het bewind, en een
 * score is een ander doel. Zie CcbrService voor die redenering.
 */
class CcbrCheck extends Model
{
    protected $fillable = [
        'company_id', 'customer_id', 'checked_by', 'checked_at',
        'achternaam', 'voorvoegsel', 'geboortedatum', 'geboortejaar',
        'gevonden', 'maatregel', 'grond', 'grond_tekst', 'kaartnummer',
        'ingangsdatum', 'einddatum', 'rechtbank', 'beperkt_bewind',
        'vertegenwoordigers', 'volledige_match', 'vernietigen_op',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'geboortedatum' => 'date:Y-m-d',
        'ingangsdatum' => 'date:Y-m-d',
        'einddatum' => 'date:Y-m-d',
        'vernietigen_op' => 'date:Y-m-d',
        'gevonden' => 'boolean',
        'beperkt_bewind' => 'boolean',
        'volledige_match' => 'boolean',
        'vertegenwoordigers' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('ccbr_checks.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (CcbrCheck $check) {
            if (! $check->company_id && auth()->check()) {
                $check->company_id = auth()->user()->company_id;
            }

            if (! $check->checked_at) {
                $check->checked_at = now();
            }

            // De bewaartermijn hoort bij het vastleggen, niet bij het opruimen:
            // zo staat hij vast ook als er daarna nooit meer iemand kijkt.
            $check->vernietigen_op ??= static::vernietigingsdatum(
                $check->checked_at,
                $check->einddatum
            );
        });
    }

    /**
     * Wanneer deze gegevens vernietigd moeten zijn.
     *
     * Artikel 2 van de gebruiksvoorwaarden: binnen zes maanden na beëindiging
     * van de maatregel. Of de maatregel is geëindigd weet je alleen door
     * opnieuw te bevragen, dus wordt hier de vroegste van twee genomen: zes
     * maanden na deze controle, of zes maanden na de einddatum als het register
     * die al meegaf. Daarmee wordt de termijn altijd gehaald.
     */
    public static function vernietigingsdatum(mixed $gecontroleerdOp, mixed $einddatum = null): \Illuminate\Support\Carbon
    {
        $uiterlijk = \Illuminate\Support\Carbon::parse($gecontroleerdOp)->addMonths(6);

        if ($einddatum) {
            $naEinde = \Illuminate\Support\Carbon::parse($einddatum)->addMonths(6);
            if ($naEinde->lt($uiterlijk)) {
                return $naEinde->startOfDay();
            }
        }

        return $uiterlijk->startOfDay();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /** Is dit antwoord nog bruikbaar, of is het te oud om op te varen? */
    public function verlopen(): bool
    {
        return $this->vernietigen_op->isPast();
    }
}

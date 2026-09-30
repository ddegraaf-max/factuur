<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Een onderdeel op de tijdslijn van een project: meestal een gegunde
 * uitvraag met de onderaannemer erbij, soms eigen werk. Met de datums, de
 * status en wat er automatisch naar de onderaannemer is gegaan.
 */
class ProjectPlanItem extends Model
{
    public const STATUSES = ['planned', 'started', 'done'];

    protected $fillable = [
        'project_id', 'tender_round_id', 'subcontractor_id', 'title', 'starts_on', 'ends_on', 'status', 'done_on', 'sort', 'notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'done_on' => 'date',
        'request_start' => 'date',
        'request_answer_start' => 'date',
        'headsup_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'problem_at' => 'datetime',
        'request_sent_at' => 'datetime',
        'request_answered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectPlanItem $item) {
            if (! $item->token) {
                $item->token = Str::random(48);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withoutGlobalScope('company');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(TenderRound::class, 'tender_round_id')->withoutGlobalScope('company');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class)->withoutGlobalScope('company');
    }

    /** Is er iemand om te mailen? */
    public function mailable(): bool
    {
        return filled($this->subcontractor?->email);
    }

    /** Een verzoek om eerder te beginnen dat nog geen antwoord heeft. */
    public function requestPending(): bool
    {
        return $this->request_sent_at !== null && $this->request_answered_at === null;
    }

    /** Aantal kalenderdagen van het onderdeel (minstens één). */
    public function days(): int
    {
        if (! $this->starts_on || ! $this->ends_on) {
            return 1;
        }

        return max(1, $this->starts_on->diffInDays($this->ends_on) + 1);
    }

    public function url(): string
    {
        return route('plan.show', $this->token);
    }

    /** Eerstvolgende werkdag op of na de datum. */
    public static function workday(Carbon $day): Carbon
    {
        $day = $day->copy()->startOfDay();
        while ($day->isWeekend()) {
            $day->addDay();
        }

        return $day;
    }
}

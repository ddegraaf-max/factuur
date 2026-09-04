<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Eén regel in het logboek (zie App\Support\Audit). */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'user_id', 'user_name', 'action', 'subject_type', 'subject_id',
        'subject_label', 'description', 'changes', 'ip', 'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTION_LABELS = [
        'created' => 'Aangemaakt',
        'updated' => 'Gewijzigd',
        'deleted' => 'Verwijderd',
        'sent' => 'Verstuurd',
        'reminded' => 'Herinnering',
        'accepted' => 'Geaccepteerd',
        'rejected' => 'Afgewezen',
        'reopened' => 'Opnieuw aangeboden',
        'paid' => 'Betaald',
        'login' => 'Ingelogd',
        'logout' => 'Uitgelogd',
        'exported' => 'Geëxporteerd',
        'purged' => 'Opgeruimd',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('activity_logs.company_id', auth()->user()->company_id);
            }
        });
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst($this->action);
    }
}

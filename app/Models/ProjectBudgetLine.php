<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén regel van de voorcalculatie van een project: kostensoort, omschrijving, bedrag exclusief btw. */
class ProjectBudgetLine extends Model
{
    protected $fillable = ['project_id', 'kind', 'description', 'amount', 'sort'];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

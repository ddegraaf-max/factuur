<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Website van een administratie (publiek onder /s/{slug}); inhoud als JSON. */
class CompanySite extends Model
{
    protected $fillable = ['company_id', 'published', 'content', 'answers', 'generated_at'];

    protected $casts = [
        'published' => 'boolean',
        'content' => 'array',
        'answers' => 'array',
        'generated_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    /** Heeft de site bruikbare inhoud (minimaal een titel)? */
    public function hasContent(): bool
    {
        return trim((string) ($this->content['hero']['title'] ?? '')) !== '';
    }
}

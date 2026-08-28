<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Digitaal visitekaartje van een administratie (publiek onder /k/{slug}). */
class BusinessCard extends Model
{
    protected $fillable = [
        'company_id', 'published', 'contact_name', 'job_title', 'tagline', 'whatsapp', 'linkedin_url',
        'show_kvk', 'show_vat', 'show_address',
    ];

    protected $casts = [
        'published' => 'boolean',
        'show_kvk' => 'boolean',
        'show_vat' => 'boolean',
        'show_address' => 'boolean',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    /** WhatsApp-link: Nederlands 06-nummer wordt internationaal (316…). */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->whatsapp) ?: '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '31' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }
}

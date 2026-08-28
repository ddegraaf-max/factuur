<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Verwarringsincident (merkbewaking). Platformbreed; alleen voor de eigenaar. */
class BrandIncident extends Model
{
    public const SOURCES = [
        'contactformulier' => 'Contactformulier',
        'verwarringspagina' => 'Pagina "Zocht u een ander EasyInvoice?"',
        'telefoon' => 'Telefoon',
        'e-mail' => 'E-mail',
        'handmatig' => 'Handmatig',
    ];

    protected $fillable = [
        'occurred_on', 'source', 'name', 'email', 'summary', 'evidence',
        'attachment_name', 'attachment_mime', 'attachment_data', 'created_by',
    ];

    protected $casts = ['occurred_on' => 'date'];

    protected $hidden = ['attachment_data'];
}

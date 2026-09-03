<?php

namespace App\Models;

use App\Services\VvemaatService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'invoice_id', 'kind', 'amount', 'paid_on',
        'method', 'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('payments.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Payment $payment) {
            if (! $payment->company_id && auth()->check()) {
                $payment->company_id = auth()->user()->company_id;
            }
        });

        static::saved(function (Payment $payment) {
            // Recalculate parent invoice
            $invoice = $payment->invoice()->withoutGlobalScope('company')->first();
            if ($invoice) {
                $invoice->paid_total = $invoice->payments()->sum('amount');
                $invoice->refreshStatus();
                $invoice->saveQuietly();

                /*
                 * Is dit een abonnementsfactuur van een VvE-omgeving, dan hoort
                 * VvEMaat te weten dat er betaald is — anders blijft daar een
                 * vereniging op slot staan die haar rekening gewoon heeft
                 * voldaan.
                 *
                 * Dit hangt hier en niet bij Mollie, het bankafletteren of het
                 * handmatig afboeken. Een betaling ontstaat op vijf plekken in
                 * deze applicatie, en dit is de enige waar ze alle vijf
                 * langskomen. Aanhaken bij die vijf afzonderlijk betekent dat
                 * de zesde het vergeet.
                 *
                 * De melding kan een betaling nooit breken: de service vangt
                 * alles op. Wat niet aankomt laat vvemaat_notified_at leeg, en
                 * de planner probeert het later opnieuw.
                 */
                if ($invoice->status === 'paid' && ! $invoice->vvemaat_notified_at) {
                    app(VvemaatService::class)->meldBetaling($invoice);
                }
            }
        });

        static::deleted(function (Payment $payment) {
            $invoice = $payment->invoice()->withoutGlobalScope('company')->first();
            if ($invoice) {
                $invoice->paid_total = $invoice->payments()->sum('amount');
                $invoice->refreshStatus();
                $invoice->saveQuietly();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}

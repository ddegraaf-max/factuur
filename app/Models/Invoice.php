<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use HasFactory;
    use \App\Models\Concerns\HasBrandProfile;
    use \App\Models\Concerns\HasVatTreatment;

    protected $fillable = [
        'company_id', 'project_id', 'customer_id', 'brand_profile_id', 'number', 'portal_token', 'reference', 'status',
        'is_credit', 'credits_invoice_id',
        'invoice_date', 'due_date', 'payment_terms', 'language',
        'customer_name', 'customer_address_line', 'customer_postal_code',
        'customer_city', 'customer_country', 'customer_vat_number',
        'customer_kvk_number', 'customer_email',
        'subtotal', 'vat_total', 'total', 'paid_total', 'vat_breakdown', 'vat_reversed', 'vat_exempt',
        'notes', 'footer', 'internal_notes',
        'sent_at', 'scheduled_send_on', 'first_viewed_at', 'paid_at',
        'thanks_sent_at', 'thanks_sent_to',
        'incasso_sent_at', 'incasso_reference', 'incasso_handler', 'incasso_phase',
        // Welke periode deze factuur dekt. Gevuld door de terugkerende
        // facturatie, die hem exact kent. VvEMaat leidt daaruit af tot wanneer
        // een vereniging toegang heeft, dus raden is hier geen optie.
        'period_start', 'period_end',
    ];

    protected $casts = [
        'is_credit' => 'boolean',
        'vat_reversed' => 'boolean',
        'vat_exempt' => 'boolean',
        // Als jjjj-mm-dd naar het scherm: de gewone notatie rekent om naar UTC
        // en maakt van 28 september middernacht 27 september 22:00 uur, waarna
        // een datumveld de verkeerde dag toont en bij opslaan ook bewaart.
        'invoice_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'sent_at' => 'datetime',
        'scheduled_send_on' => 'date',
        'first_viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'thanks_sent_at' => 'datetime',
        'reminders_paused_at' => 'datetime',
        'reminders_paused_until' => 'date',
        'reminder_shift_days' => 'integer',
        'incasso_sent_at' => 'datetime',
        'peppol_sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'vat_breakdown' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'vvemaat_notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('invoices.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Invoice $invoice) {
            if (! $invoice->company_id && auth()->check()) {
                $invoice->company_id = auth()->user()->company_id;
            }
        });

        /*
         * Het grootboek bijwerken.
         *
         * Een concept boekt niet; op het moment dat de factuur definitief wordt
         * staat de status op 'sent' en zijn de regels er, en dan gaat de boeking
         * erin. Dit hangt aan het opslaan en niet aan het versturen, omdat een
         * factuur ook definitief kan worden zonder mail — bij het afletteren van
         * de bank, bij een import, of bij een creditnota.
         *
         * Mislukt de boeking, dan gaat het opslaan gewoon door: zie de
         * toelichting bij LedgerPostingService::sync().
         */
        static::saved(function (Invoice $invoice) {
            app(\App\Services\LedgerPostingService::class)->sync($invoice);
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withoutGlobalScope('company'); }
    public function lines(): HasMany { return $this->hasMany(InvoiceLine::class)->orderBy('sort_order'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function reminderLogs(): HasMany { return $this->hasMany(ReminderLog::class)->orderBy('sent_at'); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }
    public function creditNotes(): HasMany { return $this->hasMany(Invoice::class, 'credits_invoice_id'); }
    public function originalInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'credits_invoice_id')->withoutGlobalScope('company'); }
    public function views(): HasMany { return $this->hasMany(InvoiceView::class)->orderByDesc('viewed_at'); }
    /** Online aanmaningen bij deze factuur, de nieuwste eerst. */
    public function demands(): HasMany { return $this->hasMany(PaymentDemand::class)->withoutGlobalScope('company')->orderByDesc('id'); }

    /**
     * Wat er op de PDF van het factuurtotaal afgaat vóór "Te betalen": elke
     * geboekte betaling, aanbetaling, verrekening en kwijtschelding, op datum.
     *
     * Tot 1.76.8 stonden hier alleen aanbetalingen. Een betaling die je boekte
     * nadat de factuur was verstuurd, bleef op de PDF onzichtbaar: de herinnering
     * zei "nog € 710 te betalen" en de bijgevoegde factuur "Te betalen € 1.210".
     *
     * Een creditnota toont alleen aanbetalingen, zoals altijd: haar verrekening
     * met de factuur staat al op die factuur.
     */
    public function documentSettlements(): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->payments()->orderBy('paid_on')->orderBy('id');

        if ($this->is_credit) {
            $query->where('kind', 'advance');
        }

        return $query->get();
    }

    /**
     * Zorgt dat de factuur een geheime portaal-token heeft en geeft die terug.
     * Wordt aangeroepen bij het versturen (factuurmail en herinneringen).
     */
    public function ensurePortalToken(): string
    {
        if (! $this->portal_token) {
            $this->portal_token = bin2hex(random_bytes(32));
            $this->saveQuietly();
        }

        return $this->portal_token;
    }

    /** Volledige URL van deze factuur in het klantenportaal (of null zonder token). */
    public function portalUrl(): ?string
    {
        return $this->portal_token
            ? route('portal.invoice', $this->portal_token)
            : null;
    }

    /**
     * Staan herinneringen, aanmaningen en incasso op pauze? Een pauze met
     * einddatum loopt tot en met die dag en vervalt daarna vanzelf.
     */
    public function remindersPaused(): bool
    {
        if (! $this->reminders_paused_at) {
            return false;
        }

        return ! $this->reminders_paused_until
            || $this->reminders_paused_until->copy()->startOfDay()->gte(now()->startOfDay());
    }

    /** 'Op pauze' als status in lijsten: alleen zolang de factuur nog openstaat. */
    public function isPaused(): bool
    {
        return ! $this->is_credit
            && in_array($this->status, ['sent', 'partial', 'overdue'], true)
            && $this->remindersPaused();
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total - (float) $this->paid_total;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_credit) return false;
        return in_array($this->status, ['sent', 'partial'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) return 0;
        return (int) $this->due_date->diffInDays(now());
    }

    /**
     * Zet verstuurde facturen met een verstreken vervaldatum op 'overdue'.
     * Creditnota's slaan we over: daar valt niets te innen, dus die kunnen
     * nooit achterstallig zijn. (Het dashboard vergat die controle ooit,
     * waardoor creditnota's tóch als achterstallig te boek stonden.)
     */
    public static function markOverdue(): int
    {
        return static::query()
            ->where('status', 'sent')
            ->where('is_credit', false)
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    public function scopeOpen(Builder $query): Builder
    {
        // Creditnota's zijn geen vordering: die tellen nooit als openstaand.
        return $query->where('is_credit', false)->whereIn('status', ['sent', 'partial', 'overdue', 'incasso']);
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('is_credit', false);
    }

    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('is_credit', true);
    }

    /** Openstaande facturen waarvan de pauze nu loopt (zie isPaused()). */
    public function scopePaused(Builder $query): Builder
    {
        return $query->where('is_credit', false)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNotNull('reminders_paused_at')
            ->where(fn (Builder $q) => $q->whereNull('reminders_paused_until')
                ->orWhereDate('reminders_paused_until', '>=', now()));
    }

    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (! $status || $status === 'all') return $query;
        if ($status === 'creditnota') return $query->where('is_credit', true);
        if ($status === 'paused') return $this->scopePaused($query);
        return $query->where('is_credit', false)->where('status', $status);
    }

    public function refreshStatus(): void
    {
        if (in_array($this->status, ['draft', 'cancelled', 'incasso'])) return;

        $paid = (float) $this->paid_total;
        $total = (float) $this->total;

        // Een creditnota kent geen betaaltermijn: ze is verstuurd, of verrekend
        // (met de factuur, of terugbetaald) zodra het hele bedrag is geboekt.
        if ($this->is_credit) {
            $this->status = ($total > 0 && $paid >= $total - 0.004) ? 'settled' : 'sent';
            return;
        }

        if ($paid >= $total && $total > 0) {
            $this->status = 'paid';
            if (! $this->paid_at) $this->paid_at = now();
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'sent';
        }
    }
}

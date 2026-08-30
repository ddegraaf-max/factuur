<?php

namespace App\Services;

use App\Mail\WykupRequestMail;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Audit;
use App\Support\Market;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Windykacja (Poolse markt): wat een ondernemer zelf kan doen vóór de
 * overdracht aan sprzedamfakture.pl, en de aanvraag om een vordering te verkopen.
 *
 *  - Odsetki ustawowe za opóźnienie w transakcjach handlowych: stopa
 *    referencyjna NBP + 10 p.p. (config markets.pl.interest_rate), per dag.
 *  - Rekompensata za koszty odzyskiwania należności (art. 10 ustawy z 8 marca
 *    2013 r.): 40 / 70 / 100 EUR per vordering, naar hoogte van de hoofdsom,
 *    omgerekend naar PLN.
 *  - Wezwanie do zapłaty: formele (voor-gerechtelijke) aanmaning als PDF.
 *  - Wykup wierzytelności: verzoek aan sprzedamfakture.pl om de factuur te kopen.
 */
class WindykacjaService
{
    public const DEADLINE_DAYS = 7;

    public function interestRate(): float
    {
        $env = env('WINDYKACJA_INTEREST_RATE');

        return $env !== null && $env !== '' ? (float) $env : (float) Market::get('interest_rate', 0.14);
    }

    public function eurPln(): float
    {
        $env = env('WINDYKACJA_EUR_PLN');

        return $env !== null && $env !== '' ? (float) $env : (float) Market::get('eur_pln', 4.30);
    }

    /** Enkelvoudige rente over $days dagen (ACT/365). */
    public function interest(float $amount, int $days, ?float $rate = null): float
    {
        if ($amount <= 0 || $days <= 0) {
            return 0.0;
        }

        return round($amount * ($rate ?? $this->interestRate()) * $days / 365, 2);
    }

    /** @return array{eur:int, pln:float} vaste vergoeding naar hoogte van de hoofdsom (in PLN). */
    public function compensation(float $principal): array
    {
        $eur = $principal <= 5000 ? 40 : ($principal <= 50000 ? 70 : 100);

        return ['eur' => $eur, 'pln' => round($eur * $this->eurPln(), 2)];
    }

    public function daysOverdue(Invoice $invoice, ?Carbon $on = null): int
    {
        $on = $on ?? now();
        if (! $invoice->due_date || $invoice->due_date->gte($on->copy()->startOfDay())) {
            return 0;
        }

        return (int) $invoice->due_date->copy()->startOfDay()->diffInDays($on->copy()->startOfDay());
    }

    /**
     * De volledige vordering op een factuur: hoofdsom (openstaand), rente tot
     * vandaag, rekompensata en het totaal, plus de betaaltermijn van het wezwanie.
     */
    public function claim(Invoice $invoice, ?Carbon $on = null): array
    {
        $on = $on ?? now();
        $principal = round(max(0, (float) ($invoice->amount_due ?? $invoice->open_amount ?? $invoice->total)), 2);
        $days = $this->daysOverdue($invoice, $on);
        $interest = $this->interest($principal, $days);
        $compensation = $this->compensation($principal);

        return [
            'principal' => $principal,
            'days' => $days,
            'rate' => $this->interestRate(),
            'interest' => $interest,
            'compensation_eur' => $compensation['eur'],
            'compensation' => $compensation['pln'],
            'total' => round($principal + $interest + $compensation['pln'], 2),
            'deadline' => $on->copy()->addDays(self::DEADLINE_DAYS),
            'on' => $on,
        ];
    }

    /** Wezwanie do zapłaty als PDF (DomPDF). */
    public function wezwaniePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing('company', 'customer');

        return Pdf::loadView('pdf.wezwanie', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'claim' => $this->claim($invoice),
            'partner' => [
                'name' => Market::incasso('partner_name'),
                'website' => (string) Market::get('incasso.website', ''),
            ],
        ])->setPaper('a4');
    }

    /** Verzoek aan de partner om de vordering te kopen (wykup wierzytelności / cesja). */
    public function requestSale(Invoice $invoice, User $user, ?string $note = null): Invoice
    {
        if ($invoice->is_credit || $invoice->status === 'paid' || $invoice->status === 'draft') {
            throw new \DomainException(__('Alleen een verstuurde, onbetaalde factuur kan te koop worden aangeboden.'));
        }

        $invoice->forceFill(['sale_requested_at' => now()])->save();

        try {
            Mail::to(Market::incasso('claims_email'))
                ->cc(array_filter([Market::incasso('cc')]))
                ->send(new WykupRequestMail($invoice->fresh(), $user, $this->claim($invoice), $note));
        } catch (\Throwable $e) {
            Log::error('Wykup-verzoek mailen mislukt', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }

        Audit::log('invoice.sale_requested', $invoice, __('Factuur :number te koop aangeboden aan :partner', ['number' => $invoice->number, 'partner' => Market::incasso('partner_name')]), [], $invoice->company_id);

        return $invoice->fresh();
    }
}

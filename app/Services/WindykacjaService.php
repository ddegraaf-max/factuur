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

    public function __construct(private ?NbpService $nbp = null) {}

    private function nbp(): NbpService
    {
        return $this->nbp ??= app(NbpService::class);
    }

    /**
     * Wettelijke rente per periode (odsetki ustawowe za opóźnienie w transakcjach handlowych):
     * NBP-referentierente + 10 procentpunt, per halfjaar vastgesteld — 'interest_rates' in
     * config/markets.php. WINDYKACJA_INTEREST_RATE dwingt één vaste rente af.
     *
     * @return array<string, float> ingangsdatum (Y-m-d) => rente, oplopend gesorteerd
     */
    public function ratePeriods(): array
    {
        $env = env('WINDYKACJA_INTEREST_RATE');
        if ($env !== null && $env !== '') {
            return ['1970-01-01' => (float) $env];
        }

        $table = (array) Market::get('interest_rates', []);
        if ($table === []) {
            return ['1970-01-01' => (float) Market::get('interest_rate', 0.14)];
        }
        ksort($table);

        return array_map('floatval', $table);
    }

    /** De rente die op een bepaalde dag geldt (vóór de eerste periode: de eerste rente). */
    public function interestRateOn(Carbon $on): float
    {
        $periods = $this->ratePeriods();
        $day = $on->toDateString();
        $rate = null;
        foreach ($periods as $from => $r) {
            if ($from <= $day) {
                $rate = $r;
            } else {
                break;
            }
        }

        return $rate ?? (float) array_values($periods)[0];
    }

    /** Rente van vandaag (weergave, publieke calculator). */
    public function interestRate(): float
    {
        return $this->interestRateOn(now());
    }

    /** Vaste vangnetkoers EUR/PLN (zonder NBP). */
    public function eurPln(): float
    {
        return $this->nbp()->fallback()['rate'];
    }

    /**
     * Rente vanaf de dag ná de vervaldatum tot en met $on, gesegmenteerd per renteperiode
     * (een vordering die over 1 juli heen loopt, krijgt twee tarieven).
     *
     * @return array{total: float, periods: list<array{from: string, to: string, days: int, rate: float, amount: float}>}
     */
    public function interestBetween(float $amount, Carbon $due, Carbon $on): array
    {
        $start = $due->copy()->startOfDay()->addDay();
        $end = $on->copy()->startOfDay();
        if ($amount <= 0 || $end->lt($start)) {
            return ['total' => 0.0, 'periods' => []];
        }

        $boundaries = array_keys($this->ratePeriods());
        $segments = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $rate = $this->interestRateOn($cursor);
            $next = null;
            foreach ($boundaries as $b) {
                if ($b > $cursor->toDateString()) {
                    $next = Carbon::parse($b)->startOfDay();
                    break;
                }
            }
            $segEnd = ($next !== null && $next->copy()->subDay()->lt($end)) ? $next->copy()->subDay() : $end->copy();
            $days = (int) $cursor->diffInDays($segEnd) + 1;
            $segments[] = [
                'from' => $cursor->toDateString(),
                'to' => $segEnd->toDateString(),
                'days' => $days,
                'rate' => $rate,
                'amount' => round($amount * $rate * $days / 365, 2),
            ];
            $cursor = $segEnd->copy()->addDay();
        }

        return ['total' => round(array_sum(array_column($segments, 'amount')), 2), 'periods' => $segments];
    }

    /** Enkelvoudige rente over $days dagen (ACT/365). */
    public function interest(float $amount, int $days, ?float $rate = null): float
    {
        if ($amount <= 0 || $days <= 0) {
            return 0.0;
        }

        return round($amount * ($rate ?? $this->interestRate()) * $days / 365, 2);
    }

    /**
     * Vaste vergoeding (art. 10: 40/70/100 EUR naar hoogte van de hoofdsom), omgerekend tegen de
     * gemiddelde NBP-koers van de laatste werkdag van de maand vóór de vervalmaand. Zonder
     * vervaldatum (of zonder NBP) geldt de vaste vangnetkoers.
     *
     * @return array{eur: int, pln: float, rate: float, rate_date: ?string, source: string}
     */
    public function compensation(float $principal, ?Carbon $dueDate = null): array
    {
        $eur = $principal <= 5000 ? 40 : ($principal <= 50000 ? 70 : 100);
        $fx = $dueDate ? $this->nbp()->eurRateForDueDate($dueDate) : $this->nbp()->fallback();

        return ['eur' => $eur, 'pln' => round($eur * $fx['rate'], 2), 'rate' => $fx['rate'], 'rate_date' => $fx['date'], 'source' => $fx['source']];
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
        $interest = $invoice->due_date
            ? $this->interestBetween($principal, $invoice->due_date, $on)
            : ['total' => 0.0, 'periods' => []];
        $compensation = $this->compensation($principal, $invoice->due_date);
        // De vergoeding ontstaat pas zodra er rente loopt (dag ná de vervaldatum).
        $compensationPln = $days > 0 ? $compensation['pln'] : 0.0;

        return [
            'principal' => $principal,
            'days' => $days,
            'rate' => $this->interestRateOn($on),
            'interest' => $interest['total'],
            'interest_periods' => $interest['periods'],
            'compensation_eur' => $compensation['eur'],
            'compensation' => $compensationPln,
            'eur_pln' => $compensation['rate'],
            'eur_pln_date' => $compensation['rate_date'],
            'eur_pln_source' => $compensation['source'],
            'total' => round($principal + $interest['total'] + $compensationPln, 2),
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
            Mail::to(Market::wykup('email'))
                ->cc(array_filter([Market::wykup('cc')]))
                ->send(new WykupRequestMail($invoice->fresh(), $user, $this->claim($invoice), $note));
        } catch (\Throwable $e) {
            Log::error('Wykup-verzoek mailen mislukt', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }

        Audit::log('invoice.sale_requested', $invoice, __('Factuur :number te koop aangeboden aan :partner', ['number' => $invoice->number, 'partner' => Market::wykup('partner_name')]), [], $invoice->company_id);

        return $invoice->fresh();
    }
}

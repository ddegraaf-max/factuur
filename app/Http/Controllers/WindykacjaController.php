<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\NbpService;
use App\Services\WindykacjaService;
use Carbon\Carbon;
use App\Support\Market;
use Illuminate\Http\Request;

/**
 * Windykacja (Poolse markt): wezwanie do zapłaty als PDF en het verzoek om
 * een vordering te verkopen aan sprzedamfakture.pl (factuurkoper; geen incasso). De overdracht van een
 * dossier zelf loopt via IncassoController (partner per markt).
 */
class WindykacjaController extends Controller
{
    public function __construct(private WindykacjaService $service) {}

    /** Berekening van de vordering (hoofdsom, rente, rekompensata) voor de factuurpagina. */
    public function claim(Invoice $invoice)
    {
        abort_unless(Market::isPl(), 404);
        $this->authorizeInvoice($invoice);

        $claim = $this->service->claim($invoice);
        $claim['deadline'] = $claim['deadline']->toDateString();
        $claim['on'] = $claim['on']->toDateString();

        return response()->json($claim);
    }

    public function wezwanie(Invoice $invoice)
    {
        abort_unless(Market::isPl(), 404);
        $this->authorizeInvoice($invoice);
        abort_if($invoice->is_credit || $invoice->status === 'draft', 422, __('Alleen voor verstuurde facturen.'));

        $pdf = $this->service->wezwaniePdf($invoice);
        $name = 'wezwanie-do-zaplaty-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $invoice->number) . '.pdf';

        return $pdf->download($name);
    }

    public function sale(Request $request, Invoice $invoice)
    {
        abort_unless(Market::isPl(), 404);
        $this->authorizeInvoice($invoice);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->service->requestSale($invoice, $request->user(), $data['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('flash', __('Verzoek verstuurd naar :partner — zij nemen binnen één werkdag contact met je op.', ['partner' => Market::wykup('partner_name')]));
    }

    /**
     * Publiek (kalkulator odsetek): de renteperiodes en de NBP-koers die bij een vervaldatum hoort,
     * zodat de calculator op de site met dezelfde wettelijke waarden rekent als de app.
     */
    public function rates(Request $request, NbpService $nbp)
    {
        abort_unless(Market::isPl(), 404);
        $data = $request->validate(['termin' => ['nullable', 'date'], 'data' => ['nullable', 'date']]);

        $due = ! empty($data['termin']) ? Carbon::parse($data['termin']) : null;
        $on = ! empty($data['data']) ? Carbon::parse($data['data']) : now();
        $fx = $due ? $nbp->eurRateForDueDate($due) : $nbp->fallback();

        return response()->json([
            'periods' => collect($this->service->ratePeriods())->map(fn ($rate, $from) => ['from' => $from, 'rate' => $rate])->values(),
            'rate' => $this->service->interestRateOn($on),
            'eur_pln' => $fx['rate'],
            'eur_pln_date' => $fx['date'],
            'source' => $fx['source'],
        ]);
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless((int) $invoice->company_id === (int) auth()->user()->company_id, 404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Services\PaymentDemandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Online aanmaning vanuit de factuur: berekening vooraf, versturen, intrekken,
 * de brief als PDF en — als de termijn voorbij is — het dossier overdragen aan
 * de deurwaarder. De pagina van de klant zit in PaymentDemandPageController.
 */
class PaymentDemandController extends Controller
{
    public function __construct(private PaymentDemandService $service) {}

    /** De berekening voor het venster, bij de gekozen soort klant, termijn en rente. */
    public function preview(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($this->service->available(), 404);
        $data = $this->options($request);
        $invoice->loadMissing('company', 'customer');

        $claim = $this->service->preview(
            $invoice,
            $data['debtor_type'] ?? null,
            (bool) ($data['with_interest'] ?? true),
            isset($data['term_days']) ? (int) $data['term_days'] : null,
        );

        return response()->json([
            'blocker' => $this->service->blocker($invoice),
            'debtor_type' => $claim['debtor_type'],
            'term_days' => $claim['term_days'],
            'term_min' => $claim['debtor_type'] === 'consumer' ? PaymentDemandService::TERM_CONSUMER : PaymentDemandService::TERM_MIN_BUSINESS,
            'term_max' => PaymentDemandService::TERM_MAX,
            'deadline_label' => $claim['deadline']->translatedFormat('j F Y'),
            'sent_to' => $invoice->customer_email,
            'principal' => $claim['principal'],
            'interest' => $claim['interest'],
            'interest_days' => $claim['interest_days'],
            'rate' => $claim['rate'],
            'per_day' => $claim['per_day'],
            'costs' => $claim['costs'],
            'costs_vat' => $claim['costs_vat'],
            'costs_total' => $claim['costs_total'],
            'total' => $claim['total'],
            'total_after' => $claim['total_after'],
            // De dag waarop het dossier vanzelf overgaat, als daarvoor wordt gekozen.
            'auto_transfer_label' => $claim['deadline']->copy()->addWeekdays(PaymentDemandService::AUTO_TRANSFER_WEEKDAYS)->translatedFormat('j F Y'),
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($this->service->available(), 404);

        try {
            $demand = $this->service->send($invoice, $this->options($request));
        } catch (\DomainException $e) {
            return back()->withErrors(['demand' => $e->getMessage()]);
        }

        return back()->with('flash', __('Laatste aanmaning verstuurd naar :to. Betalen zonder incassokosten kan tot en met :date.', [
            'to' => $demand->sent_to, 'date' => $demand->deadline->translatedFormat('j F Y'),
        ]));
    }

    public function withdraw(Invoice $invoice, PaymentDemand $demand): RedirectResponse
    {
        $this->authorizeDemand($invoice, $demand);

        try {
            $this->service->withdraw($demand);
        } catch (\DomainException $e) {
            return back()->withErrors(['demand' => $e->getMessage()]);
        }

        return back()->with('flash', __('Aanmaning ingetrokken. Op de pagina van de klant staat dat ook.'));
    }

    /** Automatische overdracht van een lopende aanmaning aan- of uitzetten. */
    public function auto(Request $request, Invoice $invoice, PaymentDemand $demand): RedirectResponse
    {
        $this->authorizeDemand($invoice, $demand);
        $on = (bool) $request->validate(['auto_transfer' => ['required', 'boolean']])['auto_transfer'];

        try {
            $this->service->setAutoTransfer($demand, $on);
        } catch (\DomainException $e) {
            return back()->withErrors(['demand' => $e->getMessage()]);
        }

        return back()->with('flash', $on
            ? __('Automatische overdracht staat aan: op :date gaat het dossier naar de deurwaarder, als er dan niet is betaald.', ['date' => $this->service->autoTransferOn($demand)->translatedFormat('j F Y')])
            : __('Automatische overdracht staat uit. Overdragen doe je zelf met de knop.'));
    }

    /** Termijn voorbij en niet betaald: met één klik naar de deurwaarder. */
    public function transfer(Invoice $invoice, PaymentDemand $demand): RedirectResponse
    {
        $this->authorizeDemand($invoice, $demand);

        try {
            $fresh = $this->service->transfer($demand);
        } catch (\DomainException $e) {
            return back()->withErrors(['demand' => $e->getMessage()]);
        }

        return back()->with('flash', __('Dossier :reference overgedragen aan :partner, met de aanmaning en het logboek erbij.', [
            'reference' => $fresh->incasso_reference, 'partner' => \App\Support\Market::incasso('partner_name'),
        ]));
    }

    /** De brief zoals hij naar de klant is gegaan. */
    public function pdf(Invoice $invoice, PaymentDemand $demand): HttpResponse
    {
        $this->authorizeDemand($invoice, $demand);

        return $this->service->pdf($demand)->download('aanmaning-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $invoice->number) . '.pdf');
    }

    /** @return array<string, mixed> */
    private function options(Request $request): array
    {
        return $request->validate([
            'debtor_type' => ['nullable', Rule::in(['business', 'consumer'])],
            'term_days' => ['nullable', 'integer', 'min:1', 'max:' . PaymentDemandService::TERM_MAX],
            'with_interest' => ['nullable', 'boolean'],
            'auto_transfer' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeDemand(Invoice $invoice, PaymentDemand $demand): void
    {
        abort_unless($this->service->available() && (int) $demand->invoice_id === (int) $invoice->id, 404);
        $demand->setRelation('invoice', $invoice);
    }
}

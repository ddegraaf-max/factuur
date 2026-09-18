<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    public function __construct(private CreditNoteService $service) {}

    public function store(Request $request, Invoice $invoice)
    {
        $kind = $request->input('kind', 'full');
        if (! in_array($kind, ['full', 'partial'])) abort(422, 'Invalid kind');

        try {
            $credit = $this->service->createFromInvoice($invoice, $kind);
        } catch (\DomainException $e) {
            return back()->withErrors(['credit' => $e->getMessage()]);
        }

        // Volledige creditnota: meteen definitief (nummer) én verrekend met de factuur.
        if ($kind === 'full') {
            $settled = $this->service->finalize($credit);

            return redirect()->route('invoices.show', $credit)->with('flash', $this->finalizedFlash($credit->fresh(), $settled, $invoice));
        }

        // Partial: open as draft to edit
        return redirect()->route('invoices.edit', $credit)
            ->with('flash', __('Concept creditnota aangemaakt — pas regels aan.'));
    }

    public function finalize(Invoice $invoice)
    {
        try {
            $settled = $this->service->finalize($invoice);
        } catch (\DomainException $e) {
            abort(422, $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('flash', $this->finalizedFlash($invoice->fresh(), $settled, $invoice->originalInvoice));
    }

    /** Wat er is gebeurd: gemaild (naar wie) of niet (geen adres), en verrekend met welke factuur. */
    private function finalizedFlash(Invoice $credit, float $settled, ?Invoice $invoice): string
    {
        $message = $credit->customer_email
            ? __('Creditnota :number verstuurd naar :email.', ['number' => $credit->number, 'email' => $credit->customer_email])
            : __('Creditnota :number vastgelegd. Deze klant heeft geen e-mailadres — download de PDF om hem zelf te versturen.', ['number' => $credit->number]);

        if ($settled > 0 && $invoice) {
            $message .= ' ' . __('Verrekend met factuur :invoice.', ['invoice' => $invoice->number]);
        }

        return $message;
    }
}

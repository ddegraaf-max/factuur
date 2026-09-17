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

            return redirect()->route('invoices.show', $credit)
                ->with('flash', $settled > 0
                    ? __('Creditnota :number aangemaakt en verrekend met factuur :invoice.', ['number' => $credit->number, 'invoice' => $invoice->number])
                    : __('Creditnota :number aangemaakt en verstuurd.', ['number' => $credit->number]));
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

        return redirect()->route('invoices.show', $invoice)
            ->with('flash', $settled > 0
                ? __('Creditnota :number is definitief en verrekend met factuur :invoice.', ['number' => $invoice->number, 'invoice' => $invoice->originalInvoice?->number])
                : __('Creditnota :number verstuurd.', ['number' => $invoice->number]));
    }
}

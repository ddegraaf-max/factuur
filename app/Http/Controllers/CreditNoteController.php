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

        $credit = $this->service->createFromInvoice($invoice, $kind);

        // For full credit: also send immediately (so it has a number)
        if ($kind === 'full') {
            $credit->update([
                'number' => $this->service->nextNumber($credit->company),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            return redirect()->route('invoices.show', $credit)
                ->with('flash', 'Creditnota ' . $credit->number . ' aangemaakt en verstuurd.');
        }

        // Partial: open as draft to edit
        return redirect()->route('invoices.edit', $credit)
            ->with('flash', 'Concept creditnota aangemaakt — pas regels aan.');
    }

    public function finalize(Invoice $invoice)
    {
        if (! $invoice->is_credit || $invoice->status !== 'draft') {
            abort(422, 'Niet een conceptcreditnota.');
        }

        $invoice->update([
            'number' => $this->service->nextNumber($invoice->company),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('flash', 'Creditnota ' . $invoice->number . ' verstuurd.');
    }
}

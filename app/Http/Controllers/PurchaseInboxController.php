<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInboxItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Postvak IN voor inkoop: bijlagen uit doorgestuurde e-mails bekijken,
 * inboeken (via het gewone inkoopformulier, met scan & herken) of afwijzen.
 */
class PurchaseInboxController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'pending');

        $items = PurchaseInboxItem::with('purchaseInvoice:id,supplier_name')
            ->when($status === 'pending', fn ($q) => $q->where('status', 'pending'))
            ->when($status === 'done', fn ($q) => $q->whereIn('status', ['processed', 'dismissed']))
            ->orderByDesc('received_at')
            ->paginate(25)
            ->withQueryString();

        $items->getCollection()->transform(fn ($i) => [
            'id' => $i->id,
            'from_email' => $i->from_email,
            'subject' => $i->subject,
            'filename' => $i->filename,
            'is_image' => $i->isImage(),
            'size_label' => $i->size_bytes < 1048576
                ? round($i->size_bytes / 1024) . ' KB'
                : round($i->size_bytes / 1048576, 1) . ' MB',
            'status' => $i->status,
            'received_label' => $i->received_at->translatedFormat('j M Y, H:i'),
            'purchase_invoice_id' => $i->purchase_invoice_id,
            'purchase_supplier' => $i->purchaseInvoice?->supplier_name,
            // Boekingsvoorstel uit de automatische herkenning (of de foutmelding).
            'scanned' => $i->scanned_at !== null,
            'scan_error' => $i->scan_error,
            'proposal' => $i->scan ? [
                'supplier_name' => $i->scan['supplier_name'] ?? null,
                'invoice_date' => $i->scan['invoice_date'] ?? null,
                'category' => $i->scan['category'] ?? null,
                'total_incl' => round(collect($i->scan['vat_lines'] ?? [])
                    ->sum(fn ($l) => (float) ($l['base'] ?? 0) + (float) ($l['vat'] ?? 0)), 2),
                'warning' => $i->scan['warning'] ?? null,
            ] : null,
        ]);

        $company = $request->user()->company;

        return Inertia::render('Inkoop/Postvak', [
            'items' => $items,
            'filters' => ['status' => $status],
            'counts' => [
                'pending' => PurchaseInboxItem::where('status', 'pending')->count(),
                'done' => PurchaseInboxItem::whereIn('status', ['processed', 'dismissed'])->count(),
            ],
            // Zonder inboekdomein (env) toont de pagina de activatie-uitleg.
            'inbound_address' => $company->inboundAddress(),
            'configured' => filled(config('services.inbound.domain')) && filled(config('services.inbound.secret')),
            'scan_enabled' => app(\App\Services\ReceiptScanService::class)->enabled(),
        ]);
    }

    /**
     * Direct inboeken: maak de inkoopfactuur aan uit het boekingsvoorstel
     * (de automatische herkenning) — de gebruiker heeft het voorstel op het
     * kaartje gezien en bevestigt met één klik. Aanpassen kan altijd nog
     * via "Controleer eerst" of achteraf via bewerken.
     */
    public function book(PurchaseInboxItem $item): RedirectResponse
    {
        abort_unless($item->status === 'pending' && is_array($item->scan), 404);

        $scan = $item->scan;

        $lines = [];
        $subtotal = 0.0;
        $vatTotal = 0.0;
        foreach ($scan['vat_lines'] ?? [] as $line) {
            $base = round((float) ($line['base'] ?? 0), 2);
            $vat = round((float) ($line['vat'] ?? 0), 2);
            $lines[] = ['base' => $base, 'rate' => (float) ($line['rate'] ?? 0), 'vat' => $vat];
            $subtotal += $base;
            $vatTotal += $vat;
        }
        if ($lines === []) {
            return back()->with('flash', 'Dit voorstel bevat geen bedragen — boek het in via "Controleer eerst".');
        }

        // Herkende verrekeningen (bijv. "reeds ontvangen"): verlagen het te
        // betalen bedrag, niet de kosten of de voorbelasting.
        $deductions = collect($scan['deductions'] ?? [])
            ->filter(fn ($d) => (float) ($d['amount'] ?? 0) > 0)
            ->map(fn ($d) => [
                'description' => mb_substr(trim($d['description'] ?? 'Reeds ontvangen/verrekend'), 0, 190),
                'date' => $scan['invoice_date'] ?? null,
                'amount' => round((float) $d['amount'], 2),
            ])->values()->all();

        $purchase = \App\Models\PurchaseInvoice::create([
            'supplier_name' => $scan['supplier_name'] ?: 'Onbekende leverancier',
            'supplier_reference' => $scan['supplier_reference'] ?? null,
            'category' => $scan['category'] ?? null,
            'invoice_date' => $scan['invoice_date'] ?? $item->received_at->toDateString(),
            'due_date' => $scan['due_date'] ?? null,
            'status' => 'open',
            'subtotal' => round($subtotal, 2),
            'vat_total' => round($vatTotal, 2),
            'total' => round($subtotal + $vatTotal, 2),
            'vat_lines' => $lines,
            'deductions' => $deductions ?: null,
            'notes' => trim(($scan['notes'] ?? '') . "\nAutomatisch herkend uit e-mail (Postvak IN)."),
        ]);

        \App\Models\Attachment::create([
            'attachable_type' => \App\Models\PurchaseInvoice::class,
            'attachable_id' => $purchase->id,
            'filename' => $item->filename,
            'mime_type' => $item->mime_type,
            'size_bytes' => $item->size_bytes,
            'file_data' => $item->file_data,
        ]);

        $item->update(['status' => 'processed', 'purchase_invoice_id' => $purchase->id]);

        return redirect()->route('purchases.show', $purchase)
            ->with('flash', 'Inkoopfactuur ingeboekt vanuit het Postvak IN.');
    }

    /** Het bestand zelf (voor voorbeeld/thumbnail en het inkoopformulier). */
    public function file(PurchaseInboxItem $item): HttpResponse
    {
        $contents = $item->contents();
        abort_unless($contents !== null, 404);

        return response($contents)
            ->header('Content-Type', $item->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . addslashes($item->filename) . '"');
    }

    public function dismiss(PurchaseInboxItem $item): RedirectResponse
    {
        if ($item->status === 'pending') {
            $item->update(['status' => 'dismissed']);
        }

        return back()->with('flash', 'Item afgewezen — je kunt het later alsnog verwijderen.');
    }

    public function destroy(PurchaseInboxItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('flash', 'Item verwijderd uit het postvak.');
    }

    /** Nieuw inboek-adres genereren (het oude adres vervalt meteen). */
    public function rotateAddress(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $company->forceFill(['inbound_token' => null])->saveQuietly();
        $company->ensureInboundToken();

        return back()->with('flash', 'Nieuw inboek-adres aangemaakt — het oude adres werkt niet meer.');
    }
}

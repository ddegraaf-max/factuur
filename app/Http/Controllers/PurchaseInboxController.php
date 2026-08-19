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
        ]);
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

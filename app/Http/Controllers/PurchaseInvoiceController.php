<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\PurchaseInvoice;
use App\Services\ReceiptScanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inkoopfacturen (crediteuren): binnengekomen facturen van leveranciers
 * inboeken — handmatig, of met een foto/PDF van de bon erbij.
 *
 * De BTW van deze facturen is de voorbelasting (rubriek 5b) en wordt
 * automatisch meegenomen in het BTW-overzicht per kwartaal.
 */
class PurchaseInvoiceController extends Controller
{
    /** Vaste kostencategorieën — herkenbaar voor de aangifte en de boekhouder. */
    public const CATEGORIES = [
        'Inkoop goederen',
        'Uitbesteed werk',
        'Kantoor & administratie',
        'Software & ICT',
        'Telefoon & internet',
        'Marketing & reclame',
        'Reis- & verblijfkosten',
        'Auto & vervoer',
        'Huisvesting',
        'Verzekeringen',
        'Abonnementen & contributies',
        'Opleiding & ontwikkeling',
        'Bankkosten',
        'Overig',
    ];

    public function index(Request $request): Response
    {
        $status = $request->input('status', 'all');
        $q = $request->input('q');

        $purchases = PurchaseInvoice::withCount('attachments')
            ->when($status === 'open', fn ($qb) => $qb->where('status', 'open'))
            ->when($status === 'paid', fn ($qb) => $qb->where('status', 'paid'))
            ->when($status === 'overdue', fn ($qb) => $qb->where('status', 'open')->whereDate('due_date', '<', now()))
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('supplier_name', 'like', "%{$q}%")
                  ->orWhere('supplier_reference', 'like', "%{$q}%")
                  ->orWhere('category', 'like', "%{$q}%");
            }))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $purchases->getCollection()->transform(fn ($p) => [
            'id' => $p->id,
            'supplier_name' => $p->supplier_name,
            'supplier_reference' => $p->supplier_reference,
            'category' => $p->category,
            'invoice_date_label' => $p->invoice_date->translatedFormat('j M Y'),
            'due_date_label' => $p->due_date?->translatedFormat('j M Y'),
            'status' => $p->status,
            'is_overdue' => $p->is_overdue,
            'days_overdue' => $p->days_overdue,
            'total' => (float) $p->total,
            'vat_total' => (float) $p->vat_total,
            'attachments_count' => $p->attachments_count,
        ]);

        // Openstaand tellen we als "te betalen": het totaal minus verrekeningen
        // (bijv. al door een deurwaarder ontvangen bedragen).
        $all = PurchaseInvoice::query();
        $open = (clone $all)->where('status', 'open')->get(['total', 'due_date', 'deductions', 'supplier_name']);
        $overdue = $open->filter(fn ($p) => $p->due_date && $p->due_date->isPast());

        $quarterStart = now()->firstOfQuarter();
        $quarterVat = (float) PurchaseInvoice::whereDate('invoice_date', '>=', $quarterStart)->sum('vat_total');
        $yearBase = (float) PurchaseInvoice::whereYear('invoice_date', now()->year)->sum('subtotal');

        // Crediteurenoverzicht: bij wie staat het meeste open (te betalen)?
        $bySupplier = $open->groupBy('supplier_name')
            ->map(fn ($rows, $name) => [
                'supplier_name' => $name,
                'count' => $rows->count(),
                'open_total' => round($rows->sum(fn ($p) => $p->payable), 2),
            ])
            ->sortByDesc('open_total')
            ->take(5)
            ->values();

        $counts = [
            'all' => PurchaseInvoice::count(),
            'open' => PurchaseInvoice::where('status', 'open')->count(),
            'overdue' => $overdue->count(),
            'paid' => PurchaseInvoice::where('status', 'paid')->count(),
        ];

        return Inertia::render('Inkoop/Index', [
            'purchases' => $purchases,
            'filters' => ['status' => $status, 'q' => $q],
            'counts' => $counts,
            'stats' => [
                'open_total' => round($open->sum(fn ($p) => $p->payable), 2),
                'open_count' => $open->count(),
                'overdue_total' => round($overdue->sum(fn ($p) => $p->payable), 2),
                'overdue_count' => $overdue->count(),
                'quarter_vat' => round($quarterVat, 2),
                'quarter_label' => 'Q' . now()->quarter . ' ' . now()->year,
                'year_base' => round($yearBase, 2),
                'year_label' => (string) now()->year,
            ],
            'by_supplier' => $bySupplier,
        ]);
    }

    public function create(Request $request): Response
    {
        // Inboeken vanuit het Postvak IN: het aangeleverde bestand staat dan
        // alvast naast het formulier en is direct te scannen.
        $inboxItem = null;
        if ($request->filled('inbox')) {
            $item = \App\Models\PurchaseInboxItem::where('status', 'pending')->find($request->integer('inbox'));
            if ($item) {
                $inboxItem = [
                    'id' => $item->id,
                    'filename' => $item->filename,
                    'mime_type' => $item->mime_type,
                    'is_image' => $item->isImage(),
                    'from_email' => $item->from_email,
                    'url' => route('purchases.inbox.file', $item),
                    // Al automatisch herkend? Dan vult het formulier zich voor.
                    'scan' => $item->scan,
                ];
            }
        }

        $scanner = app(ReceiptScanService::class);
        $company = $request->user()->company;

        return Inertia::render('Inkoop/Form', [
            'purchase' => null,
            'suppliers' => $this->supplierSuggestions(),
            'categories' => self::CATEGORIES,
            'scan_enabled' => $scanner->availableFor($company),
            // De functie bestaat wél, maar zit in het Slim-abonnement: toon een upgradehint.
            'scan_locked' => $scanner->enabled() && ! $company->hasAiAccess(),
            'inbox_item' => $inboxItem,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($error = $this->deductionsError($data)) {
            return back()->withErrors(['deductions' => $error]);
        }
        $purchase = PurchaseInvoice::create($this->attributes($data));

        $this->saveAttachments($request, $purchase);

        // Vanuit het Postvak IN? Dan wordt het aangeleverde bestand als
        // bijlage gekoppeld en het postvak-item afgevinkt.
        if (! empty($data['inbox_id'])) {
            $item = \App\Models\PurchaseInboxItem::where('status', 'pending')->find($data['inbox_id']);
            if ($item) {
                Attachment::create([
                    'attachable_type' => PurchaseInvoice::class,
                    'attachable_id' => $purchase->id,
                    'filename' => $item->filename,
                    'mime_type' => $item->mime_type,
                    'size_bytes' => $item->size_bytes,
                    'file_data' => $item->file_data,
                ]);
                $item->update(['status' => 'processed', 'purchase_invoice_id' => $purchase->id]);
            }
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('flash', 'Inkoopfactuur ingeboekt.');
    }

    public function show(PurchaseInvoice $purchase): Response
    {
        $purchase->load('attachments');

        return Inertia::render('Inkoop/Show', [
            'purchase' => array_merge($purchase->toArray(), [
                'invoice_date_label' => $purchase->invoice_date->translatedFormat('j F Y'),
                'due_date_label' => $purchase->due_date?->translatedFormat('j F Y'),
                'paid_at_label' => $purchase->paid_at?->translatedFormat('j F Y'),
                'deductions' => collect($purchase->deductions ?? [])->map(fn ($d) => [
                    'description' => $d['description'] ?? '',
                    'date_label' => ! empty($d['date']) ? Carbon::parse($d['date'])->translatedFormat('j M Y') : null,
                    'amount' => (float) ($d['amount'] ?? 0),
                ])->values(),
                'deductions_total' => $purchase->deductions_total,
                'payable' => $purchase->payable,
                'is_overdue' => $purchase->is_overdue,
                'days_overdue' => $purchase->days_overdue,
                'attachments' => $purchase->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'kind' => $a->kind,
                    'size_formatted' => $a->size_formatted,
                    'uploaded_at_label' => $a->created_at?->translatedFormat('j M Y'),
                ]),
            ]),
        ]);
    }

    public function edit(Request $request, PurchaseInvoice $purchase): Response
    {
        $purchase->load('attachments');
        $scanner = app(ReceiptScanService::class);
        $company = $request->user()->company;

        return Inertia::render('Inkoop/Form', [
            'purchase' => array_merge($purchase->toArray(), [
                'invoice_date' => $purchase->invoice_date->format('Y-m-d'),
                'due_date' => $purchase->due_date?->format('Y-m-d'),
                'paid_at' => $purchase->paid_at?->format('Y-m-d'),
                'attachments' => $purchase->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'kind' => $a->kind,
                    'size_formatted' => $a->size_formatted,
                ]),
            ]),
            'suppliers' => $this->supplierSuggestions(),
            'categories' => self::CATEGORIES,
            'scan_enabled' => $scanner->availableFor($company),
            'scan_locked' => $scanner->enabled() && ! $company->hasAiAccess(),
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchase): RedirectResponse
    {
        $data = $this->validated($request);
        if ($error = $this->deductionsError($data)) {
            return back()->withErrors(['deductions' => $error]);
        }
        $purchase->update($this->attributes($data));

        $this->saveAttachments($request, $purchase);

        return redirect()->route('purchases.show', $purchase)
            ->with('flash', 'Inkoopfactuur bijgewerkt.');
    }

    public function destroy(PurchaseInvoice $purchase): RedirectResponse
    {
        $purchase->attachments()->delete();
        $purchase->delete();

        return redirect()->route('purchases.index')->with('flash', 'Inkoopfactuur verwijderd.');
    }

    public function markPaid(Request $request, PurchaseInvoice $purchase): RedirectResponse
    {
        $data = $request->validate([
            'paid_at' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:bank_transfer,ideal,cash,card,direct_debit,other'],
        ]);

        $purchase->update([
            'status' => 'paid',
            'paid_at' => $data['paid_at'],
            'payment_method' => $data['payment_method'] ?? null,
        ]);

        return back()->with('flash', 'Gemarkeerd als betaald.');
    }

    public function reopen(PurchaseInvoice $purchase): RedirectResponse
    {
        $purchase->update(['status' => 'open', 'paid_at' => null, 'payment_method' => null]);

        return back()->with('flash', 'Factuur staat weer open.');
    }

    /**
     * Scan & herken: lees een foto of PDF van een bon met AI en geef de
     * herkende formulierwaarden terug. De gebruiker controleert ze daarna
     * gewoon in het formulier — er wordt hier niets opgeslagen.
     */
    public function scan(Request $request, ReceiptScanService $scanner): JsonResponse
    {
        abort_unless($scanner->enabled(), 404);

        if (! $request->user()->company->hasAiAccess()) {
            return response()->json([
                'message' => 'Scan & herken zit in het Slim-abonnement. Upgrade via Instellingen → Abonnement.',
            ], 403);
        }

        if ($request->user()->company->aiLimitReached()) {
            return response()->json([
                'message' => 'Het maandelijkse AI-tegoed is opgebruikt (fair use). Volgende maand staat de teller weer op nul — tot die tijd kun je gewoon handmatig inboeken.',
            ], 429);
        }

        $request->validate([
            'file' => ['required_without:inbox_id', 'file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
            'inbox_id' => ['nullable', 'integer'],
        ], [
            'file.required_without' => 'Voeg eerst een foto of PDF van de bon toe.',
            'file.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden kunnen worden gescand.',
            'file.max' => 'Het bestand mag maximaal 10 MB groot zijn.',
        ]);

        // Bron: een upload uit het formulier, of een bestand uit het Postvak IN.
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $bytes = file_get_contents($file->getRealPath());
            $mime = $file->getMimeType() ?? 'application/octet-stream';
        } else {
            $item = \App\Models\PurchaseInboxItem::findOrFail($request->integer('inbox_id'));
            $bytes = $item->contents();
            $mime = $item->mime_type;
        }

        try {
            $result = $scanner->scan($bytes, $mime);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        \App\Models\AiUsageEvent::record($request->user()->company_id, 'receipt_scan', 'form');

        return response()->json(['result' => $result]);
    }

    public function storeAttachments(Request $request, PurchaseInvoice $purchase): RedirectResponse
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
        ], [
            'files.*.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden zijn toegestaan.',
            'files.*.max' => 'Elk bestand mag maximaal 10 MB groot zijn.',
        ]);

        $this->saveAttachments($request, $purchase);

        return back()->with('flash', 'Bijlage(n) toegevoegd.');
    }

    /** CSV-export van de inkoopadministratie voor de boekhouder. */
    public function csv(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfYear();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();

        $purchases = PurchaseInvoice::whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $filename = sprintf('easyinvoice-inkoop-%s-tm-%s.csv', $from->format('Y-m-d'), $to->format('Y-m-d'));

        return response()->streamDownload(function () use ($purchases) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Leverancier', 'Factuurnummer', 'Categorie', 'Factuurdatum', 'Vervaldatum',
                'Status', 'Betaald op',
                'Grondslag 21%', 'BTW 21%', 'Grondslag 9%', 'BTW 9%', 'Grondslag 0%',
                'Bedrag excl. BTW', 'BTW totaal', 'Bedrag incl. BTW', 'Verrekend', 'Te betalen', 'Notities',
            ], ';');

            $money = fn ($v) => number_format((float) $v, 2, ',', '');
            $sum = ['base21' => 0.0, 'vat21' => 0.0, 'base9' => 0.0, 'vat9' => 0.0, 'base0' => 0.0, 'subtotal' => 0.0, 'vat' => 0.0, 'total' => 0.0, 'deducted' => 0.0, 'payable' => 0.0];

            foreach ($purchases as $p) {
                $buckets = ['21' => ['base' => 0.0, 'vat' => 0.0], '9' => ['base' => 0.0, 'vat' => 0.0], '0' => ['base' => 0.0, 'vat' => 0.0]];
                foreach ($p->vat_lines ?? [] as $line) {
                    $key = (string) (int) (float) ($line['rate'] ?? 0);
                    if (! isset($buckets[$key])) $key = '0';
                    $buckets[$key]['base'] += (float) ($line['base'] ?? 0);
                    $buckets[$key]['vat'] += (float) ($line['vat'] ?? 0);
                }

                fputcsv($out, [
                    $p->supplier_name,
                    $p->supplier_reference ?? '',
                    $p->category ?? '',
                    $p->invoice_date->format('d-m-Y'),
                    $p->due_date?->format('d-m-Y') ?? '',
                    $p->status === 'paid' ? 'Betaald' : 'Open',
                    $p->paid_at?->format('d-m-Y') ?? '',
                    $money($buckets['21']['base']), $money($buckets['21']['vat']),
                    $money($buckets['9']['base']), $money($buckets['9']['vat']),
                    $money($buckets['0']['base']),
                    $money($p->subtotal), $money($p->vat_total), $money($p->total),
                    $money($p->deductions_total), $money($p->payable),
                    $p->notes ?? '',
                ], ';');

                $sum['base21'] += $buckets['21']['base'];
                $sum['vat21'] += $buckets['21']['vat'];
                $sum['base9'] += $buckets['9']['base'];
                $sum['vat9'] += $buckets['9']['vat'];
                $sum['base0'] += $buckets['0']['base'];
                $sum['subtotal'] += (float) $p->subtotal;
                $sum['vat'] += (float) $p->vat_total;
                $sum['total'] += (float) $p->total;
                $sum['deducted'] += $p->deductions_total;
                $sum['payable'] += $p->payable;
            }

            fputcsv($out, [
                'TOTAAL', '', '', '', '', '', '',
                $money($sum['base21']), $money($sum['vat21']),
                $money($sum['base9']), $money($sum['vat9']),
                $money($sum['base0']),
                $money($sum['subtotal']), $money($sum['vat']), $money($sum['total']),
                $money($sum['deducted']), $money($sum['payable']), '',
            ], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* ===================== Helpers ===================== */

    protected function supplierSuggestions(): array
    {
        return PurchaseInvoice::selectRaw('supplier_name, MAX(id) AS last_id')
            ->groupBy('supplier_name')
            ->orderByDesc('last_id')
            ->limit(100)
            ->pluck('supplier_name')
            ->all();
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'supplier_name' => ['required', 'string', 'max:180'],
            'supplier_reference' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:60'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'vat_lines' => ['required', 'array', 'min:1'],
            'vat_lines.*.base' => ['required', 'numeric', 'between:-9999999,9999999'],
            'vat_lines.*.rate' => ['required', 'numeric', 'in:0,9,21'],
            'vat_lines.*.vat' => ['required', 'numeric', 'between:-9999999,9999999'],
            // Verrekeningen: al ontvangen of ingehouden bedragen die het te
            // betalen bedrag verlagen (niet de kosten of de voorbelasting).
            'deductions' => ['nullable', 'array', 'max:10'],
            'deductions.*.description' => ['required', 'string', 'max:190'],
            'deductions.*.date' => ['nullable', 'date'],
            'deductions.*.amount' => ['required', 'numeric', 'min:0.01'],
            'is_paid' => ['nullable', 'boolean'],
            'paid_at' => ['nullable', 'date', 'required_if:is_paid,true'],
            'payment_method' => ['nullable', 'in:bank_transfer,ideal,cash,card,direct_debit,other'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
            'inbox_id' => ['nullable', 'integer'],
        ], [
            'supplier_name.required' => 'Vul de naam van de leverancier in.',
            'invoice_date.required' => 'Vul de factuurdatum in.',
            'vat_lines.required' => 'Voeg minstens één bedragregel toe.',
            'vat_lines.*.base.required' => 'Vul het bedrag exclusief BTW in.',
            'deductions.*.description.required' => 'Geef elke verrekening een omschrijving (bijv. "Ontvangen door deurwaarder").',
            'deductions.*.amount.required' => 'Vul bij elke verrekening een bedrag in.',
            'deductions.*.amount.min' => 'Vul bij elke verrekening een bedrag in.',
            'paid_at.required_if' => 'Vul de betaaldatum in.',
            'files.*.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden zijn toegestaan.',
            'files.*.max' => 'Elk bestand mag maximaal 10 MB groot zijn.',
        ]);
    }

    /** Databasevelden uit de gevalideerde invoer; totalen worden hier berekend. */
    protected function attributes(array $data): array
    {
        $lines = [];
        $subtotal = 0.0;
        $vatTotal = 0.0;

        foreach ($data['vat_lines'] as $line) {
            $base = round((float) $line['base'], 2);
            $vat = round((float) $line['vat'], 2);
            $lines[] = ['base' => $base, 'rate' => (float) $line['rate'], 'vat' => $vat];
            $subtotal += $base;
            $vatTotal += $vat;
        }

        $deductions = [];
        foreach ($data['deductions'] ?? [] as $d) {
            $deductions[] = [
                'description' => mb_substr(trim($d['description']), 0, 190),
                'date' => $d['date'] ?? null,
                'amount' => round((float) $d['amount'], 2),
            ];
        }

        $isPaid = (bool) ($data['is_paid'] ?? false);

        return [
            'supplier_name' => trim($data['supplier_name']),
            'supplier_reference' => $data['supplier_reference'] ?? null,
            'category' => $data['category'] ?? null,
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'] ?? null,
            'status' => $isPaid ? 'paid' : 'open',
            'paid_at' => $isPaid ? ($data['paid_at'] ?? now()->toDateString()) : null,
            'payment_method' => $isPaid ? ($data['payment_method'] ?? null) : null,
            'subtotal' => round($subtotal, 2),
            'vat_total' => round($vatTotal, 2),
            'total' => round($subtotal + $vatTotal, 2),
            'vat_lines' => $lines,
            'deductions' => $deductions ?: null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /** Verrekeningen mogen samen nooit boven het factuurtotaal uitkomen. */
    protected function deductionsError(array $data): ?string
    {
        $total = collect($data['vat_lines'])->sum(fn ($l) => (float) $l['base'] + (float) $l['vat']);
        $deducted = collect($data['deductions'] ?? [])->sum(fn ($d) => (float) $d['amount']);

        return $deducted > round($total, 2) + 0.009
            ? 'De verrekeningen zijn samen hoger dan het factuurtotaal.'
            : null;
    }

    protected function saveAttachments(Request $request, PurchaseInvoice $purchase): void
    {
        foreach ($request->file('files', []) as $file) {
            // In de database (base64), niet op schijf: het bestandssysteem van
            // Railway wordt bij elke deploy leeggegooid.
            Attachment::create([
                'attachable_type' => PurchaseInvoice::class,
                'attachable_id' => $purchase->id,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'file_data' => base64_encode(file_get_contents($file->getRealPath())),
            ]);
        }
    }
}

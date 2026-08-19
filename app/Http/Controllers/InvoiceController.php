<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Services\InvoiceManager;
use App\Services\UblGenerator;
use App\Services\VatCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceManager $manager) {}

    public function index(Request $request): Response
    {
        // Auto-refresh overdue status on listing (skip credit notes + incasso)
        Invoice::where('status', 'sent')
            ->where('is_credit', false)
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        $status = $request->input('status', 'all');
        $q = $request->input('q');

        $invoices = Invoice::with('customer')
            ->withCount('attachments')
            ->forStatus($status)
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('number', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('reference', 'like', "%{$q}%");
            }))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $invoices->getCollection()->transform(fn ($i) => [
            'id' => $i->id,
            'number' => $i->number,
            'customer_name' => $i->customer_name,
            'invoice_date' => $i->invoice_date->format('Y-m-d'),
            'invoice_date_label' => $i->invoice_date->translatedFormat('j M Y'),
            'due_date_label' => $i->due_date?->translatedFormat('j M Y'),
            'status' => $i->status,
            'is_credit' => (bool) $i->is_credit,
            'days_overdue' => $i->days_overdue,
            'viewed_label' => $i->first_viewed_at?->translatedFormat('j M Y, H:i'),
            'total' => (float) $i->total,
            'paid_total' => (float) $i->paid_total,
            'attachments_count' => $i->attachments_count,
        ]);

        // Counts per status for the filter chips (regular invoices only, credit notes separate)
        $counts = [
            'all' => Invoice::count(),
            'draft' => Invoice::regular()->where('status', 'draft')->count(),
            'sent' => Invoice::regular()->where('status', 'sent')->count(),
            'partial' => Invoice::regular()->where('status', 'partial')->count(),
            'overdue' => Invoice::regular()->where('status', 'overdue')->count(),
            'incasso' => Invoice::regular()->where('status', 'incasso')->count(),
            'paid' => Invoice::regular()->where('status', 'paid')->count(),
            'creditnota' => Invoice::credit()->count(),
        ];

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => ['status' => $status, 'q' => $q],
            'counts' => $counts,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Invoices/Form', [
            'invoice' => null,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address_line', 'postal_code', 'city', 'country', 'vat_number', 'kvk_number', 'email', 'payment_terms']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit', 'price', 'vat_rate']),
            'vat_rates' => VatCalculator::availableRates(),
            'preselect_customer_id' => $request->input('customer_id'),
            'price_mode' => auth()->user()->company?->price_mode ?? 'excl',
            'default_payment_terms' => (int) (auth()->user()->company?->default_payment_terms ?? 30),
            'brand_profiles' => \App\Models\BrandProfile::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $invoice = $this->manager->create($data);

        // Bijlagen en verrekeningen eerst opslaan: bij "direct versturen"
        // moeten ze op de PDF en in de factuurmail terechtkomen.
        $this->saveAttachments($request, $invoice);

        try {
            $this->syncAdvances($request, $invoice);
        } catch (\DomainException $e) {
            return redirect()->route('invoices.edit', $invoice)->withErrors(['advances' => $e->getMessage()]);
        }

        if ($request->input('action') === 'send') {
            $this->manager->send($invoice);
            return redirect()->route('invoices.show', $invoice)->with('flash', "Factuur {$invoice->number} verstuurd.");
        }

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Concept opgeslagen.');
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load('lines', 'payments', 'customer', 'attachments', 'creditNotes', 'reminderLogs', 'views');
        if ($invoice->is_credit) {
            $invoice->load('originalInvoice');
        }
        $company = auth()->user()->company;

        // Peppol: is deze klant bereikbaar (gecachte weekcheck) en is de
        // verzendkant geconfigureerd?
        $peppol = null;
        if (! $invoice->is_credit && $invoice->customer) {
            $peppolService = app(\App\Services\PeppolService::class);
            $available = null;
            try {
                $available = $peppolService->checkCustomer($invoice->customer);
            } catch (\Throwable $e) {
                // Directory onbereikbaar — geen blocker voor de pagina.
            }
            $peppol = [
                'participant_id' => $peppolService->participantId($invoice->customer),
                'available' => $available,
                'sending_enabled' => $peppolService->sendingEnabled(),
                'sent_at_label' => $invoice->peppol_sent_at?->translatedFormat('j M Y, H:i'),
                'reference' => $invoice->peppol_reference,
            ];
        }

        return Inertia::render('Invoices/Show', [
            'peppol' => $peppol,
            'invoice' => array_merge($invoice->toArray(), [
                'brand_profile_name' => $invoice->brandProfile?->name,
                'invoice_date_label' => $invoice->invoice_date->translatedFormat('j M Y'),
                'due_date_label' => $invoice->due_date?->translatedFormat('j M Y'),
                'sent_at_label' => $invoice->sent_at?->translatedFormat('j M Y, H:i'),
                'scheduled_send_on' => $invoice->scheduled_send_on?->format('Y-m-d'),
                'scheduled_send_on_label' => $invoice->scheduled_send_on?->translatedFormat('j F Y'),
                'first_viewed_at_label' => $invoice->first_viewed_at?->translatedFormat('j M Y, H:i'),
                'history' => $this->history($invoice),
                'portal_url' => $invoice->portalUrl(),
                'views' => $invoice->views->map(fn ($v) => [
                    'id' => $v->id,
                    'event' => $v->event,
                    'viewed_at_label' => $v->viewed_at?->translatedFormat('j M Y, H:i'),
                    'ip_address' => $v->ip_address,
                ]),
                'incasso_sent_at_label' => $invoice->incasso_sent_at?->translatedFormat('j M Y'),
                'days_overdue' => $invoice->days_overdue,
                'remaining' => $invoice->remaining_amount,
                'attachments' => $invoice->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'kind' => $a->kind,
                    'size_formatted' => $a->size_formatted,
                    'for_customer' => (bool) $a->for_customer,
                    'uploaded_at_label' => $a->created_at?->translatedFormat('j M Y'),
                ]),
                'reminder_logs' => $invoice->reminderLogs->map(fn ($r) => [
                    'id' => $r->id,
                    'type' => $r->type,
                    'kind' => $r->kind,
                    'sent_to' => $r->sent_to,
                    'amount_open' => (float) $r->amount_open,
                    'sent_at_label' => $r->sent_at?->translatedFormat('j M Y, H:i'),
                ]),
                'credit_notes' => $invoice->creditNotes->map(fn ($c) => [
                    'id' => $c->id,
                    'number' => $c->number,
                    'status' => $c->status,
                    'total' => (float) $c->total,
                    'invoice_date_label' => $c->invoice_date->translatedFormat('j M Y'),
                ]),
            ]),
            'company' => $company,
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        if ($invoice->status !== 'draft') {
            return Inertia::render('Invoices/Show', [
                'invoice' => $invoice->load('lines', 'payments'),
                'flash' => ['error' => 'Verstuurde facturen kunnen niet worden gewijzigd. Maak een creditnota aan.'],
            ]);
        }

        $invoice->load('lines');

        return Inertia::render('Invoices/Form', [
            'invoice' => $invoice,
            'advances' => $invoice->payments()->where('kind', 'advance')->orderBy('paid_on')->get()->map(fn ($p) => [
                'description' => $p->reference,
                'date' => $p->paid_on?->format('Y-m-d'),
                'amount' => (float) $p->amount,
            ])->values(),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address_line', 'postal_code', 'city', 'country', 'vat_number', 'kvk_number', 'email', 'payment_terms']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit', 'price', 'vat_rate']),
            'vat_rates' => VatCalculator::availableRates(),
            'price_mode' => auth()->user()->company?->price_mode ?? 'excl',
            'default_payment_terms' => (int) (auth()->user()->company?->default_payment_terms ?? 30),
            'brand_profiles' => \App\Models\BrandProfile::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Verstuurde facturen kunnen niet worden gewijzigd.']);
        }

        $data = $this->validated($request);
        $this->manager->update($invoice, $data);

        $this->saveAttachments($request, $invoice);

        try {
            $this->syncAdvances($request, $invoice->fresh());
        } catch (\DomainException $e) {
            return back()->withErrors(['advances' => $e->getMessage()]);
        }

        if ($request->input('action') === 'send') {
            $this->manager->send($invoice);
            return redirect()->route('invoices.show', $invoice)->with('flash', "Factuur {$invoice->number} verstuurd.");
        }

        return redirect()->route('invoices.show', $invoice)->with('flash', 'Concept bijgewerkt.');
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->manager->send($invoice);
        return back()->with('flash', "Factuur {$invoice->number} verstuurd.");
    }

    /** Maak een nieuw concept met dezelfde klant en regels. */
    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $invoice->load('lines');
        $terms = (int) ($invoice->payment_terms ?: (auth()->user()->company?->default_payment_terms ?? 30));

        $copy = $invoice->replicate([
            'number', 'portal_token', 'status', 'paid_total', 'paid_at',
            'sent_at', 'scheduled_send_on', 'first_viewed_at',
            'incasso_sent_at', 'incasso_reference', 'incasso_handler', 'incasso_phase',
            'is_credit', 'credits_invoice_id',
        ]);
        $copy->status = 'draft';
        $copy->is_credit = false;
        $copy->invoice_date = now();
        $copy->due_date = now()->addDays($terms);
        $copy->save();

        foreach ($invoice->lines as $line) {
            $copy->lines()->create($line->only([
                'product_id', 'sort_order', 'description', 'details', 'quantity',
                'unit', 'unit_price', 'vat_rate', 'line_subtotal', 'line_vat', 'line_total',
            ]));
        }

        return redirect()->route('invoices.edit', $copy)
            ->with('flash', "Kopie aangemaakt van factuur {$invoice->number}. Controleer en verstuur wanneer je klaar bent.");
    }

    /** Interne notitie — alleen zichtbaar in de app, nooit voor de klant. */
    public function updateInternalNotes(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $invoice->update(['internal_notes' => $data['internal_notes'] ?: null]);

        return back()->with('flash', 'Interne notitie opgeslagen.');
    }

    /** Plan een concept in om automatisch te versturen. */
    public function schedule(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['schedule' => 'Alleen concepten kunnen worden ingepland.']);
        }

        $data = $request->validate([
            'send_on' => ['required', 'date', 'after_or_equal:tomorrow'],
        ], [
            'send_on.after_or_equal' => 'Kies een datum vanaf morgen — vandaag versturen doe je met de knop "Versturen".',
        ]);

        $invoice->update(['scheduled_send_on' => $data['send_on']]);

        return back()->with('flash', 'Factuur ingepland voor ' . $invoice->fresh()->scheduled_send_on->translatedFormat('j F Y') . '.');
    }

    public function unschedule(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['scheduled_send_on' => null]);

        return back()->with('flash', 'Inplanning geannuleerd — de factuur blijft een concept.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return back()->withErrors(['delete' => 'Alleen concepten kunnen worden verwijderd.']);
        }
        $invoice->delete();
        return redirect()->route('invoices.index')->with('flash', 'Concept verwijderd.');
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        $invoice->load('lines');
        $company = $invoice->brandedCompany();

        $template = in_array($company->invoice_template, ['modern', 'classic', 'minimal'], true)
            ? $company->invoice_template
            : 'modern';

        $pdf = \App\Support\DocumentLocale::using($invoice->language, fn () => Pdf::loadView("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4'));

        $filename = ($invoice->number ?: "concept-{$invoice->id}") . '.pdf';
        return $pdf->stream($filename);
    }

    public function ubl(Invoice $invoice, UblGenerator $generator): HttpResponse|RedirectResponse
    {
        if ($invoice->status === 'draft') {
            return back()->withErrors(['ubl' => 'Verstuur de factuur eerst; concepten hebben nog geen factuurnummer.']);
        }

        $invoice->load('lines');
        $xml = $generator->generate($invoice);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$generator->filename($invoice).'"',
        ]);
    }

    public function remind(Invoice $invoice, \App\Services\ReminderService $reminders): RedirectResponse
    {
        try {
            $label = $reminders->sendManual($invoice);
        } catch (\DomainException $e) {
            return back()->withErrors(['reminder' => $e->getMessage()]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Handmatige herinnering mislukt', [
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['reminder' => 'Versturen is niet gelukt. Probeer het later opnieuw.']);
        }

        return back()->with('flash', "{$label} verstuurd naar {$invoice->customer_email}.");
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['nullable', 'in:payment,write_off,advance'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . ($invoice->remaining_amount + 0.01)],
            'paid_on' => ['required', 'date'],
            'method' => ['nullable', 'required_if:kind,payment', 'in:bank_transfer,ideal,cash,card,other'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Afboeking: wikkelt (een deel van) de factuur af zonder echt geld —
        // en zonder effect op omzet of BTW (die rekenen op de factuurregels).
        $kind = $data['kind'] ?? 'payment';

        Payment::create([
            'invoice_id' => $invoice->id,
            'kind' => $kind,
            'amount' => $data['amount'],
            'paid_on' => $data['paid_on'],
            'method' => $kind === 'write_off' ? 'other' : ($data['method'] ?? 'bank_transfer'),
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('flash', match ($kind) {
            'write_off' => 'Afboeking geregistreerd — je omzet en BTW blijven ongewijzigd.',
            'advance' => 'Verrekening geregistreerd — de PDF toont het bedrag als "reeds doorgestort"; omzet en BTW blijven ongewijzigd.',
            default => 'Betaling geregistreerd.',
        });
    }

    /**
     * Historie van de factuur: alle gebeurtenissen uit bestaande gegevens
     * samengesteld tot één tijdlijn (nieuwste eerst).
     */
    protected function history(Invoice $invoice): array
    {
        $events = collect();
        $push = function ($ts, string $icon, string $label) use ($events) {
            if ($ts) {
                $events->push(['ts' => $ts->toIso8601String(), 'label_ts' => $ts->translatedFormat('j M Y' . ($ts->format('H:i') !== '00:00' ? ', H:i' : '')), 'icon' => $icon, 'label' => $label]);
            }
        };

        $push($invoice->created_at, 'plus', 'Aangemaakt');
        $push($invoice->sent_at, 'send', 'Verstuurd' . ($invoice->customer_email ? " naar {$invoice->customer_email}" : ''));
        $push($invoice->peppol_sent_at, 'send', 'Afgeleverd via het Peppol-netwerk' . ($invoice->peppol_reference ? " (ref. {$invoice->peppol_reference})" : ''));
        $push($invoice->first_viewed_at, 'eye', 'Voor het eerst bekeken door de klant');

        foreach ($invoice->reminderLogs as $log) {
            $push($log->sent_at, $log->kind === 'warning' ? 'alert' : 'bell', "{$log->type} verstuurd naar {$log->sent_to}");
        }

        foreach ($invoice->payments as $payment) {
            $label = match ($payment->kind) {
                'write_off' => 'Afgeboekt: € ',
                'advance' => 'Verrekend / reeds doorgestort: € ',
                default => 'Betaling ontvangen: € ',
            };
            $push($payment->paid_on, $payment->kind === 'write_off' ? 'credit' : 'euro',
                $label . number_format((float) $payment->amount, 2, ',', '.'));
        }

        if ($invoice->status === 'paid') {
            $push($invoice->paid_at, 'check', 'Volledig betaald');
        }

        $push($invoice->incasso_sent_at, 'gavel', 'Overgedragen aan incasso' . ($invoice->incasso_handler ? " ({$invoice->incasso_handler})" : ''));

        foreach ($invoice->creditNotes as $credit) {
            $push($credit->created_at, 'credit', 'Creditnota ' . ($credit->number ?: '(concept)') . ' aangemaakt');
        }

        return $events->sortByDesc('ts')->values()->map(fn ($e) => [
            'icon' => $e['icon'],
            'label' => $e['label'],
            'ts_label' => $e['label_ts'],
        ])->all();
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            // De manager controleert dat het profiel van het eigen bedrijf is.
            'brand_profile_id' => ['nullable', 'integer', 'exists:brand_profiles,id'],
            'invoice_date' => ['required', 'date'],
            'payment_terms' => ['required', 'integer', 'min:0', 'max:365'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.details' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit' => ['nullable', 'string', 'max:30'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'in:0,9,21'],
            'action' => ['nullable', 'in:draft,send'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
            'files_for_customer' => ['nullable', 'array'],
            'files_for_customer.*' => ['in:0,1'],
            // Verrekeningen: al doorgestorte deelbetalingen die op de factuur
            // in mindering komen op het te betalen bedrag (niet op de BTW).
            'advances' => ['nullable', 'array', 'max:10'],
            'advances.*.description' => ['required', 'string', 'max:190'],
            'advances.*.date' => ['nullable', 'date'],
            'advances.*.amount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'files.*.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden zijn toegestaan.',
            'files.*.max' => 'Elk bestand mag maximaal 10 MB groot zijn.',
            'advances.*.description' => 'Geef elke verrekening een omschrijving (bijv. "Reeds doorgestort").',
            'advances.*.amount' => 'Vul bij elke verrekening een bedrag in.',
        ]);
    }

    /**
     * Verrekeningen ("reeds doorgestort") synchroniseren als advance-betalingen.
     * Ze verlagen het te betalen bedrag — nooit het factuurtotaal of de BTW.
     */
    protected function syncAdvances(Request $request, Invoice $invoice): void
    {
        if (! $request->exists('advances')) {
            return;
        }

        $rows = collect($request->input('advances', []));
        if ($rows->sum('amount') > (float) $invoice->total + 0.009) {
            throw new \DomainException('De verrekeningen zijn samen hoger dan het factuurtotaal.');
        }

        // Via de modellen verwijderen zodat paid_total netjes wordt herrekend.
        $invoice->payments()->where('kind', 'advance')->get()->each->delete();

        foreach ($rows as $row) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'kind' => 'advance',
                'amount' => $row['amount'],
                'paid_on' => $row['date'] ?? now()->toDateString(),
                'method' => 'bank_transfer',
                'reference' => $row['description'],
            ]);
        }
    }

    /** Bijlagen die bij het opstellen zijn toegevoegd (met per bestand: voor de klant of intern). */
    protected function saveAttachments(Request $request, Invoice $invoice): void
    {
        $flags = $request->input('files_for_customer', []);

        foreach ($request->file('files', []) as $i => $file) {
            \App\Models\Attachment::create([
                'attachable_type' => Invoice::class,
                'attachable_id' => $invoice->id,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'file_data' => base64_encode(file_get_contents($file->getRealPath())),
                'for_customer' => ($flags[$i] ?? '1') === '1',
            ]);
        }
    }
}

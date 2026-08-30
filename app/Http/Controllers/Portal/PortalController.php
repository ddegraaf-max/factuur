<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\InvoiceView;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Het klantenportaal zelf: overzicht van facturen en de factuurdetailpagina.
 *
 * Iedere inzage (pagina bekeken, PDF gedownload) wordt vastgelegd in het
 * inzagelog, zodat de ondernemer in de app kan zien of zijn factuur is bekeken.
 */
class PortalController extends Controller
{
    /** Overzicht van alle (verstuurde) facturen voor het geverifieerde adres. */
    public function index(Request $request): Response
    {
        $email = PortalAuthController::verifiedEmail($request);

        $invoices = Invoice::withoutGlobalScope('company')
            ->with('company:id,name,brand_color')
            ->whereRaw('LOWER(customer_email) = ?', [$email])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $open = $invoices->filter(fn ($i) => ! $i->is_credit && in_array($i->status, ['sent', 'partial', 'overdue', 'incasso']));

        // Offertes voor dit adres: alleen verstuurde (met portaallink), zodat
        // de klant ze hier kan bekijken en ondertekenen.
        $quotes = Quote::withoutGlobalScope('company')
            ->with('company:id,name,brand_color')
            ->whereRaw('LOWER(customer_email) = ?', [$email])
            ->where('status', '!=', 'draft')
            ->whereNotNull('portal_token')
            ->orderByDesc('quote_date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Portal/Index', [
            'email' => $email,
            'invoices' => $invoices->map(fn ($i) => $this->invoiceSummary($i))->values(),
            'quotes' => $quotes->map(fn ($q) => $this->quoteSummary($q))->values(),
            'stats' => [
                'open_count' => $open->count(),
                'open_amount' => round($open->sum(fn ($i) => (float) $i->total - (float) $i->paid_total), 2),
                'overdue_count' => $open->filter(fn ($i) => $i->is_overdue || $i->status === 'overdue')->count(),
                'quotes_open_count' => $quotes->filter(fn ($q) => $q->status === 'sent' && ! $q->is_expired)->count(),
            ],
        ]);
    }

    /** Offerte zoals de klant hem in het overzicht ziet. */
    protected function quoteSummary(Quote $quote): array
    {
        $expired = $quote->status === 'expired' || $quote->is_expired;

        return [
            'token' => $quote->portal_token,
            'number' => $quote->number,
            'status' => $expired ? 'expired' : $quote->status,
            'status_label' => match (true) {
                $expired => __('Verlopen'),
                $quote->status === 'accepted' => __('Geaccepteerd'),
                $quote->status === 'rejected' => __('Afgewezen'),
                default => __('Wacht op uw reactie'),
            },
            'awaiting' => ! $expired && $quote->status === 'sent',
            'company_name' => $quote->company?->name,
            'quote_date_label' => $quote->quote_date?->translatedFormat('j M Y'),
            'valid_until_label' => $quote->valid_until?->translatedFormat('j F Y'),
            'days_left' => $quote->status === 'sent' ? $quote->days_left : null,
            'accepted_at_label' => ($quote->signed_at ?? $quote->accepted_at)?->translatedFormat('j M Y'),
            'total' => (float) $quote->total,
        ];
    }

    /** Factuurdetail via de beveiligde link. Niet geverifieerd? Eerst de codestap. */
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invoice = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $invoice->customer_email) !== 0) {
            // Bezit van de link is stap 1; stap 2 is de code naar het
            // e-mailadres van de factuur. Zet de verificatie klaar.
            $request->session()->put('portal_pending_email', mb_strtolower($invoice->customer_email));
            $request->session()->put('portal_intended', route('portal.invoice', $token));
            $request->session()->put('portal_gate', [
                'type' => 'factuur',
                'company' => $invoice->company?->name,
                'number' => $invoice->number,
            ]);

            return redirect()->route('portal.verify.show');
        }

        $this->logView($request, $invoice, 'viewed');

        // Online betalingen: net terug van Mollie (of webhook nog onderweg)?
        // Check dan de actuele status, zodat de betaling direct zichtbaar is.
        $mollie = app(\App\Services\MolliePaymentService::class);
        $pendingOnline = \App\Models\OnlinePayment::where('invoice_id', $invoice->id)
            ->whereIn('status', ['open', 'pending'])
            ->where('created_at', '>=', now()->subDays(2))
            ->get();
        foreach ($pendingOnline as $op) {
            try {
                $mollie->sync($op);
            } catch (\Throwable $e) {
                // Mollie even onbereikbaar — de pagina laadt gewoon.
            }
        }

        $invoice->refresh();
        $invoice->load('lines', 'payments');
        // Het portaal toont de huisstijl van de handelsnaam waaronder de
        // factuur is verstuurd; KvK/BTW/IBAN blijven van de juridische entiteit.
        $company = $invoice->brandedCompany();

        // Alleen bijlagen die expliciet voor de klant zijn gemarkeerd.
        $customerAttachments = $invoice->attachments()
            ->withoutGlobalScope('company')
            ->where('for_customer', true)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'filename' => $a->filename,
                'kind' => $a->kind,
                'size_formatted' => $a->size_formatted,
            ])
            ->values();

        return Inertia::render('Portal/Show', [
            'invoice' => array_merge($this->invoiceSummary($invoice), [
                'reference' => $invoice->reference,
                'notes' => $invoice->notes,
                'footer' => $invoice->footer,
                'payment_terms' => $invoice->payment_terms,
                'customer_name' => $invoice->customer_name,
                'customer_address_line' => $invoice->customer_address_line,
                'customer_postal_code' => $invoice->customer_postal_code,
                'customer_city' => $invoice->customer_city,
                'vat_breakdown' => $invoice->vat_breakdown,
                'subtotal' => (float) $invoice->subtotal,
                'vat_total' => (float) $invoice->vat_total,
                'lines' => $invoice->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'description' => $l->description,
                    'details' => $l->details,
                    'quantity' => (float) $l->quantity,
                    'unit' => $l->unit,
                    'unit_price' => (float) $l->unit_price,
                    'vat_rate' => (float) $l->vat_rate,
                    'line_subtotal' => (float) $l->line_subtotal,
                ]),
                // Betalingen en verrekeningen ("reeds doorgestort") tonen —
                // interne afboekingen niet.
                'payments' => $invoice->payments->whereIn('kind', ['payment', 'advance'])->map(fn ($p) => [
                    'id' => $p->id,
                    'paid_on_label' => $p->paid_on?->translatedFormat('j M Y'),
                    'amount' => (float) $p->amount,
                    'label' => $p->kind === 'advance'
                        ? ($p->reference ?: __('Verrekend / reeds doorgestort'))
                        : __('Betaling ontvangen'),
                ])->values(),
                'attachments' => $customerAttachments,
            ]),
            // Betaallink (iDEAL / BLIK): alleen als de afzender Mollie heeft gekoppeld.
            'payment' => [
                'enabled' => $mollie->payable($invoice),
                'just_returned' => $request->boolean('betaald'),
                'label' => (string) \App\Support\Market::get('payment.online_label', 'iDEAL'),
            ],
            'company' => [
                'name' => $company?->name,
                'address_line' => $company?->address_line,
                'postal_code' => $company?->postal_code,
                'city' => $company?->city,
                'kvk_number' => $company?->kvk_number,
                'vat_number' => $company?->vat_number,
                'iban' => $company?->iban,
                'email' => $company?->email,
                'phone' => $company?->phone,
                'brand_color' => $company?->brand_color,
                'logo_data' => $company?->logo_data,
            ],
        ]);
    }

    /** Start een online betaling (iDEAL via Mollie) en stuur door naar de checkout. */
    public function pay(Request $request, string $token): HttpResponse|RedirectResponse
    {
        $invoice = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $invoice->customer_email) !== 0) {
            return redirect()->route('portal.invoice', $token);
        }

        try {
            $checkoutUrl = app(\App\Services\MolliePaymentService::class)->checkoutUrl($invoice);
        } catch (\DomainException $e) {
            return redirect()->route('portal.invoice', $token)->with('error', $e->getMessage());
        }

        $this->logView($request, $invoice, 'payment_started');

        // Externe redirect vanuit een Inertia-POST: 409 + X-Inertia-Location.
        return Inertia::location($checkoutUrl);
    }

    /** PDF downloaden vanuit het portaal (en die download loggen). */
    public function pdf(Request $request, string $token): HttpResponse|RedirectResponse
    {
        $invoice = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $invoice->customer_email) !== 0) {
            return redirect()->route('portal.invoice', $token);
        }

        $this->logView($request, $invoice, 'pdf');

        $invoice->load('lines');
        $company = $invoice->brandedCompany();

        $template = $company->resolvedInvoiceTemplate();

        $pdf = \App\Support\DocumentLocale::using($invoice->language, fn () => Pdf::loadView("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4'));

        return $pdf->download(($invoice->number ?: 'factuur') . '.pdf');
    }

    /** Bijlage downloaden vanuit het portaal — alleen voor-de-klant-bestanden. */
    public function attachment(Request $request, string $token, string $attachmentId): HttpResponse|RedirectResponse
    {
        abort_unless(ctype_digit($attachmentId), 404);
        $invoice = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $invoice->customer_email) !== 0) {
            return redirect()->route('portal.invoice', $token);
        }

        $file = $invoice->attachments()
            ->withoutGlobalScope('company')
            ->where('for_customer', true)
            ->where('id', $attachmentId)
            ->first();
        abort_if(! $file, 404);

        $contents = $file->contents();
        abort_if($contents === null, 404);

        $this->logView($request, $invoice, 'attachment');

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_ATTACHMENT,
                $file->filename,
                'bijlage'
            ),
            'Content-Length' => (string) strlen($contents),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /* ===================== Helpers ===================== */

    protected function findByToken(string $token): Invoice
    {
        abort_unless(strlen($token) === 64 && ctype_xdigit($token), 404);

        $invoice = Invoice::withoutGlobalScope('company')
            ->with('company')
            ->where('portal_token', $token)
            ->where('status', '!=', 'draft')
            ->first();

        abort_if(! $invoice || ! $invoice->customer_email, 404);

        return $invoice;
    }

    /**
     * Legt de inzage vast. Paginaweergaven binnen 30 minuten in dezelfde sessie
     * tellen als één keer kijken (anders vervuilt elke refresh het log);
     * PDF-downloads worden altijd gelogd.
     */
    protected function logView(Request $request, Invoice $invoice, string $event): void
    {
        if ($event === 'viewed') {
            $key = "portal_seen_{$invoice->id}";
            $lastSeen = (int) $request->session()->get($key, 0);
            if (now()->timestamp - $lastSeen < 1800) {
                return;
            }
            $request->session()->put($key, now()->timestamp);
        }

        InvoiceView::create([
            'invoice_id' => $invoice->id,
            'event' => $event,
            'email' => PortalAuthController::verifiedEmail($request),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'viewed_at' => now(),
        ]);

        if (! $invoice->first_viewed_at) {
            $invoice->forceFill(['first_viewed_at' => now()])->saveQuietly();
        }
    }

    /** Compacte weergave van een factuur voor het portaal. */
    protected function invoiceSummary(Invoice $invoice): array
    {
        return [
            'token' => $invoice->portal_token,
            'number' => $invoice->number,
            'status' => $invoice->status,
            'is_credit' => (bool) $invoice->is_credit,
            'days_overdue' => $invoice->days_overdue,
            'company_name' => $invoice->company?->name,
            'brand_color' => $invoice->company?->brand_color,
            'invoice_date_label' => $invoice->invoice_date?->translatedFormat('j M Y'),
            'due_date_label' => $invoice->due_date?->translatedFormat('j F Y'),
            'total' => (float) $invoice->total,
            'paid_total' => (float) $invoice->paid_total,
            'remaining' => round((float) $invoice->total - (float) $invoice->paid_total, 2),
        ];
    }
}

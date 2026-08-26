<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceManager
{
    public function __construct(
        protected VatCalculator $vat,
        protected InvoiceNumberGenerator $numbers,
    ) {}

    /**
     * Create a new draft invoice with line items.
     *
     * @param  array  $data  ['customer_id','invoice_date','payment_terms','reference','notes','lines'=>[]]
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $invoiceDate = isset($data['invoice_date'])
                ? Carbon::parse($data['invoice_date'])
                : now();
            $paymentTerms = (int) ($data['payment_terms'] ?? $customer->payment_terms ?? $customer->company->default_payment_terms ?? 30);

            // Handelsnaam: alleen een profiel van hetzelfde bedrijf telt
            // (zonder global scope, want dit draait ook via de console).
            $profile = ! empty($data['brand_profile_id'])
                ? \App\Models\BrandProfile::withoutGlobalScope('company')
                    ->where('company_id', $customer->company_id)
                    ->find($data['brand_profile_id'])
                : null;

            $lines = $data['lines'] ?? [];
            $mode = $this->resolveMode($data, $customer->company);
            $totals = $this->vat->calculateInvoice($lines, $mode);

            // Documenttaal: momentopname van de klantinstelling (of expliciet
            // meegegeven, bijv. bij het omzetten van een offerte).
            $language = $data['language'] ?? $customer->language ?? 'nl';
            $language = in_array($language, \App\Support\DocumentLocale::SUPPORTED, true) ? $language : 'nl';

            $invoice = Invoice::create([
                // Expliciet meegeven: bij het genereren via de console (terugkerende
                // facturen) is er geen ingelogde gebruiker die dit automatisch invult.
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'brand_profile_id' => $profile?->id,
                'language' => $language,
                'status' => 'draft',
                'reference' => $data['reference'] ?? null,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays($paymentTerms),
                'payment_terms' => $paymentTerms,

                // Snapshot
                'customer_name' => $customer->name,
                'customer_address_line' => $customer->address_line,
                'customer_postal_code' => $customer->postal_code,
                'customer_city' => $customer->city,
                'customer_country' => $customer->country,
                'customer_vat_number' => $customer->vat_number,
                'customer_kvk_number' => $customer->kvk_number,
                'customer_email' => $customer->email,

                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'paid_total' => 0,
                'vat_breakdown' => $totals['vat_breakdown'],

                'notes' => $data['notes'] ?? null,
                'footer' => ($profile && filled($profile->invoice_footer))
                    ? $profile->invoice_footer
                    : $customer->company->invoice_footer,
            ]);

            $this->syncLines($invoice, $lines, $mode);

            return $invoice->fresh('lines');
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \DomainException('Alleen concept-facturen kunnen worden gewijzigd.');
        }

        return DB::transaction(function () use ($invoice, $data) {
            $lines = $data['lines'] ?? [];
            $mode = $this->resolveMode($data, $invoice->company);
            $totals = $this->vat->calculateInvoice($lines, $mode);

            $invoiceDate = isset($data['invoice_date'])
                ? Carbon::parse($data['invoice_date'])
                : $invoice->invoice_date;
            $paymentTerms = (int) ($data['payment_terms'] ?? $invoice->payment_terms);

            // Handelsnaam wijzigen mag zolang het een concept is; de voetnoot
            // schuift mee naar die van het nieuwe profiel (of het bedrijf).
            $brandChanges = [];
            if (array_key_exists('brand_profile_id', $data)) {
                $profile = ! empty($data['brand_profile_id'])
                    ? \App\Models\BrandProfile::withoutGlobalScope('company')
                        ->where('company_id', $invoice->company_id)
                        ->find($data['brand_profile_id'])
                    : null;
                $brandChanges = [
                    'brand_profile_id' => $profile?->id,
                    'footer' => ($profile && filled($profile->invoice_footer))
                        ? $profile->invoice_footer
                        : $invoice->company->invoice_footer,
                ];
            }

            // Klant: een concept volgt de actuele klantgegevens tot het wordt
            // verstuurd — pas dan is de momentopname definitief. Zo werkt ook
            // een andere klant kiezen (bijv. na dupliceren) gewoon. Alleen een
            // klant van hetzelfde bedrijf telt.
            $customerChanges = [];
            if (! empty($data['customer_id'])) {
                $customer = Customer::withoutGlobalScope('company')
                    ->where('company_id', $invoice->company_id)
                    ->find($data['customer_id']);
                if ($customer) {
                    $customerChanges = [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_address_line' => $customer->address_line,
                        'customer_postal_code' => $customer->postal_code,
                        'customer_city' => $customer->city,
                        'customer_country' => $customer->country,
                        'customer_vat_number' => $customer->vat_number,
                        'customer_kvk_number' => $customer->kvk_number,
                        'customer_email' => $customer->email,
                    ];
                    // Andere klant? Dan ook de documenttaal van die klant.
                    if ((int) $customer->id !== (int) $invoice->customer_id) {
                        $language = $customer->language ?? 'nl';
                        $customerChanges['language'] = in_array($language, \App\Support\DocumentLocale::SUPPORTED, true) ? $language : 'nl';
                    }
                }
            }

            // Leeggemaakte velden komen als null binnen (lege strings worden
            // door Laravel naar null omgezet). "Sleutel aanwezig" is dus het
            // criterium om te wijzigen — niet "waarde niet null", anders is
            // een opmerking of referentie nooit meer leeg te maken.
            $invoice->update($brandChanges + $customerChanges + [
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $invoice->reference,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays($paymentTerms),
                'payment_terms' => $paymentTerms,
                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'vat_breakdown' => $totals['vat_breakdown'],
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $invoice->notes,
            ]);

            $invoice->lines()->delete();
            $this->syncLines($invoice, $lines, $mode);

            return $invoice->fresh('lines');
        });
    }

    /**
     * Send an invoice — assigns final number, locks the snapshot, marks as sent.
     */
    public function send(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \DomainException('Alleen concepten kunnen worden verstuurd.');
        }

        $invoice = DB::transaction(function () use ($invoice) {
            if (! $invoice->number) {
                $invoice->number = $this->numbers->generate(
                    $invoice->company,
                    $invoice->invoice_date->year
                );
            }
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            // Geheime link voor het klantenportaal ("Bekijk factuur online").
            if (! $invoice->portal_token) {
                $invoice->portal_token = bin2hex(random_bytes(32));
            }
            // Vooraf verrekende bedragen (reeds doorgestort) meenemen in de
            // status: deels of zelfs volledig voldaan bij versturen.
            $invoice->refreshStatus();
            $invoice->save();

            return $invoice;
        });

        $this->emailInvoice($invoice);

        return $invoice;
    }

    /**
     * Mail de factuur (met PDF) naar de klant.
     *  - TO  : de klant
     *  - CC  : jouw eigen kopie-adres (of je bedrijfs-e-mail)
     *  - BCC : je boekhoudkantoor (indien ingesteld)
     * Faalt de mail, dan blijft de factuur gewoon 'verstuurd' en loggen we de fout.
     */
    protected function emailInvoice(Invoice $invoice): void
    {
        try {
            if (! $invoice->customer_email) {
                return;
            }

            // PDF én mail in de taal van het document (nl of en).
            \App\Support\DocumentLocale::using($invoice->language, function () use ($invoice) {
                $this->renderAndMail($invoice);
            });
        } catch (\Throwable $e) {
            Log::error('Factuur mailen mislukt', [
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Bouwt de PDF (+ UBL) en verstuurt de factuurmail — binnen de documenttaal. */
    protected function renderAndMail(Invoice $invoice): void
    {
        // Huisstijl van de gekozen handelsnaam (of gewoon het bedrijf).
        $company = $invoice->brandedCompany();
        $invoice->load('lines');

        $template = $company->resolvedInvoiceTemplate();

        $pdf = Pdf::loadView("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $company,
        ])->setPaper('a4')->output();

        // E-facturatie: UBL-bijlage genereren. Mislukt dit, dan gaat de
        // factuurmail gewoon (alleen met PDF) de deur uit.
        $ubl = null;
        try {
            $ubl = app(UblGenerator::class)->generate($invoice);
        } catch (\Throwable $e) {
            Log::warning('UBL-bijlage genereren mislukt', [
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        $mail = Mail::to($invoice->customer_email);

        $cc = $company->copy_email ?: $company->email;
        if ($cc && strcasecmp($cc, $invoice->customer_email) !== 0) {
            $mail->cc($cc);
        }
        if (! empty($company->accountant_email)) {
            $mail->bcc($company->accountant_email);
        }

        $mail->send(new InvoiceMail($invoice, $pdf, $ubl));
    }

    /** 'incl' wanneer de ondernemer zijn prijzen inclusief btw invoert. */
    protected function priceMode(?\App\Models\Company $company): string
    {
        return ($company?->price_mode === 'incl') ? 'incl' : 'excl';
    }

    /**
     * De schakelaar op het formulier wint van de bedrijfsinstelling — zo kun
     * je per factuur kiezen hoe je de prijzen intypt.
     */
    protected function resolveMode(array $data, ?\App\Models\Company $company): string
    {
        return in_array($data['price_mode'] ?? null, ['excl', 'incl'], true)
            ? $data['price_mode']
            : $this->priceMode($company);
    }

    protected function syncLines(Invoice $invoice, array $lines, string $mode = 'excl'): void
    {
        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $discount = min(100, max(0, (float) ($line['discount_pct'] ?? 0)));
            $calc = $this->vat->calculateLine($qty, $price, $rate, $mode, $discount);

            // In de database staat de stuksprijs altijd exclusief btw.
            $storedPrice = $mode === 'incl'
                ? $this->vat->netUnitPrice($price, $rate)
                : $price;

            $invoice->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'sort_order' => $index,
                'description' => $line['description'] ?? '',
                'details' => $line['details'] ?? null,
                'quantity' => $qty,
                'unit' => $line['unit'] ?? 'stuk',
                'unit_price' => $storedPrice,
                'vat_rate' => $rate,
                'discount_pct' => $discount > 0 ? $discount : null,
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
        }
    }
}

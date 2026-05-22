<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

            $lines = $data['lines'] ?? [];
            $totals = $this->vat->calculateInvoice($lines);

            $invoice = Invoice::create([
                'customer_id' => $customer->id,
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
                'footer' => $customer->company->invoice_footer,
            ]);

            $this->syncLines($invoice, $lines);

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
            $totals = $this->vat->calculateInvoice($lines);

            $invoiceDate = isset($data['invoice_date'])
                ? Carbon::parse($data['invoice_date'])
                : $invoice->invoice_date;
            $paymentTerms = (int) ($data['payment_terms'] ?? $invoice->payment_terms);

            $invoice->update([
                'reference' => $data['reference'] ?? $invoice->reference,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays($paymentTerms),
                'payment_terms' => $paymentTerms,
                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'vat_breakdown' => $totals['vat_breakdown'],
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            $invoice->lines()->delete();
            $this->syncLines($invoice, $lines);

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

        return DB::transaction(function () use ($invoice) {
            if (! $invoice->number) {
                $invoice->number = $this->numbers->generate(
                    $invoice->company,
                    $invoice->invoice_date->year
                );
            }
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->save();

            return $invoice;
        });
    }

    protected function syncLines(Invoice $invoice, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $calc = $this->vat->calculateLine($qty, $price, $rate);

            $invoice->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'sort_order' => $index,
                'description' => $line['description'] ?? '',
                'details' => $line['details'] ?? null,
                'quantity' => $qty,
                'unit' => $line['unit'] ?? 'stuk',
                'unit_price' => $price,
                'vat_rate' => $rate,
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
        }
    }
}

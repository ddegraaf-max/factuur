<?php

namespace App\Services;

use App\Mail\QuoteMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteManager
{
    public function __construct(
        protected VatCalculator $vat,
        protected InvoiceManager $invoices,
    ) {}

    public function create(array $data): Quote
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $company = $customer->company;
            $mode = $this->resolveMode($data, $company);

            $quoteDate = isset($data['quote_date']) ? Carbon::parse($data['quote_date']) : now();
            $validDays = (int) ($data['valid_days'] ?? $company->quote_valid_days ?? 30);

            // Handelsnaam: alleen een profiel van hetzelfde bedrijf telt.
            $profile = ! empty($data['brand_profile_id'])
                ? \App\Models\BrandProfile::withoutGlobalScope('company')
                    ->where('company_id', $company->id)
                    ->find($data['brand_profile_id'])
                : null;

            $lines = $data['lines'] ?? [];
            $totals = $this->vat->calculateInvoice($lines, $mode);

            // Documenttaal: momentopname van de klantinstelling.
            $language = $customer->language ?? 'nl';
            $language = in_array($language, \App\Support\DocumentLocale::SUPPORTED, true) ? $language : 'nl';

            $quote = Quote::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'brand_profile_id' => $profile?->id,
                'language' => $language,
                'status' => 'draft',
                'reference' => $data['reference'] ?? null,
                'quote_date' => $quoteDate,
                'valid_until' => $quoteDate->copy()->addDays($validDays),

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
                'vat_breakdown' => $totals['vat_breakdown'],

                'intro' => $data['intro'] ?? null,
                'notes' => $data['notes'] ?? null,
                'footer' => ($profile && filled($profile->invoice_footer))
                    ? $profile->invoice_footer
                    : $company->invoice_footer,
            ]);

            $this->syncLines($quote, $lines, $mode);

            return $quote->fresh('lines');
        });
    }

    public function update(Quote $quote, array $data): Quote
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            throw new \DomainException('Een geaccepteerde of afgewezen offerte kun je niet meer wijzigen.');
        }

        return DB::transaction(function () use ($quote, $data) {
            $mode = $this->resolveMode($data, $quote->company);
            $lines = $data['lines'] ?? [];
            $totals = $this->vat->calculateInvoice($lines, $mode);

            $quoteDate = isset($data['quote_date']) ? Carbon::parse($data['quote_date']) : $quote->quote_date;
            $validDays = (int) ($data['valid_days'] ?? $quote->quote_date->diffInDays($quote->valid_until));

            // Handelsnaam wijzigen; de voetnoot schuift mee naar die van het
            // nieuwe profiel (of terug naar de standaard van het bedrijf).
            $brandChanges = [];
            if (array_key_exists('brand_profile_id', $data)) {
                $profile = ! empty($data['brand_profile_id'])
                    ? \App\Models\BrandProfile::withoutGlobalScope('company')
                        ->where('company_id', $quote->company_id)
                        ->find($data['brand_profile_id'])
                    : null;
                $brandChanges = [
                    'brand_profile_id' => $profile?->id,
                    'footer' => ($profile && filled($profile->invoice_footer))
                        ? $profile->invoice_footer
                        : $quote->company->invoice_footer,
                ];
            }

            // Klant: de offerte volgt de actuele klantgegevens zolang hij nog
            // gewijzigd mag worden; een andere klant kiezen werkt zo ook.
            $customerChanges = [];
            if (! empty($data['customer_id'])) {
                $customer = Customer::withoutGlobalScope('company')
                    ->where('company_id', $quote->company_id)
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
                    if ((int) $customer->id !== (int) $quote->customer_id) {
                        $language = $customer->language ?? 'nl';
                        $customerChanges['language'] = in_array($language, \App\Support\DocumentLocale::SUPPORTED, true) ? $language : 'nl';
                    }
                }
            }

            $quote->update($brandChanges + $customerChanges + [
                // Leeggemaakt veld = null; sleutel aanwezig is het criterium (zie InvoiceManager).
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $quote->reference,
                'quote_date' => $quoteDate,
                'valid_until' => $quoteDate->copy()->addDays($validDays),
                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'vat_breakdown' => $totals['vat_breakdown'],
                'intro' => array_key_exists('intro', $data) ? $data['intro'] : $quote->intro,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quote->notes,
            ]);

            $quote->lines()->delete();
            $this->syncLines($quote, $lines, $mode);

            return $quote->fresh('lines');
        });
    }

    /** Ken een definitief nummer toe, markeer als verstuurd en mail de offerte. */
    public function send(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            throw new \DomainException('Deze offerte kan niet (opnieuw) worden verstuurd.');
        }

        $quote = DB::transaction(function () use ($quote) {
            if (! $quote->number) {
                $quote->number = $this->nextNumber($quote->company, $quote->quote_date->year);
            }
            $quote->status = 'sent';
            $quote->sent_at = now();
            // Geheime link voor het portaal: bekijken én digitaal ondertekenen.
            if (! $quote->portal_token) {
                $quote->portal_token = bin2hex(random_bytes(32));
            }
            $quote->save();

            return $quote;
        });

        $this->email($quote);

        return $quote;
    }

    public function accept(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'expired'], true)) {
            throw new \DomainException('Alleen een verstuurde offerte kan worden geaccepteerd.');
        }

        $quote->update(['status' => 'accepted', 'accepted_at' => now(), 'rejected_at' => null]);
        \App\Support\Audit::log('accepted', $quote, \App\Support\Audit::label($quote) . ' geaccepteerd' . (auth()->check() ? '' : ' door de klant via het portaal'), [], $quote->company_id);

        return $quote->fresh();
    }

    /** Waarom er (nu) geen bevestiging kan — of null als het kan. */
    public function acceptConfirmationBlocker(Quote $quote, bool $force = false): ?string
    {
        if ($quote->status !== 'accepted') {
            return 'De offerte is (nog) niet geaccepteerd.';
        }
        if (! $quote->customer_email) {
            return 'Deze klant heeft geen e-mailadres. Vul het aan bij de klantgegevens.';
        }
        if ($quote->accept_mail_sent_at && ! $force) {
            return 'Er is al een bevestiging verstuurd op '.$quote->accept_mail_sent_at->translatedFormat('j F Y').'.';
        }

        return null;
    }

    /**
     * Bevestiging van het akkoord naar de klant, met de (ondertekende)
     * offerte als PDF. Met $force ook als er al eerder een is verstuurd.
     *
     * @throws \DomainException  met een uitlegbare reden wanneer het niet kan
     */
    public function sendAcceptConfirmation(Quote $quote, bool $force = false): bool
    {
        if ($reason = $this->acceptConfirmationBlocker($quote, $force)) {
            throw new \DomainException($reason);
        }

        $company = $quote->company;
        $branded = $quote->brandedCompany();
        $quote->load('lines', 'installments');
        $quote->ensurePortalToken();

        \App\Support\DocumentLocale::using($quote->language, function () use ($quote, $company, $branded) {
            // Dezelfde PDF als in het portaal — mét handtekeningblok als er is getekend.
            $pdf = Pdf::loadView('pdf.quote', [
                'quote' => $quote,
                'company' => $branded,
            ])->setPaper('a4')->output();

            // Uit een demo vertrekt nooit echte post (ook niet via het portaal).
            Mail::mailer($company->is_demo ? 'log' : null)
                ->to($quote->customer_email)
                ->send(new \App\Mail\QuoteAcceptedMail($quote, $pdf));
        });

        $quote->forceFill([
            'accept_mail_sent_at' => now(),
            'accept_mail_sent_to' => $quote->customer_email,
        ])->saveQuietly();

        return true;
    }

    public function reject(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'expired'], true)) {
            throw new \DomainException('Alleen een verstuurde offerte kan worden afgewezen.');
        }

        $quote->update(['status' => 'rejected', 'rejected_at' => now(), 'accepted_at' => null]);
        \App\Support\Audit::log('rejected', $quote, \App\Support\Audit::label($quote) . ' afgewezen' . (auth()->check() ? '' : ' door de klant via het portaal'), [], $quote->company_id);

        return $quote->fresh();
    }

    /**
     * Zet de offerte om in een concept-factuur met dezelfde regels.
     * De offerte blijft bestaan als vastlegging van de afspraak.
     */
    public function convertToInvoice(Quote $quote): Invoice
    {
        if ($quote->converted_invoice_id) {
            throw new \DomainException('Van deze offerte is al een factuur gemaakt.');
        }
        if ($quote->status === 'rejected') {
            throw new \DomainException('Een afgewezen offerte kun je niet omzetten.');
        }
        if ($quote->installments()->exists()) {
            throw new \DomainException('Deze offerte wordt in termijnen gefactureerd — gebruik het termijnplan (of verwijder dat eerst).');
        }

        $quote->loadMissing('lines');
        $mode = $this->priceMode($quote->company);

        return DB::transaction(function () use ($quote, $mode) {
            // De regels staan netto opgeslagen; InvoiceManager rekent in de
            // ingestelde modus, dus geven we de prijzen in diezelfde vorm door.
            $lines = $quote->lines->map(fn ($l) => [
                'product_id' => $l->product_id,
                'description' => $l->description,
                'details' => $l->details,
                'quantity' => (float) $l->quantity,
                'unit' => $l->unit,
                'unit_price' => $mode === 'incl'
                    ? $this->grossUnitPrice($l)
                    : (float) $l->unit_price,
                'vat_rate' => (float) $l->vat_rate,
                // De korting verhuist mee; grossUnitPrice geeft de prijs
                // vóór korting terug, dus er wordt niets dubbel gekort.
                'discount_pct' => (float) ($l->discount_pct ?? 0),
            ])->values()->all();

            $invoice = $this->invoices->create([
                'customer_id' => $quote->customer_id,
                // De factuur gaat onder dezelfde handelsnaam én taal de deur uit.
                'brand_profile_id' => $quote->brand_profile_id,
                'language' => $quote->language,
                'invoice_date' => now()->toDateString(),
                'reference' => $quote->reference ?: ('Offerte '.$quote->number),
                'notes' => $quote->notes,
                'lines' => $lines,
            ]);

            // Omzetten betekent dat de klant akkoord is; leg dat ook zo vast.
            $quote->update([
                'converted_invoice_id' => $invoice->id,
                'status' => 'accepted',
                'accepted_at' => $quote->accepted_at ?? now(),
            ]);

            return $invoice;
        });
    }

    /** Zet verstuurde offertes waarvan de geldigheid voorbij is op 'verlopen'. */
    public function markExpired(): int
    {
        return Quote::withoutGlobalScope('company')
            ->where('status', 'sent')
            ->whereDate('valid_until', '<', today())
            ->update(['status' => 'expired']);
    }

    public function nextNumber(Company $company, ?int $year = null): string
    {
        $year ??= (int) date('Y');

        return DB::transaction(function () use ($company, $year) {
            $row = DB::table('quote_sequences')
                ->where('company_id', $company->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $next = 1;
                DB::table('quote_sequences')->insert([
                    'company_id' => $company->id,
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $next = $row->last_number + 1;
                DB::table('quote_sequences')
                    ->where('id', $row->id)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            }

            $template = $company->quote_number_format ?: 'OFF-{year}-{sequence:4}';

            return preg_replace_callback(
                '/\{(year|sequence)(?::(\d+))?\}/',
                function ($m) use ($year, $next) {
                    $value = $m[1] === 'year' ? $year : $next;
                    $padding = isset($m[2]) ? (int) $m[2] : 0;

                    return str_pad((string) $value, $padding, '0', STR_PAD_LEFT);
                },
                $template
            );
        });
    }

    protected function email(Quote $quote): void
    {
        try {
            if (! $quote->customer_email) {
                return;
            }

            // PDF én mail in de taal van het document (nl of en).
            \App\Support\DocumentLocale::using($quote->language, fn () => $this->renderAndMail($quote));
        } catch (\Throwable $e) {
            Log::error('Offerte mailen mislukt', [
                'quote' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Bouwt de offerte-PDF en verstuurt de mail — binnen de documenttaal. */
    protected function renderAndMail(Quote $quote): void
    {
        // Huisstijl van de gekozen handelsnaam (of gewoon het bedrijf).
        $company = $quote->brandedCompany();
        $quote->load('lines');

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
        ])->setPaper('a4')->output();

        $mail = Mail::to($quote->customer_email);

        $cc = $company->copy_email ?: $company->email;
        if ($cc && strcasecmp($cc, $quote->customer_email) !== 0) {
            $mail->cc($cc);
        }

        $mail->send(new QuoteMail($quote, $pdf));
    }

    /**
     * Brutostuksprijs afgeleid uit het regeltotaal — precies wat er is
     * ingetypt. Het regeltotaal bevat al de regelkorting; die delen we er
     * weer uit, anders zou de korting dubbel worden toegepast.
     */
    protected function grossUnitPrice($line): float
    {
        $qty = (float) $line->quantity;
        $factor = 1 - min(100, max(0, (float) ($line->discount_pct ?? 0))) / 100;

        return ($qty > 0 && $factor > 0)
            ? round((float) $line->line_total / $qty / $factor, 2)
            : round((float) $line->unit_price * (1 + (float) $line->vat_rate / 100), 2);
    }

    protected function priceMode(?Company $company): string
    {
        return ($company?->price_mode === 'incl') ? 'incl' : 'excl';
    }

    /**
     * De schakelaar op het formulier wint van de bedrijfsinstelling — zo kun
     * je per offerte kiezen hoe je de prijzen intypt.
     */
    protected function resolveMode(array $data, ?Company $company): string
    {
        return in_array($data['price_mode'] ?? null, ['excl', 'incl'], true)
            ? $data['price_mode']
            : $this->priceMode($company);
    }

    protected function syncLines(Quote $quote, array $lines, string $mode = 'excl'): void
    {
        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $discount = min(100, max(0, (float) ($line['discount_pct'] ?? 0)));
            $calc = $this->vat->calculateLine($qty, $price, $rate, $mode, $discount);

            $quote->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'sort_order' => $index,
                'description' => $line['description'] ?? '',
                'details' => $line['details'] ?? null,
                'quantity' => $qty,
                'unit' => $line['unit'] ?? 'stuk',
                'unit_price' => $mode === 'incl' ? $this->vat->netUnitPrice($price, $rate) : $price,
                'vat_rate' => $rate,
                'discount_pct' => $discount > 0 ? $discount : null,
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
        }
    }
}

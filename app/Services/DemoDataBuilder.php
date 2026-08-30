<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Models\RecurringInvoice;
use App\Models\ReminderLog;
use App\Models\TimeEntry;
use App\Models\Trip;
use App\Models\User;
use App\Support\Brand;
use App\Support\Market;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoDataBuilder
 *
 * Bouwt een complete, realistische demo-omgeving: een eigen sandbox-bedrijf met
 * klanten, producten, facturen in alle statussen, betalingen, een creditnota,
 * een incassodossier en terugkerende profielen.
 *
 * De voorbeelddata volgt de markt (App\Support\Market): in Nederland is dat
 * Jansen Webdesign (EUR, btw 21%, KvK), onder Lopra Polska is het Studio
 * Wnętrz Kowalska (PLN, btw 23/8%, NIP, FV-nummers, windykacja — plus uren,
 * ritten en inkoopfacturen). De opbouw — dezelfde statussen, dezelfde stappen
 * in dezelfde volgorde — is voor beide markten gelijk; alleen de gegevens
 * komen uit dataset().
 *
 * Elke bezoeker krijgt zijn eigen bedrijf, zodat je vrij kunt klikken zonder
 * dat je de demo van iemand anders verstoort. Na afloop ruimt
 * `demo:cleanup` alles weer op.
 */
class DemoDataBuilder
{
    /** Hoe lang een demo-omgeving blijft bestaan. */
    public const LIFETIME_HOURS = 24;

    /** Voorbeelddata van de actieve markt; gevuld in build() uit dataset(). */
    protected array $d = [];

    public function __construct(protected VatCalculator $vat) {}

    /**
     * Maak een volledige demo-omgeving en geef de bijbehorende (ingelogde) gebruiker terug.
     */
    public function build(): User
    {
        $this->d = $this->dataset();

        return DB::transaction(function () {
            $company = $this->createCompany();
            $user = $this->createUser($company);

            $customers = $this->createCustomers($company);
            $this->createProducts($company);
            $invoices = $this->createInvoices($company, $customers);
            $this->createRecurring($company, $customers, $invoices);
            $this->createQuotes($company, $customers);
            $this->createVatFiling($company);
            $this->createHoursAndTrips($company, $user, $customers);
            $this->createPurchases($company);

            return $user;
        });
    }

    protected function createCompany(): Company
    {
        return Company::create(array_merge($this->d['company'], [
            'is_demo' => true,
            'demo_expires_at' => now()->addHours(self::LIFETIME_HOURS),
            // Willekeurige (maar realistisch ogende) nummers: het KvK-/REGON- en
            // btw-/NIP-nummer zijn uniek in de database, en elke demo is een eigen bedrijf.
            'kvk_number' => $this->uniqueRegistry(),
            'vat_number' => $this->uniqueVat(),
            // Ruime proefperiode zodat de demo nooit tegen het abonnementsslot loopt.
            'trial_ends_at' => now()->addDays(14),
        ]));
    }

    protected function createUser(Company $company): User
    {
        $user = User::create([
            'name' => $this->d['user_name'],
            // Uniek adres per demo; er wordt toch nooit echt naartoe gemaild.
            'email' => 'demo+'.Str::lower(Str::random(10)).'@'.Brand::domain(),
            'password' => Hash::make(Str::random(40)),
            'company_id' => $company->id,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->companies()->attach($company->id, ['role' => 'owner']);

        return $user;
    }

    /** @return array<string, Customer> */
    protected function createCustomers(Company $company): array
    {
        $customers = [];
        foreach ($this->d['customers'] as $key => [$name, $contact, $email, $phone, $street, $zip, $city, $registry, $vat, $terms]) {
            $customers[$key] = Customer::create(array_merge([
                'company_id' => $company->id,
                'name' => $name,
                'type' => 'business',
                'contact_name' => $contact,
                'email' => $email,
                'phone' => $phone,
                'address_line' => $street,
                'postal_code' => $zip,
                'city' => $city,
                'country' => $this->d['country'],
                'kvk_number' => $registry,
                'vat_number' => $vat,
                'payment_terms' => $terms,
            ], $this->d['customer_extra']));
        }

        return $customers;
    }

    protected function createProducts(Company $company): void
    {
        foreach ($this->d['products'] as [$name, $description, $sku, $unit, $price, $rate]) {
            Product::create([
                'company_id' => $company->id,
                'name' => $name,
                'description' => $description,
                'sku' => $sku,
                'unit' => $unit,
                'price' => $price,
                'vat_rate' => $rate,
                'is_active' => true,
            ]);
        }
    }

    /** @return array<string, Invoice> */
    protected function createInvoices(Company $company, array $customers): array
    {
        $year = now()->year;
        $seq = 30;
        $made = [];

        // --- Betaalde facturen (historie voor de grafieken) ---
        foreach ($this->d['paid'] as $i => [$customerKey, $daysAgo, $lines]) {
            $invoice = $this->makeInvoice($company, $customers[$customerKey], $lines, [
                'number' => sprintf($this->d['invoice_number'], $year, ++$seq),
                'status' => 'sent',
                'invoice_date' => now()->subDays($daysAgo),
                'reference' => sprintf($this->d['paid_reference'], $year, 100 + $i),
            ]);

            // Betaling registreren → status wordt automatisch 'paid'.
            Payment::create([
                'company_id' => $company->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'paid_on' => now()->subDays($daysAgo - 9),
                'method' => 'bank_transfer',
                'reference' => $this->d['payment_reference'].$invoice->number,
            ]);

            // De betaling heeft de factuur zelf al bijgewerkt (status 'paid');
            // eerst opnieuw inlezen, anders overschrijven we dat met oude waarden.
            $invoice->refresh();
            $paidAt = now()->subDays($daysAgo - 9);
            $invoice->forceFill([
                'paid_at' => $paidAt,
                // In de demo is de bedankmail al "verstuurd", zodat de
                // factuurhistorie laat zien hoe dat eruitziet.
                'thanks_sent_at' => $invoice->customer_email ? $paidAt->copy()->addMinutes(2) : null,
                'thanks_sent_to' => $invoice->customer_email,
            ])->save();
            $made['paid'.$i] = $invoice;
        }

        // --- Deels betaald ---
        $spec = $this->d['partial'];
        $partial = $this->makeInvoice($company, $customers[$spec['customer']], $spec['lines'], [
            'number' => sprintf($this->d['invoice_number'], $year, ++$seq),
            'status' => 'sent',
            'invoice_date' => now()->subDays(21),
            'reference' => $spec['reference'],
        ]);
        Payment::create([
            'company_id' => $company->id,
            'invoice_id' => $partial->id,
            'amount' => $spec['amount'],
            'paid_on' => now()->subDays(9),
            'method' => $spec['method'],
            'reference' => $spec['payment_reference'],
            'notes' => $spec['payment_notes'],
        ]);
        $made['partial'] = $partial->fresh();

        // --- Openstaand (nog niet vervallen) ---
        $spec = $this->d['open'];
        $made['open'] = $this->makeInvoice($company, $customers[$spec['customer']], $spec['lines'], [
            'number' => sprintf($this->d['invoice_number'], $year, ++$seq),
            'status' => 'sent',
            'invoice_date' => now()->subDays(4),
            'reference' => $spec['reference'],
            'notes' => $spec['notes'],
        ]);

        // --- Vervallen (met herinnering in het verloop) ---
        $spec = $this->d['overdue'];
        $overdue = $this->makeInvoice($company, $customers[$spec['customer']], $spec['lines'], [
            'number' => sprintf($this->d['invoice_number'], $year, ++$seq),
            'status' => 'overdue',
            'invoice_date' => now()->subDays($spec['days_ago']),
            'reference' => $spec['reference'],
        ]);
        ReminderLog::create([
            'company_id' => $company->id,
            'invoice_id' => $overdue->id,
            'type' => $spec['reminder'],
            'kind' => 'reminder',
            'channel' => 'email',
            'sent_to' => $overdue->customer_email,
            'amount_open' => $overdue->total,
            'sent_at' => now()->subDays($spec['reminder_days_ago']),
        ]);
        $made['overdue'] = $overdue;

        // --- Incasso-dossier ---
        $spec = $this->d['incasso'];
        $incasso = $this->makeInvoice($company, $customers[$spec['customer']], $spec['lines'], [
            'number' => sprintf($this->d['invoice_number'], $year, ++$seq),
            'status' => 'sent',
            'invoice_date' => now()->subDays(96),
            'reference' => $spec['reference'],
        ]);
        foreach ($spec['trail'] as [$label, $kind, $daysAgo]) {
            ReminderLog::create([
                'company_id' => $company->id,
                'invoice_id' => $incasso->id,
                'type' => $label,
                'kind' => $kind,
                'channel' => 'email',
                'sent_to' => $incasso->customer_email,
                'amount_open' => $incasso->total,
                'sent_at' => now()->subDays($daysAgo),
            ]);
        }
        $incasso->forceFill([
            'status' => 'incasso',
            'incasso_sent_at' => now()->subDays(45),
            'incasso_reference' => sprintf('%s-%d-0001', Market::isPl() ? 'SF' : 'ARM', $year),
            'incasso_handler' => Market::incasso('partner_name'),
            'incasso_phase' => 'minnelijk',
        ])->save();
        DB::table('incasso_sequences')->insert([
            'company_id' => $company->id,
            'year' => $year,
            'current_value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $made['incasso'] = $incasso;

        // Bijlage bij het incassodossier — laat zien hoe bewijsstukken meegaan.
        [$filename, $title, $body] = $this->d['attachment'];
        $this->attachSample($company, $incasso, $filename, $title, $body);

        // --- Creditnota op een betaalde factuur ---
        $spec = $this->d['credit'];
        $original = $made['paid1'];
        $credit = $this->makeInvoice($company, $customers[$spec['customer']], $spec['lines'], [
            'number' => sprintf($spec['number'], $year, 1),
            'status' => 'sent',
            'invoice_date' => now()->subDays(74),
            'is_credit' => true,
            'credits_invoice_id' => $original->id,
            'reference' => sprintf($spec['reference'], $original->number),
            'notes' => sprintf($spec['notes'], $original->number),
        ]);
        $made['credit'] = $credit;

        // --- Concept ---
        $spec = $this->d['draft'];
        $made['draft'] = $this->makeInvoice($company, $customers[$spec['customer']], $spec['lines'], [
            'number' => null,
            'status' => 'draft',
            'invoice_date' => now(),
            'reference' => $spec['reference'],
            'notes' => $spec['notes'],
        ]);

        // Nummerreeks bijwerken zodat een nieuwe factuur netjes doortelt.
        DB::table('invoice_sequences')->insert([
            'company_id' => $company->id,
            'year' => $year,
            'last_number' => $seq,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $made;
    }

    protected function createRecurring(Company $company, array $customers, array $invoices): void
    {
        [$monthly, $quarterly] = $this->d['recurring'];

        RecurringInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $customers[$monthly['customer']]->id,
            'source_invoice_id' => $invoices['open']->id ?? null,
            'frequency' => 'monthly',
            'start_date' => now()->subMonths(4)->startOfMonth(),
            'next_run_on' => now()->addDays(6),
            'auto_send' => true,
            'active' => true,
            'reference' => $monthly['reference'],
            'payment_terms' => 14,
            'lines' => $monthly['lines'],
            'last_run_on' => now()->subDays(24),
            'invoices_generated' => 4,
        ]);

        RecurringInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $customers[$quarterly['customer']]->id,
            'frequency' => 'quarterly',
            'start_date' => now()->subMonths(6)->startOfMonth(),
            'next_run_on' => now()->addDays(19),
            'auto_send' => false,
            'active' => true,
            'reference' => $quarterly['reference'],
            'payment_terms' => 30,
            'lines' => $quarterly['lines'],
            'last_run_on' => now()->subMonths(2)->subDays(11),
            'invoices_generated' => 2,
        ]);
    }

    /**
     * Btw-aangifte in de demo: het vorige tijdvak al aangegeven en betaald —
     * zo zie je hoe de historie eruitziet. In Nederland per kwartaal, met een
     * (fictief) omzetbelastingnummer zodat het betalingskenmerk verschijnt;
     * in Polen per maand (JPK_V7M), zonder ob-nummer.
     */
    protected function createVatFiling(Company $company): void
    {
        if ($this->d['ob_number']) {
            // 123456782 is het bekende test-BSN (voldoet aan de elfproef); niet van iemand.
            $company->forceFill(['ob_number' => $this->d['ob_number']])->save();
        }

        if ($this->d['vat_period'] === 'month') {
            $previous = now()->subMonth();
            $period = $previous->month;
            $filedAt = now()->startOfMonth()->addDays(19)->setTime(10, 15);
        } else {
            $previous = now()->subQuarter();
            $period = (int) ceil($previous->month / 3);
            $filedAt = now()->firstOfQuarter()->addDays(11)->setTime(10, 15);
        }
        if ($filedAt->isFuture()) {
            $filedAt = now()->subDay();
        }

        \App\Models\VatFiling::create([
            'company_id' => $company->id,
            'year' => $previous->year,
            'period_type' => $this->d['vat_period'],
            'period' => $period,
            'filed_at' => $filedAt,
            'paid_at' => $filedAt->copy()->addMinutes(20),
        ]);
    }

    /**
     * Maak één factuur met regels en berekende totalen.
     *
     * @param  array<int, array{0:string,1:float,2:float,3?:int}>  $lines  [omschrijving, aantal, stuksprijs, (btw-tarief)]
     */
    protected function makeInvoice(Company $company, Customer $customer, array $lines, array $attributes): Invoice
    {
        $normalized = $this->normalizeLines($lines);

        $totals = $this->vat->calculateInvoice($normalized);
        $invoiceDate = Carbon::parse($attributes['invoice_date']);
        $terms = $attributes['payment_terms'] ?? $customer->payment_terms ?? 14;

        $invoice = Invoice::create(array_merge([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate->copy()->addDays($terms),
            'payment_terms' => $terms,

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
            'footer' => $company->invoice_footer,
        ], $attributes));

        if (($attributes['status'] ?? null) !== 'draft') {
            $invoice->forceFill(['sent_at' => $invoiceDate->copy()->addHours(9)])->save();
        }

        foreach ($normalized as $index => $line) {
            $calc = $this->vat->calculateLine($line['quantity'], $line['unit_price'], $line['vat_rate']);
            $invoice->lines()->create([
                'sort_order' => $index,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit' => $this->unitFor($line['description']),
                'unit_price' => $line['unit_price'],
                'vat_rate' => $line['vat_rate'],
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
        }

        return $invoice;
    }

    /** [omschrijving, aantal, prijs, (tarief)] → regels met het markttarief als standaard. */
    protected function normalizeLines(array $lines): array
    {
        return array_map(fn ($l) => [
            'description' => $l[0],
            'quantity' => $l[1],
            'unit_price' => $l[2],
            'vat_rate' => $l[3] ?? $this->d['vat_rate'],
        ], $lines);
    }

    /** Eenheid op de regel: per omschrijving uit het dataset, anders de standaard ('stuk' / 'szt.'). */
    protected function unitFor(string $description): string
    {
        return $this->d['units'][$description] ?? $this->d['unit'];
    }

    /** Offertes in verschillende stadia, zodat de hele flow zichtbaar is. */
    protected function createQuotes(Company $company, array $customers): void
    {
        $year = now()->year;
        $specs = $this->d['quotes'];

        foreach ($specs as $i => [$customerKey, $status, $daysAgo, $validDays, $reference, $lines]) {
            $customer = $customers[$customerKey];
            $quoteDate = now()->subDays($daysAgo);

            $normalized = $this->normalizeLines($lines);

            $totals = $this->vat->calculateInvoice($normalized);

            $quote = Quote::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'number' => sprintf($this->d['quote_number'], $year, $i + 1),
                'status' => $status,
                'reference' => $reference,
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

                'intro' => $this->d['quote_intro'],
                'footer' => $company->invoice_footer,
                'sent_at' => $quoteDate->copy()->addHours(10),
                'accepted_at' => $status === 'accepted' ? $quoteDate->copy()->addDays(4) : null,
            ]);

            foreach ($normalized as $index => $line) {
                $calc = $this->vat->calculateLine($line['quantity'], $line['unit_price'], $line['vat_rate']);
                $quote->lines()->create([
                    'sort_order' => $index,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit' => $this->unitFor($line['description']),
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'line_subtotal' => $calc['subtotal'],
                    'line_vat' => $calc['vat'],
                    'line_total' => $calc['total'],
                ]);
            }
        }

        DB::table('quote_sequences')->insert([
            'company_id' => $company->id,
            'year' => $year,
            'last_number' => count($specs),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Uren en ritten (nog niet gefactureerd), zodat "Klaar om te factureren"
     * in de demo iets laat zien. Alleen wanneer het dataset ze bevat.
     */
    protected function createHoursAndTrips(Company $company, User $user, array $customers): void
    {
        foreach ($this->d['hours'] as [$customerKey, $daysAgo, $project, $description, $minutes, $billable]) {
            TimeEntry::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'customer_id' => $customerKey ? $customers[$customerKey]->id : null,
                'work_date' => now()->subDays($daysAgo),
                'project' => $project,
                'description' => $description,
                'minutes' => $minutes,
                'billable' => $billable,
            ]);
        }

        foreach ($this->d['trips'] as [$customerKey, $daysAgo, $from, $to, $roundTrip, $description, $km, $billable]) {
            Trip::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'customer_id' => $customerKey ? $customers[$customerKey]->id : null,
                'trip_date' => now()->subDays($daysAgo),
                'from_location' => $from,
                'to_location' => $to,
                'round_trip' => $roundTrip,
                'description' => $description,
                'kilometers' => $km,
                // Geen eigen tarief: het standaardtarief van het bedrijf (km_rate van de markt) geldt.
                'billable' => $billable,
            ]);
        }
    }

    /** Inkoopfacturen (open en betaald) voor het inkoopoverzicht en de voorbelasting. Alleen wanneer het dataset ze bevat. */
    protected function createPurchases(Company $company): void
    {
        foreach ($this->d['purchases'] as [$supplier, $reference, $category, $daysAgo, $termDays, $paidDaysAgo, $method, $base, $rate]) {
            $vatAmount = round($base * $rate / 100, 2);
            $invoiceDate = now()->subDays($daysAgo);

            PurchaseInvoice::create([
                'company_id' => $company->id,
                'supplier_name' => $supplier,
                'supplier_reference' => $reference,
                'category' => $category,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays($termDays),
                'status' => $paidDaysAgo === null ? 'open' : 'paid',
                'paid_at' => $paidDaysAgo === null ? null : now()->subDays($paidDaysAgo),
                'payment_method' => $method,
                'subtotal' => $base,
                'vat_total' => $vatAmount,
                'total' => round($base + $vatAmount, 2),
                'vat_lines' => [['base' => $base, 'rate' => (float) $rate, 'vat' => $vatAmount]],
            ]);
        }
    }

    /**
     * Hang een klein voorbeeld-PDF'je aan een factuur, zodat de bijlagenfunctie
     * in de demo ook echt iets laat zien.
     */
    protected function attachSample(Company $company, Invoice $invoice, string $filename, string $title, string $body): void
    {
        try {
            $html = sprintf(
                '<html><body style="font-family:sans-serif;padding:40px;">'
                .'<h1 style="font-size:20px;">%s</h1>'
                .'<p style="font-size:13px;line-height:1.6;color:#333;">%s</p>'
                .'<p style="font-size:12px;color:#777;margin-top:40px;">'.$this->d['attachment_note'].'</p>'
                .'</body></html>',
                e($title),
                e($body),
                e($company->name),
                e(Brand::name())
            );

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output();

            Attachment::create([
                'company_id' => $company->id,
                'attachable_type' => Invoice::class,
                'attachable_id' => $invoice->id,
                'filename' => $filename,
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($pdf),
                'file_data' => base64_encode($pdf),
            ]);
        } catch (\Throwable $e) {
            // Een ontbrekende voorbeeldbijlage mag de demo nooit blokkeren.
            \Illuminate\Support\Facades\Log::warning('Demo-bijlage maken mislukt', ['error' => $e->getMessage()]);
        }
    }

    /** Uniek registernummer: 8-cijferig KvK-nummer (nl) of 9-cijferig REGON (pl). */
    protected function uniqueRegistry(): string
    {
        do {
            $number = (string) (Market::isPl()
                ? random_int(360000000, 369999999)
                : random_int(80000000, 89999999));
        } while (Company::where('kvk_number', $number)->exists());

        return $number;
    }

    /** Uniek btw-nummer: NL…B.. (nl) of een NIP met kloppend controlecijfer (pl). */
    protected function uniqueVat(): string
    {
        do {
            $vat = Market::isPl()
                ? $this->randomNip()
                : 'NL'.random_int(100000000, 999999999).'B'.str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
        } while (Company::where('vat_number', $vat)->exists());

        return $vat;
    }

    /**
     * Fictieve NIP uit de Krakowse reeks (675…) met geldig controlecijfer
     * (gewichten 6 5 7 2 3 4 5 6 7, modulo 11). Komt de rest op 10 uit, dan
     * bestaat er geen geldig controlecijfer en proberen we opnieuw.
     */
    protected function randomNip(): string
    {
        do {
            $base = '675'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sum = 0;
            foreach ([6, 5, 7, 2, 3, 4, 5, 6, 7] as $i => $weight) {
                $sum += $weight * (int) $base[$i];
            }
            $nip = $base.($sum % 11);
        } while (! NipService::valid($nip));

        return $nip;
    }

    /** Voorbeelddata van de actieve markt. */
    protected function dataset(): array
    {
        return Market::isPl() ? $this->datasetPl() : $this->datasetNl();
    }

    /**
     * Nederland: Jansen Webdesign in Hilversum — EUR, btw 21%, KvK, nummers
     * 2026-0031…, Armaere als incassopartner.
     */
    protected function datasetNl(): array
    {
        return [
            'company' => [
                'name' => 'Jansen Webdesign',
                'trading_name' => 'Jansen Webdesign',
                'iban' => 'NL12 RABO 0123 4567 89',
                'email' => 'administratie@jansenwebdesign.nl',
                'phone' => '035 - 123 45 67',
                'website' => 'www.jansenwebdesign.nl',
                'address_line' => 'Lindelaan 12',
                'postal_code' => '1211 AB',
                'city' => 'Hilversum',
                'country' => 'NL',
                'currency' => 'EUR',
                'brand_color' => '#E8231F',
                'accent_color' => '#1C1917',
                'invoice_template' => 'modern',
                'invoice_font' => 'sans',
                'price_mode' => 'excl',
                'fiscal_year_start' => 1,
                'default_send_method' => 'email',
                'results_per_page' => 25,
                'default_payment_terms' => 14,
                'invoice_number_format' => '{year}-{sequence:4}',
                'invoice_footer' => 'Bedankt voor de samenwerking! Gelieve het factuurbedrag binnen de betaaltermijn te voldoen onder vermelding van het factuurnummer.',
                'copy_email' => 'administratie@jansenwebdesign.nl',
                'accountant_email' => 'boekhouding@dekkeraccountants.nl',
                'daily_notification_enabled' => true,
                'daily_notification_email' => 'administratie@jansenwebdesign.nl',
            ],
            'user_name' => 'Jan Jansen',
            'country' => 'NL',
            'customer_extra' => [],
            'vat_rate' => 21,
            'unit' => 'stuk',
            'units' => [],
            'invoice_number' => '%d-%04d',
            'quote_number' => 'OFF-%d-%04d',
            'ob_number' => '123456782B01',
            'vat_period' => 'quarter',

            'customers' => [
                'devries' => ['De Vries Bouw B.V.', 'M. de Vries', 'administratie@devriesbouw.nl', '030 - 245 67 89', 'Ambachtsweg 45', '3542 DG', 'Utrecht', '30123456', 'NL812345678B01', 14],
                'lumen'   => ['Studio Lumen', 'S. Bakker', 'hallo@studiolumen.nl', '020 - 623 45 12', 'Prinsengracht 210', '1016 HD', 'Amsterdam', '34567890', 'NL823456789B01', 30],
                'groen'   => ['Coöperatie Groen', 'T. Hofman', 'admin@cooperatiegroen.nl', '026 - 445 12 30', 'Velperweg 88', '6824 HL', 'Arnhem', '09123456', 'NL834567890B01', 30],
                'stoepje' => ['Bakkerij Het Stoepje', 'J. Smit', 'jan@hetstoepje.nl', '035 - 621 09 87', 'Kerkstraat 3', '1211 CK', 'Hilversum', '32109876', null, 14],
                'janssen' => ['Janssen Advies', 'P. Janssen', 'p.janssen@janssenadvies.nl', '073 - 612 34 56', 'Hinthamerstraat 120', '5211 MT', "'s-Hertogenbosch", '17654321', 'NL845678901B01', 14],
                'smile'   => ['Tandartspraktijk Smile', 'Dr. K. Visser', 'balie@tandartssmile.nl', '010 - 411 22 33', 'Blaak 16', '3011 TA', 'Rotterdam', '24681012', null, 14],
            ],

            'products' => [
                ['Website ontwerp & realisatie', 'Volledig maatwerk: ontwerp, bouw en oplevering.', 'P001', 'project', 1650.00, 21],
                ['Onderhoudscontract', 'Updates, back-ups en monitoring.', 'P002', 'maand', 45.00, 21],
                ['Uurtarief development', 'Doorontwikkeling en aanpassingen.', 'P003', 'uur', 75.00, 21],
                ['Hosting (managed)', 'Snelle hosting inclusief SSL-certificaat.', 'P004', 'maand', 12.50, 21],
                ['Logo & huisstijl', 'Logo, kleuren en typografie.', 'P005', 'project', 480.00, 21],
                ['SEO-pakket Start', 'Maandelijkse optimalisatie en rapportage.', 'P006', 'maand', 225.00, 21],
            ],

            // [klant, dagen geleden, regels]
            'paid' => [
                ['lumen', 118, [['Website ontwerp & realisatie', 1, 1650], ['Logo & huisstijl', 1, 480]]],
                ['stoepje', 96, [['Uurtarief development', 6, 75], ['Hosting (managed)', 3, 12.50]]],
                ['devries', 82, [['Website ontwerp & realisatie', 1, 1650], ['SEO-pakket Start', 2, 225]]],
                ['janssen', 68, [['Uurtarief development', 8, 75]]],
                ['groen', 54, [['SEO-pakket Start', 1, 225], ['Onderhoudscontract', 3, 45]]],
                ['lumen', 41, [['Uurtarief development', 12, 75], ['Hosting (managed)', 1, 12.50]]],
                ['stoepje', 27, [['Onderhoudscontract', 3, 45], ['Hosting (managed)', 3, 12.50]]],
            ],
            'paid_reference' => 'Opdracht %d-%d',
            'payment_reference' => 'SEPA ',

            'partial' => [
                'customer' => 'groen',
                'lines' => [['Website ontwerp & realisatie', 1, 1650], ['Uurtarief development', 4, 75]],
                'reference' => 'Ledenportaal fase 1',
                'amount' => 1000.00,
                'method' => 'bank_transfer',
                'payment_reference' => 'Aanbetaling',
                'payment_notes' => 'Deelbetaling volgens afspraak; restant na oplevering.',
            ],
            'open' => [
                'customer' => 'devries',
                'lines' => [['Website ontwerp & realisatie', 1, 1650], ['Onderhoudscontract', 3, 45], ['Uurtarief development', 7, 75]],
                'reference' => 'Nieuwbouw website',
                'notes' => 'Oplevering in overleg gepland voor volgende maand.',
            ],
            'overdue' => [
                'customer' => 'stoepje',
                'lines' => [['Uurtarief development', 5, 75], ['Hosting (managed)', 4, 12.50]],
                'reference' => 'Webshop-uitbreiding',
                'days_ago' => 38,
                'reminder' => 'Eerste herinnering',
                'reminder_days_ago' => 16,
            ],
            'incasso' => [
                'customer' => 'groen',
                'lines' => [['SEO-pakket Start', 4, 225], ['Uurtarief development', 3, 75]],
                'reference' => 'Campagne voorjaar',
                'trail' => [
                    ['Eerste herinnering', 'reminder', 74],
                    ['Laatste aanmaning', 'warning', 60],
                ],
            ],
            'attachment' => [
                'Opdrachtbevestiging Coöperatie Groen.pdf',
                'Opdrachtbevestiging',
                'Hierbij bevestigen wij de opdracht voor de voorjaarscampagne, conform offerte en '
                    .'akkoord per e-mail. Betaling binnen 30 dagen na factuurdatum.',
            ],
            'attachment_note' => '%s — voorbeelddocument uit de %s-demo.',
            'credit' => [
                'customer' => 'stoepje',
                'lines' => [['Correctie: te veel gefactureerde uren', 2, -75]],
                'number' => 'C%d-%04d',
                'reference' => 'Creditnota bij %s',
                'notes' => 'Correctie op factuur %s: twee uur te veel gefactureerd.',
            ],
            'draft' => [
                'customer' => 'smile',
                'lines' => [['Website ontwerp & realisatie', 1, 1650], ['Logo & huisstijl', 1, 480]],
                'reference' => 'Offerte praktijkwebsite',
                'notes' => 'Concept — nog te bespreken met de klant.',
            ],

            // Maandelijks (gekoppeld aan de openstaande factuur) en per kwartaal.
            'recurring' => [
                ['customer' => 'devries', 'reference' => 'Onderhoud & hosting', 'lines' => [
                    ['description' => 'Onderhoudscontract', 'details' => 'Updates, back-ups en monitoring', 'quantity' => 1, 'unit' => 'maand', 'unit_price' => 45.00, 'vat_rate' => 21],
                    ['description' => 'Hosting (managed)', 'details' => null, 'quantity' => 1, 'unit' => 'maand', 'unit_price' => 12.50, 'vat_rate' => 21],
                ]],
                ['customer' => 'lumen', 'reference' => 'SEO-pakket', 'lines' => [
                    ['description' => 'SEO-pakket Start', 'details' => 'Optimalisatie en kwartaalrapportage', 'quantity' => 3, 'unit' => 'maand', 'unit_price' => 225.00, 'vat_rate' => 21],
                ]],
            ],

            // [klant, status, dagen geleden, geldig (dagen), referentie, regels]
            'quotes' => [
                ['smile', 'sent', 5, 30, 'Praktijkwebsite', [
                    ['Website ontwerp & realisatie', 1, 1650],
                    ['Logo & huisstijl', 1, 480],
                    ['Hosting (managed)', 12, 12.50],
                ]],
                ['janssen', 'accepted', 22, 30, 'Doorontwikkeling portaal', [
                    ['Uurtarief development', 24, 75],
                ]],
                ['lumen', 'expired', 68, 21, 'Campagnepagina', [
                    ['Website ontwerp & realisatie', 1, 1650],
                    ['SEO-pakket Start', 3, 225],
                ]],
            ],
            'quote_intro' => 'Naar aanleiding van ons gesprek doen wij je graag het volgende voorstel.',

            'hours' => [],
            'trips' => [],
            'purchases' => [],
        ];
    }

    /**
     * Polen (Lopra Polska): Studio Wnętrz Kowalska — Anna Kowalska, projektantka
     * wnętrz in Kraków. PLN, btw 23% (8% voor afwerking in woningbouw), NIP en
     * REGON, nummers FV/2026/0031…, sprzedamfakture.pl voor de windykacja.
     * Bedragen zijn de Nederlandse × 4,3, afgerond op hele złoty.
     */
    protected function datasetPl(): array
    {
        return [
            'company' => [
                'name' => 'Studio Wnętrz Kowalska',
                'trading_name' => 'Studio Wnętrz Kowalska',
                'iban' => 'PL61 1090 1014 0000 0712 1981 2874',
                'email' => 'biuro@studiokowalska.pl',
                'phone' => '+48 12 345 67 89',
                'website' => 'www.studiokowalska.pl',
                'address_line' => 'ul. Karmelicka 24/3',
                'postal_code' => '31-128',
                'city' => 'Kraków',
                'country' => Market::country(),
                'currency' => Market::currency(),
                'brand_color' => '#2E4A3F',
                'accent_color' => '#D9A066',
                'invoice_template' => 'modern',
                'invoice_font' => 'serif',
                'price_mode' => 'excl',
                'fiscal_year_start' => 1,
                'vat_period' => 'month',
                'default_send_method' => 'email',
                'results_per_page' => 25,
                'default_payment_terms' => 14,
                'default_hourly_rate' => 180.00,
                'default_km_rate' => (float) Market::get('km_rate', 1.15),
                'invoice_number_format' => 'FV/{year}/{sequence:4}',
                'quote_number_format' => 'OF/{year}/{sequence:4}',
                'invoice_footer' => (string) Market::get('invoice_footer'),
                'copy_email' => 'biuro@studiokowalska.pl',
                'accountant_email' => 'biuro@rachunkowosc-nowak.pl',
                'daily_notification_enabled' => true,
                'daily_notification_email' => 'biuro@studiokowalska.pl',
            ],
            'user_name' => 'Anna Kowalska',
            'country' => Market::country(),
            'customer_extra' => ['language' => 'pl'],
            'vat_rate' => Market::defaultVatRate(),
            'unit' => 'szt.',
            'units' => [
                'Nadzór autorski' => 'godz.',
                'Konsultacja online' => 'godz.',
                'Dzień stylizacji' => 'dzień',
                'Prace wykończeniowe (lokal mieszkalny)' => 'm²',
                'Pakiet materiałów i próbek' => 'pakiet',
                'Korekta: zawyżona liczba godzin nadzoru' => 'godz.',
            ],
            'invoice_number' => 'FV/%d/%04d',
            'quote_number' => 'OF/%d/%04d',
            'ob_number' => null,
            'vat_period' => 'month',

            // [naam, contact, e-mail, telefoon, straat, postcode, plaats, REGON, NIP (geldig controlecijfer), termijn]
            'customers' => [
                'nowak'      => ['Nowak Deweloper Sp. z o.o.', 'Marek Nowak', 'biuro@nowakdeweloper.pl', '+48 12 422 10 20', 'ul. Długa 15', '31-147', 'Kraków', '361234567', '6761234565', 14],
                'atelier'    => ['Atelier Zieleń Sp. z o.o.', 'Katarzyna Zielińska', 'kontakt@atelierzielen.pl', '+48 22 620 30 40', 'ul. Prosta 51', '00-838', 'Warszawa', '362345678', '5262345671', 30],
                'kawiarnia'  => ['Kawiarnia Pod Lipą', 'Tomasz Lipiński', 'tomasz@podlipa.pl', '+48 71 344 20 10', 'Rynek 12', '50-101', 'Wrocław', '363456789', '8973456780', 14],
                'wisniewska' => ['Agnieszka Wiśniewska', 'Agnieszka Wiśniewska', 'a.wisniewska@wp.pl', '+48 601 234 567', 'ul. Bronowicka 8/12', '30-084', 'Kraków', '364567890', '6774567895', 14],
                'hotel'      => ['Hotel Bursztyn Sp. z o.o.', 'Ewa Kowalczyk', 'recepcja@hotelbursztyn.pl', '+48 58 301 50 60', 'ul. Długi Targ 22', '80-828', 'Gdańsk', '365678901', '5835678916', 30],
                'kancelaria' => ['Kancelaria Adwokacka Mazur i Wspólnicy', 'mec. Piotr Mazur', 'sekretariat@mazur-kancelaria.pl', '+48 61 852 40 30', 'ul. Święty Marcin 45', '61-806', 'Poznań', '366789012', '7786789018', 14],
                'dental'     => ['Silesia Dental Sp. z o.o.', 'dr Joanna Kaczmarek', 'rejestracja@silesiadental.pl', '+48 32 258 70 80', 'ul. Mariacka 10', '40-014', 'Katowice', '367890123', '6347890128', 14],
                'wojcik'     => ['Kamil Wójcik', 'Kamil Wójcik', 'kamil.wojcik@onet.pl', '+48 512 345 678', 'ul. Grochowska 240/5', '04-398', 'Warszawa', '368901234', '5218901237', 14],
                'piekarnia'  => ['Piekarnia Rogalik', 'Anna Dąbrowska', 'piekarnia@rogalik.pl', '+48 12 656 00 11', 'ul. Kalwaryjska 32', '30-504', 'Kraków', '369012345', '9459012340', 14],
            ],

            // Diensten 23%; afwerkingswerk in een woning valt onder 8% (budownictwo mieszkaniowe).
            'products' => [
                ['Projekt koncepcyjny wnętrza', 'Układ funkcjonalny, moodboard i wizualizacje 3D.', 'P001', 'projekt', 7095.00, 23],
                ['Konsultacja online', 'Godzinna konsultacja wideo z projektantką.', 'P002', 'godz.', 194.00, 23],
                ['Nadzór autorski', 'Wizyty na budowie i koordynacja wykonawców.', 'P003', 'godz.', 323.00, 23],
                ['Prace wykończeniowe (lokal mieszkalny)', 'Malowanie i montaż zabudów — stawka 8% w budownictwie mieszkaniowym.', 'P004', 'm²', 54.00, 8],
                ['Dzień stylizacji', 'Home staging: dobór dodatków, tekstyliów i oświetlenia.', 'P005', 'dzień', 2064.00, 23],
                ['Pakiet materiałów i próbek', 'Próbki tkanin, farb i okładzin dostarczone do klienta.', 'P006', 'pakiet', 968.00, 23],
            ],

            // [klant, dagen geleden, regels (omschrijving, aantal, prijs, optioneel tarief)]
            'paid' => [
                ['atelier', 118, [['Projekt koncepcyjny showroomu', 1, 7095], ['Dzień stylizacji', 1, 2064]]],
                ['kawiarnia', 96, [['Nadzór autorski', 6, 323], ['Konsultacja online', 3, 194]]],
                ['nowak', 82, [['Projekt koncepcyjny mieszkania pokazowego', 1, 7095], ['Pakiet materiałów i próbek', 2, 968]]],
                ['kancelaria', 68, [['Nadzór autorski', 8, 323]]],
                ['hotel', 54, [['Pakiet materiałów i próbek', 1, 968], ['Konsultacja online', 3, 194]]],
                ['wojcik', 41, [['Projekt koncepcyjny salonu', 1, 7095], ['Prace wykończeniowe (lokal mieszkalny)', 35, 54, 8]]],
                ['piekarnia', 27, [['Konsultacja online', 3, 194], ['Dzień stylizacji', 1, 2064]]],
            ],
            'paid_reference' => 'Zlecenie %d-%d',
            'payment_reference' => 'Przelew ',

            'partial' => [
                'customer' => 'hotel',
                'lines' => [['Projekt koncepcyjny lobby hotelowego', 1, 7095], ['Nadzór autorski', 4, 323]],
                'reference' => 'Lobby hotelowe — etap 1',
                'amount' => 4300.00,
                // BLIK is (nog) geen betaalwijze in de app; 'other' met de referentie erbij.
                'method' => 'other',
                'payment_reference' => 'Zaliczka BLIK',
                'payment_notes' => 'Płatność częściowa zgodnie z umową; reszta po odbiorze prac.',
            ],
            'open' => [
                'customer' => 'nowak',
                'lines' => [['Projekt koncepcyjny mieszkania pokazowego B2', 1, 7095], ['Konsultacja online', 3, 194], ['Nadzór autorski', 7, 323]],
                'reference' => 'Mieszkanie pokazowe B2',
                'notes' => 'Odbiór prac zaplanowany w uzgodnieniu z klientem na przyszły miesiąc.',
            ],
            // 58 dagen oud bij 14 dagen termijn: 44 dagen over tijd — rijp voor de windykacja-demo.
            'overdue' => [
                'customer' => 'kawiarnia',
                'lines' => [['Nadzór autorski', 5, 323], ['Konsultacja online', 4, 194]],
                'reference' => 'Ogródek kawiarniany — rozbudowa',
                'days_ago' => 58,
                'reminder' => 'Pierwsze przypomnienie',
                'reminder_days_ago' => 40,
            ],
            'incasso' => [
                'customer' => 'hotel',
                'lines' => [['Pakiet materiałów i próbek', 4, 968], ['Nadzór autorski', 3, 323]],
                'reference' => 'Aranżacja tarasu — sezon wiosenny',
                'trail' => [
                    ['Pierwsze przypomnienie', 'reminder', 74],
                    ['Ostateczne wezwanie do zapłaty', 'warning', 60],
                ],
            ],
            'attachment' => [
                'Potwierdzenie zlecenia Hotel Bursztyn.pdf',
                'Potwierdzenie zlecenia',
                'Niniejszym potwierdzamy zlecenie aranżacji tarasu na sezon wiosenny, zgodnie z ofertą '
                    .'i akceptacją przesłaną e-mailem. Płatność w terminie 30 dni od daty wystawienia faktury.',
            ],
            'attachment_note' => '%s — przykładowy dokument z wersji demo %s.',
            'credit' => [
                'customer' => 'kawiarnia',
                'lines' => [['Korekta: zawyżona liczba godzin nadzoru', 2, -323]],
                'number' => 'FV/%d/K/%04d',
                'reference' => 'Faktura korygująca do %s',
                'notes' => 'Korekta faktury %s: naliczono o dwie godziny za dużo.',
            ],
            'draft' => [
                'customer' => 'dental',
                'lines' => [['Projekt koncepcyjny recepcji i poczekalni', 1, 7095], ['Dzień stylizacji', 1, 2064]],
                'reference' => 'Oferta — recepcja kliniki',
                'notes' => 'Szkic — do omówienia z klientem.',
            ],

            'recurring' => [
                ['customer' => 'nowak', 'reference' => 'Nadzór autorski — osiedle Zielone Wzgórze', 'lines' => [
                    ['description' => 'Nadzór autorski', 'details' => 'Ryczałt miesięczny: cztery wizyty na budowie', 'quantity' => 4, 'unit' => 'godz.', 'unit_price' => 323.00, 'vat_rate' => 23],
                    ['description' => 'Konsultacja online', 'details' => null, 'quantity' => 2, 'unit' => 'godz.', 'unit_price' => 194.00, 'vat_rate' => 23],
                ]],
                ['customer' => 'atelier', 'reference' => 'Sezonowa aranżacja showroomu', 'lines' => [
                    ['description' => 'Dzień stylizacji', 'details' => 'Zmiana ekspozycji showroomu na nowy sezon', 'quantity' => 1, 'unit' => 'dzień', 'unit_price' => 2064.00, 'vat_rate' => 23],
                ]],
            ],

            'quotes' => [
                ['dental', 'sent', 5, 30, 'Recepcja i poczekalnia kliniki', [
                    ['Projekt koncepcyjny recepcji i poczekalni', 1, 7095],
                    ['Dzień stylizacji', 1, 2064],
                    ['Nadzór autorski', 12, 323],
                ]],
                ['kancelaria', 'accepted', 22, 30, 'Sala konferencyjna — projekt wykonawczy', [
                    ['Nadzór autorski', 24, 323],
                ]],
                ['atelier', 'expired', 68, 21, 'Witryna sezonowa', [
                    ['Projekt koncepcyjny witryny', 1, 7095],
                    ['Pakiet materiałów i próbek', 3, 968],
                ]],
            ],
            'quote_intro' => 'W nawiązaniu do naszej rozmowy przedstawiamy poniższą propozycję.',

            // [klant, dagen geleden, project, omschrijving, minuten, factureerbaar] — tarief: 180 zł/godz. van het bedrijf.
            'hours' => [
                ['nowak', 1, 'Mieszkanie pokazowe B2', 'Rewizja układu kuchni po uwagach klienta', 150, true],
                ['nowak', 2, 'Mieszkanie pokazowe B2', 'Dobór okładzin i armatury łazienkowej', 120, true],
                ['dental', 3, 'Recepcja kliniki', 'Spotkanie wstępne i inwentaryzacja pomieszczeń', 90, true],
                ['wisniewska', 4, 'Salon w Bronowicach', 'Moodboard i lista zakupów', 180, true],
                ['wisniewska', 6, 'Salon w Bronowicach', 'Konsultacja: kolorystyka i oświetlenie', 60, true],
                [null, 1, null, 'Administracja i wystawianie faktur', 45, false],
            ],
            // [klant, dagen geleden, van, naar, retour, doel, km, factureerbaar] — tarief: km_rate van de markt (1,15 zł).
            'trips' => [
                ['nowak', 2, 'Kraków, ul. Karmelicka 24', 'Kraków, os. Zielone Wzgórze', true, 'Wizyta na budowie — mieszkanie pokazowe B2', 18.0, true],
                ['dental', 3, 'Kraków, ul. Karmelicka 24', 'Katowice, ul. Mariacka 10', true, 'Inwentaryzacja recepcji kliniki', 162.0, true],
                ['wisniewska', 6, 'Kraków, ul. Karmelicka 24', 'Kraków, ul. Bronowicka 8', true, 'Konsultacja u klientki', 9.0, true],
                [null, 9, 'Kraków', 'Warszawa, targi Warsaw Home', true, 'Targi wnętrzarskie — inspiracje i kontakty', 590.0, false],
            ],
            // [leverancier, factuurnummer, categorie, dagen geleden, termijn, betaald dagen geleden (null = open), betaalwijze, netto, btw%]
            'purchases' => [
                ['Castorama Polska Sp. z o.o.', 'FV/KRK/2026/48812', 'Materiały', 12, 14, null, null, 1284.55, 23],
                ['Allegro.pl', 'FS-2026-0917331', 'Wyposażenie', 20, 14, 18, 'card', 356.10, 23],
                ['IKEA Retail Sp. z o.o. — Kraków', 'FV/2026/0123456', 'Wyposażenie', 33, 7, 33, 'card', 2439.02, 23],
                ['Biuro Rachunkowe Nowak', 'FV 7/2026', 'Księgowość', 6, 14, null, null, 450.00, 23],
                ['Orange Polska S.A.', '2026/07/0042', 'Telefon i internet', 41, 14, 30, 'direct_debit', 129.00, 23],
            ],
        ];
    }
}

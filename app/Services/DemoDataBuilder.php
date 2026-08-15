<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\ReminderLog;
use App\Models\User;
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
 * Elke bezoeker krijgt zijn eigen bedrijf, zodat je vrij kunt klikken zonder
 * dat je de demo van iemand anders verstoort. Na afloop ruimt
 * `demo:cleanup` alles weer op.
 */
class DemoDataBuilder
{
    /** Hoe lang een demo-omgeving blijft bestaan. */
    public const LIFETIME_HOURS = 24;

    public function __construct(protected VatCalculator $vat) {}

    /**
     * Maak een volledige demo-omgeving en geef de bijbehorende (ingelogde) gebruiker terug.
     */
    public function build(): User
    {
        return DB::transaction(function () {
            $company = $this->createCompany();
            $user = $this->createUser($company);

            $customers = $this->createCustomers($company);
            $this->createProducts($company);
            $invoices = $this->createInvoices($company, $customers);
            $this->createRecurring($company, $customers, $invoices);

            return $user;
        });
    }

    protected function createCompany(): Company
    {
        return Company::create([
            'name' => 'Jansen Webdesign',
            'is_demo' => true,
            'demo_expires_at' => now()->addHours(self::LIFETIME_HOURS),
            'trading_name' => 'Jansen Webdesign',
            // Willekeurige (maar realistisch ogende) nummers: het KvK- en
            // BTW-nummer zijn uniek in de database, en elke demo is een eigen bedrijf.
            'kvk_number' => $this->uniqueKvk(),
            'vat_number' => $this->uniqueVat(),
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
            // Ruime proefperiode zodat de demo nooit tegen het abonnementsslot loopt.
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    protected function createUser(Company $company): User
    {
        $user = User::create([
            'name' => 'Jan Jansen',
            // Uniek adres per demo; er wordt toch nooit echt naartoe gemaild.
            'email' => 'demo+'.Str::lower(Str::random(10)).'@easyinvoice.nl',
            'password' => Hash::make(Str::random(40)),
            'company_id' => $company->id,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    /** @return array<string, Customer> */
    protected function createCustomers(Company $company): array
    {
        $rows = [
            'devries' => ['De Vries Bouw B.V.', 'M. de Vries', 'administratie@devriesbouw.nl', '030 - 245 67 89', 'Ambachtsweg 45', '3542 DG', 'Utrecht', '30123456', 'NL812345678B01', 14],
            'lumen'   => ['Studio Lumen', 'S. Bakker', 'hallo@studiolumen.nl', '020 - 623 45 12', 'Prinsengracht 210', '1016 HD', 'Amsterdam', '34567890', 'NL823456789B01', 30],
            'groen'   => ['Coöperatie Groen', 'T. Hofman', 'admin@cooperatiegroen.nl', '026 - 445 12 30', 'Velperweg 88', '6824 HL', 'Arnhem', '09123456', 'NL834567890B01', 30],
            'stoepje' => ['Bakkerij Het Stoepje', 'J. Smit', 'jan@hetstoepje.nl', '035 - 621 09 87', 'Kerkstraat 3', '1211 CK', 'Hilversum', '32109876', null, 14],
            'janssen' => ['Janssen Advies', 'P. Janssen', 'p.janssen@janssenadvies.nl', '073 - 612 34 56', 'Hinthamerstraat 120', '5211 MT', "'s-Hertogenbosch", '17654321', 'NL845678901B01', 14],
            'smile'   => ['Tandartspraktijk Smile', 'Dr. K. Visser', 'balie@tandartssmile.nl', '010 - 411 22 33', 'Blaak 16', '3011 TA', 'Rotterdam', '24681012', null, 14],
        ];

        $customers = [];
        foreach ($rows as $key => [$name, $contact, $email, $phone, $street, $zip, $city, $kvk, $vat, $terms]) {
            $customers[$key] = Customer::create([
                'company_id' => $company->id,
                'name' => $name,
                'type' => 'business',
                'contact_name' => $contact,
                'email' => $email,
                'phone' => $phone,
                'address_line' => $street,
                'postal_code' => $zip,
                'city' => $city,
                'country' => 'NL',
                'kvk_number' => $kvk,
                'vat_number' => $vat,
                'payment_terms' => $terms,
            ]);
        }

        return $customers;
    }

    protected function createProducts(Company $company): void
    {
        $rows = [
            ['Website ontwerp & realisatie', 'Volledig maatwerk: ontwerp, bouw en oplevering.', 'P001', 'project', 1650.00, 21],
            ['Onderhoudscontract', 'Updates, back-ups en monitoring.', 'P002', 'maand', 45.00, 21],
            ['Uurtarief development', 'Doorontwikkeling en aanpassingen.', 'P003', 'uur', 75.00, 21],
            ['Hosting (managed)', 'Snelle hosting inclusief SSL-certificaat.', 'P004', 'maand', 12.50, 21],
            ['Logo & huisstijl', 'Logo, kleuren en typografie.', 'P005', 'project', 480.00, 21],
            ['SEO-pakket Start', 'Maandelijkse optimalisatie en rapportage.', 'P006', 'maand', 225.00, 21],
        ];

        foreach ($rows as [$name, $description, $sku, $unit, $price, $rate]) {
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
        $paidSpecs = [
            ['lumen', 118, [['Website ontwerp & realisatie', 1, 1650], ['Logo & huisstijl', 1, 480]]],
            ['stoepje', 96, [['Uurtarief development', 6, 75], ['Hosting (managed)', 3, 12.50]]],
            ['devries', 82, [['Website ontwerp & realisatie', 1, 1650], ['SEO-pakket Start', 2, 225]]],
            ['janssen', 68, [['Uurtarief development', 8, 75]]],
            ['groen', 54, [['SEO-pakket Start', 1, 225], ['Onderhoudscontract', 3, 45]]],
            ['lumen', 41, [['Uurtarief development', 12, 75], ['Hosting (managed)', 1, 12.50]]],
            ['stoepje', 27, [['Onderhoudscontract', 3, 45], ['Hosting (managed)', 3, 12.50]]],
        ];

        foreach ($paidSpecs as $i => [$customerKey, $daysAgo, $lines]) {
            $invoice = $this->makeInvoice($company, $customers[$customerKey], $lines, [
                'number' => sprintf('%d-%04d', $year, ++$seq),
                'status' => 'sent',
                'invoice_date' => now()->subDays($daysAgo),
                'reference' => 'Opdracht '.$year.'-'.(100 + $i),
            ]);

            // Betaling registreren → status wordt automatisch 'paid'.
            Payment::create([
                'company_id' => $company->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'paid_on' => now()->subDays($daysAgo - 9),
                'method' => 'bank_transfer',
                'reference' => 'SEPA '.$invoice->number,
            ]);

            // De betaling heeft de factuur zelf al bijgewerkt (status 'paid');
            // eerst opnieuw inlezen, anders overschrijven we dat met oude waarden.
            $invoice->refresh();
            $invoice->forceFill(['paid_at' => now()->subDays($daysAgo - 9)])->save();
            $made['paid'.$i] = $invoice;
        }

        // --- Deels betaald ---
        $partial = $this->makeInvoice($company, $customers['groen'], [
            ['Website ontwerp & realisatie', 1, 1650],
            ['Uurtarief development', 4, 75],
        ], [
            'number' => sprintf('%d-%04d', $year, ++$seq),
            'status' => 'sent',
            'invoice_date' => now()->subDays(21),
            'reference' => 'Ledenportaal fase 1',
        ]);
        Payment::create([
            'company_id' => $company->id,
            'invoice_id' => $partial->id,
            'amount' => 1000.00,
            'paid_on' => now()->subDays(9),
            'method' => 'bank_transfer',
            'reference' => 'Aanbetaling',
            'notes' => 'Deelbetaling volgens afspraak; restant na oplevering.',
        ]);
        $made['partial'] = $partial->fresh();

        // --- Openstaand (nog niet vervallen) ---
        $made['open'] = $this->makeInvoice($company, $customers['devries'], [
            ['Website ontwerp & realisatie', 1, 1650],
            ['Onderhoudscontract', 3, 45],
            ['Uurtarief development', 7, 75],
        ], [
            'number' => sprintf('%d-%04d', $year, ++$seq),
            'status' => 'sent',
            'invoice_date' => now()->subDays(4),
            'reference' => 'Nieuwbouw website',
            'notes' => 'Oplevering in overleg gepland voor volgende maand.',
        ]);

        // --- Vervallen (met herinnering in het verloop) ---
        $overdue = $this->makeInvoice($company, $customers['stoepje'], [
            ['Uurtarief development', 5, 75],
            ['Hosting (managed)', 4, 12.50],
        ], [
            'number' => sprintf('%d-%04d', $year, ++$seq),
            'status' => 'overdue',
            'invoice_date' => now()->subDays(38),
            'reference' => 'Webshop-uitbreiding',
        ]);
        ReminderLog::create([
            'company_id' => $company->id,
            'invoice_id' => $overdue->id,
            'type' => 'Eerste herinnering',
            'kind' => 'reminder',
            'channel' => 'email',
            'sent_to' => $overdue->customer_email,
            'amount_open' => $overdue->total,
            'sent_at' => now()->subDays(16),
        ]);
        $made['overdue'] = $overdue;

        // --- Incasso-dossier ---
        $incasso = $this->makeInvoice($company, $customers['groen'], [
            ['SEO-pakket Start', 4, 225],
            ['Uurtarief development', 3, 75],
        ], [
            'number' => sprintf('%d-%04d', $year, ++$seq),
            'status' => 'sent',
            'invoice_date' => now()->subDays(96),
            'reference' => 'Campagne voorjaar',
        ]);
        $trail = [
            ['Eerste herinnering', 'reminder', 74],
            ['Laatste aanmaning', 'warning', 60],
        ];
        foreach ($trail as [$label, $kind, $daysAgo]) {
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
            'incasso_reference' => sprintf('ARM-%d-0001', $year),
            'incasso_handler' => config('incasso.partner_name', 'Armaere Gerechtsdeurwaarders'),
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

        // --- Creditnota op een betaalde factuur ---
        $original = $made['paid1'];
        $credit = $this->makeInvoice($company, $customers['stoepje'], [
            ['Correctie: te veel gefactureerde uren', 2, -75],
        ], [
            'number' => sprintf('C%d-%04d', $year, 1),
            'status' => 'sent',
            'invoice_date' => now()->subDays(74),
            'is_credit' => true,
            'credits_invoice_id' => $original->id,
            'reference' => 'Creditnota bij '.$original->number,
            'notes' => 'Correctie op factuur '.$original->number.': twee uur te veel gefactureerd.',
        ]);
        $made['credit'] = $credit;

        // --- Concept ---
        $made['draft'] = $this->makeInvoice($company, $customers['smile'], [
            ['Website ontwerp & realisatie', 1, 1650],
            ['Logo & huisstijl', 1, 480],
        ], [
            'number' => null,
            'status' => 'draft',
            'invoice_date' => now(),
            'reference' => 'Offerte praktijkwebsite',
            'notes' => 'Concept — nog te bespreken met de klant.',
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
        RecurringInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $customers['devries']->id,
            'source_invoice_id' => $invoices['open']->id ?? null,
            'frequency' => 'monthly',
            'start_date' => now()->subMonths(4)->startOfMonth(),
            'next_run_on' => now()->addDays(6),
            'auto_send' => true,
            'active' => true,
            'reference' => 'Onderhoud & hosting',
            'payment_terms' => 14,
            'lines' => [
                ['description' => 'Onderhoudscontract', 'details' => 'Updates, back-ups en monitoring', 'quantity' => 1, 'unit' => 'maand', 'unit_price' => 45.00, 'vat_rate' => 21],
                ['description' => 'Hosting (managed)', 'details' => null, 'quantity' => 1, 'unit' => 'maand', 'unit_price' => 12.50, 'vat_rate' => 21],
            ],
            'last_run_on' => now()->subDays(24),
            'invoices_generated' => 4,
        ]);

        RecurringInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $customers['lumen']->id,
            'frequency' => 'quarterly',
            'start_date' => now()->subMonths(6)->startOfMonth(),
            'next_run_on' => now()->addDays(19),
            'auto_send' => false,
            'active' => true,
            'reference' => 'SEO-pakket',
            'payment_terms' => 30,
            'lines' => [
                ['description' => 'SEO-pakket Start', 'details' => 'Optimalisatie en kwartaalrapportage', 'quantity' => 3, 'unit' => 'maand', 'unit_price' => 225.00, 'vat_rate' => 21],
            ],
            'last_run_on' => now()->subMonths(2)->subDays(11),
            'invoices_generated' => 2,
        ]);
    }

    /**
     * Maak één factuur met regels en berekende totalen.
     *
     * @param  array<int, array{0:string,1:float,2:float}>  $lines  [omschrijving, aantal, stuksprijs]
     */
    protected function makeInvoice(Company $company, Customer $customer, array $lines, array $attributes): Invoice
    {
        $normalized = array_map(fn ($l) => [
            'description' => $l[0],
            'quantity' => $l[1],
            'unit_price' => $l[2],
            'vat_rate' => 21,
        ], $lines);

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
                'unit' => 'stuk',
                'unit_price' => $line['unit_price'],
                'vat_rate' => $line['vat_rate'],
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
        }

        return $invoice;
    }

    protected function uniqueKvk(): string
    {
        do {
            $kvk = (string) random_int(80000000, 89999999);
        } while (Company::where('kvk_number', $kvk)->exists());

        return $kvk;
    }

    protected function uniqueVat(): string
    {
        do {
            $vat = 'NL'.random_int(100000000, 999999999).'B'.str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
        } while (Company::where('vat_number', $vat)->exists());

        return $vat;
    }
}

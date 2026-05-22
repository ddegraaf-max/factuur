<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent guard: if demo data already exists, skip seeding entirely.
        // This makes it safe to run `migrate --force --seed` on every deploy.
        if (User::where('email', 'demo@easyinvoice.test')->exists()) {
            $this->command->info('Demo data already present — skipping seeder.');
            return;
        }

        // 1. Create demo company
        $company = Company::create([
            'name' => 'Vries Design B.V.',
            'kvk_number' => '87654321',
            'vat_number' => 'NL862345678B01',
            'iban' => 'NL91 ABNA 0417 1643 00',
            'email' => 'info@vriesdesign.nl',
            'phone' => '+31 20 123 45 67',
            'website' => 'https://vriesdesign.nl',
            'address_line' => 'Keizersgracht 124',
            'postal_code' => '1015 CW',
            'city' => 'Amsterdam',
            'country' => 'NL',
            'brand_color' => '#E8231F',
            'default_payment_terms' => 30,
            'invoice_footer' => 'Bedankt voor uw vertrouwen. Bij vragen: info@vriesdesign.nl',
            'invoice_number_format' => '{year}-{sequence:4}',
        ]);

        // 2. Create demo user (login: demo@easyinvoice.test / wachtwoord: password)
        $user = User::create([
            'company_id' => $company->id,
            'role' => 'owner',
            'name' => 'Demo Gebruiker',
            'email' => 'demo@easyinvoice.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Authenticate so scoped models get company_id auto-assigned
        Auth::login($user);

        // 3. Demo customers
        $customers = [
            ['name' => 'TechFlow Solutions B.V.', 'type' => 'business', 'contact_name' => 'Sander de Boer', 'email' => 'sander@techflow.nl', 'kvk_number' => '76123456', 'vat_number' => 'NL856123456B01', 'address_line' => 'Singel 250', 'postal_code' => '1016 AB', 'city' => 'Amsterdam'],
            ['name' => 'Bakkerij Janssen', 'type' => 'business', 'contact_name' => 'Marieke Janssen', 'email' => 'info@bakkerijjanssen.nl', 'kvk_number' => '12345678', 'address_line' => 'Hoofdstraat 12', 'postal_code' => '3811 EP', 'city' => 'Amersfoort'],
            ['name' => 'Pieter van der Berg', 'type' => 'consumer', 'email' => 'pieter@example.nl', 'address_line' => 'Nieuwstraat 8', 'postal_code' => '2611 AB', 'city' => 'Delft'],
            ['name' => 'Studio Verde', 'type' => 'business', 'contact_name' => 'Lisa Verde', 'email' => 'lisa@studioverde.nl', 'kvk_number' => '34567890', 'vat_number' => 'NL823456789B01', 'address_line' => 'Prinsengracht 401', 'postal_code' => '1016 HM', 'city' => 'Amsterdam'],
            ['name' => 'Atelier Vermeulen', 'type' => 'business', 'contact_name' => 'Tom Vermeulen', 'email' => 'tom@vermeulen.studio', 'kvk_number' => '23456789', 'address_line' => 'Witte de Withstraat 50', 'postal_code' => '3012 BS', 'city' => 'Rotterdam'],
            ['name' => 'Café De Hoek', 'type' => 'business', 'contact_name' => 'Karim El Amrani', 'email' => 'karim@cafedehoek.nl', 'kvk_number' => '45678901', 'address_line' => 'Marktplein 3', 'postal_code' => '5611 EA', 'city' => 'Eindhoven'],
            ['name' => 'Sophie Wagenaar', 'type' => 'consumer', 'email' => 'sophie.w@example.nl', 'address_line' => 'Linnaeusstraat 22', 'postal_code' => '1093 EJ', 'city' => 'Amsterdam'],
            ['name' => 'NorthStar Consulting', 'type' => 'business', 'contact_name' => 'Erik Lindqvist', 'email' => 'erik@northstar.eu', 'kvk_number' => '56789012', 'vat_number' => 'NL845678901B01', 'address_line' => 'Zuidas Tower 18', 'postal_code' => '1082 MS', 'city' => 'Amsterdam', 'payment_terms' => 14],
            ['name' => 'GreenHands Hoveniersbedrijf', 'type' => 'business', 'contact_name' => 'Hans Groen', 'email' => 'hans@greenhands.nl', 'kvk_number' => '67890123', 'address_line' => 'Tuinweg 5', 'postal_code' => '7311 KK', 'city' => 'Apeldoorn'],
            ['name' => 'Familie de Wit', 'type' => 'consumer', 'email' => 'dewit@example.nl', 'address_line' => 'Beethovenlaan 14', 'postal_code' => '6815 BD', 'city' => 'Arnhem'],
        ];

        $customerModels = [];
        foreach ($customers as $c) {
            $customerModels[] = Customer::create($c);
        }

        // 4. Demo products / services
        $products = [
            ['name' => 'Webdesign basispakket', 'description' => '5 pagina’s, responsive, basis SEO', 'sku' => 'WD-BASIC', 'unit' => 'stuk', 'price' => 1250.00, 'vat_rate' => 21],
            ['name' => 'Logo-ontwerp', 'description' => 'Inclusief 3 concepten en 2 revisierondes', 'sku' => 'BR-LOGO', 'unit' => 'stuk', 'price' => 495.00, 'vat_rate' => 21],
            ['name' => 'Uurtarief design', 'description' => 'Strategisch en visueel ontwerpwerk', 'sku' => 'DSGN-HR', 'unit' => 'uur', 'price' => 95.00, 'vat_rate' => 21],
            ['name' => 'Hosting jaarpakket', 'description' => 'SSL, dagelijkse backups, e-mailaccounts', 'sku' => 'HOST-Y', 'unit' => 'stuk', 'price' => 180.00, 'vat_rate' => 21],
            ['name' => 'Drukwerk visitekaartjes', 'description' => '500 stuks dubbelzijdig, 350gsm', 'sku' => 'PRINT-VK', 'unit' => 'set', 'price' => 89.00, 'vat_rate' => 9],
            ['name' => 'Fotografie reportage', 'description' => 'Halve dag, inclusief nabewerking', 'sku' => 'PHOTO-HD', 'unit' => 'dag', 'price' => 650.00, 'vat_rate' => 21],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $productModels[] = Product::create($p);
        }

        // 5. Demo invoices in various statuses
        $manager = app(InvoiceManager::class);

        // Helper to create an invoice with random lines
        $createInvoice = function (int $customerIdx, array $lineSpecs, int $daysAgo, string $finalStatus) use ($customerModels, $productModels, $manager) {
            $customer = $customerModels[$customerIdx];
            $invoiceDate = now()->subDays($daysAgo);

            $lines = [];
            foreach ($lineSpecs as $spec) {
                $product = $productModels[$spec['p']];
                $lines[] = [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'details' => $product->description,
                    'quantity' => $spec['q'],
                    'unit' => $product->unit,
                    'unit_price' => $product->price,
                    'vat_rate' => $product->vat_rate,
                ];
            }

            $invoice = $manager->create([
                'customer_id' => $customer->id,
                'invoice_date' => $invoiceDate,
                'payment_terms' => $customer->payment_terms ?? 30,
                'lines' => $lines,
            ]);

            if ($finalStatus === 'draft') {
                return $invoice;
            }

            // Send it (sets dates and status)
            $manager->send($invoice);

            // Backdate sent_at to match invoice_date
            $invoice->sent_at = $invoiceDate->copy()->addHours(2);
            $invoice->save();

            if ($finalStatus === 'paid') {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total,
                    'paid_on' => $invoiceDate->copy()->addDays(rand(5, 25)),
                    'method' => 'bank_transfer',
                    'reference' => 'Factuur ' . $invoice->number,
                ]);
            } elseif ($finalStatus === 'partial') {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => round($invoice->total * 0.5, 2),
                    'paid_on' => $invoiceDate->copy()->addDays(10),
                    'method' => 'bank_transfer',
                    'reference' => 'Deelbetaling',
                ]);
            }
            // 'sent' / 'overdue' / 'partial' need no further action; refreshStatus handles it

            return $invoice;
        };

        // Various invoices
        $createInvoice(0, [['p' => 0, 'q' => 1], ['p' => 3, 'q' => 1]], 5, 'sent');
        $createInvoice(1, [['p' => 1, 'q' => 1]], 12, 'paid');
        $createInvoice(3, [['p' => 2, 'q' => 24], ['p' => 1, 'q' => 1]], 22, 'paid');
        $createInvoice(4, [['p' => 5, 'q' => 1]], 8, 'sent');
        $createInvoice(7, [['p' => 2, 'q' => 40]], 45, 'paid');
        $createInvoice(2, [['p' => 0, 'q' => 1]], 38, 'overdue');
        $createInvoice(5, [['p' => 4, 'q' => 2]], 18, 'partial');
        $createInvoice(8, [['p' => 2, 'q' => 16], ['p' => 3, 'q' => 1]], 3, 'sent');
        $createInvoice(9, [['p' => 5, 'q' => 2]], 50, 'overdue');
        $createInvoice(0, [['p' => 2, 'q' => 8]], 1, 'draft');
        $createInvoice(6, [['p' => 1, 'q' => 1], ['p' => 4, 'q' => 1]], 60, 'paid');
        $createInvoice(3, [['p' => 0, 'q' => 1]], 90, 'paid');
        $createInvoice(1, [['p' => 5, 'q' => 1]], 75, 'paid');
        $createInvoice(7, [['p' => 0, 'q' => 1], ['p' => 2, 'q' => 12]], 30, 'partial');
        $createInvoice(4, [['p' => 1, 'q' => 1]], 0, 'draft');

        // Move one overdue to incasso (Armaere)
        $overdue = Invoice::where('status', 'overdue')->where('is_credit', false)->first();
        if ($overdue) {
            app(\App\Services\IncassoService::class)->send($overdue);
        }

        // Create one example credit note for the second invoice (Bakkerij Janssen logo)
        $original = Invoice::where('is_credit', false)->whereNotNull('number')->skip(1)->first();
        if ($original) {
            $credit = app(\App\Services\CreditNoteService::class)->createFromInvoice($original, 'full');
            $credit->update([
                'number' => app(\App\Services\CreditNoteService::class)->nextNumber($credit->company),
                'status' => 'sent',
                'sent_at' => now()->subDays(2),
                'invoice_date' => now()->subDays(2),
                'due_date' => now()->subDays(2),
            ]);
        }

        Auth::logout();

        $this->command->info('✓ Demo company: Vries Design B.V.');
        $this->command->info('✓ Demo login: demo@easyinvoice.test / password');
        $this->command->info('✓ ' . count($customers) . ' klanten, ' . count($products) . ' producten, 15 facturen');
    }
}

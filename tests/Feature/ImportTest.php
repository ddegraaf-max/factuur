<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Overstapwizard: CSV inlezen, kolommen herkennen, klanten/producten/open facturen importeren zonder dubbelen. */
class ImportTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_parser_detects_delimiter_and_suggests_a_mapping_for_a_wefact_export(): void
    {
        $csv = "Bedrijfsnaam;Contactpersoon;E-mailadres;Adres;Huisnummer;Postcode;Plaats;Land;KvK-nummer;BTW-nummer;Telefoon\n"
            . "Bakkerij Jansen;Piet Jansen;piet@bakkerijjansen.nl;Dorpsstraat;12;1234 AB;Bussum;Nederland;12345678;NL001234567B01;035-1234567\n";
        $service = app(ImportService::class);
        $parsed = $service->parse($csv);
        $this->assertSame(';', $parsed['delimiter']);
        $this->assertCount(1, $parsed['rows']);

        $mapping = $service->suggestMapping('customers', $parsed['headers']);
        $this->assertSame(0, $mapping['name']);
        $this->assertSame(2, $mapping['email']);
        $this->assertSame(3, $mapping['address_line']);
        $this->assertSame(4, $mapping['house_number']);
        $this->assertSame(8, $mapping['kvk_number']);
        $this->assertSame(9, $mapping['vat_number']);
    }

    public function test_customers_can_be_imported_through_the_wizard_without_duplicates(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $before = Customer::count();

        $csv = "Naam,E-mail,Straat,Postcode,Woonplaats\nNieuwe Klant BV,info@nieuweklant.nl,Kerkweg 3,5678 CD,Hilversum\nNieuwe Klant BV,info@nieuweklant.nl,Kerkweg 3,5678 CD,Hilversum\nJan de Vries,,Laan 1,1000 AA,Amsterdam\n";
        $file = UploadedFile::fake()->createWithContent('klanten.csv', $csv);

        $this->post(route('import.preview'), ['type' => 'customers', 'file' => $file])->assertRedirect(route('import.index'));
        $preview = session('import_preview');
        $this->assertSame(3, $preview['total']);
        $this->assertSame(0, $preview['mapping']['name']);

        $this->post(route('import.commit'), ['token' => $preview['token'], 'mapping' => $preview['mapping']])->assertRedirect(route('import.index'));
        $result = session('import_result');
        $this->assertSame(2, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame($before + 2, Customer::count());

        $customer = Customer::where('email', 'info@nieuweklant.nl')->firstOrFail();
        $this->assertSame('business', $customer->type);
        $this->assertSame('Kerkweg 3', $customer->address_line);
        $this->assertSame('consumer', Customer::where('name', 'Jan de Vries')->firstOrFail()->type);
    }

    public function test_products_and_open_invoices_import(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $products = "Productnaam;Prijs excl. btw;Btw;Eenheid\nConsult;\"€ 125,00\";21;uur\nKoffie;2,50;9;stuk\n";
        $service = app(ImportService::class);
        $parsed = $service->parse($products);
        $result = $service->import($user->company, 'products', $parsed['rows'], $service->suggestMapping('products', $parsed['headers']));
        $this->assertSame(2, $result['created']);
        $this->assertEquals(125.0, (float) Product::where('name', 'Consult')->firstOrFail()->price);
        $this->assertSame(9, (int) Product::where('name', 'Koffie')->firstOrFail()->vat_rate);

        $invoices = "Factuurnummer;Klant;Factuurdatum;Vervaldatum;Totaal incl. btw;Btw;Betaald\nOUD-2026-001;Overgestapte Klant;01-07-2026;15-07-2026;1.210,00;21;0\nOUD-2026-002;Overgestapte Klant;10-08-2026;24-08-2026;605,00;21;605,00\n";
        $parsed = $service->parse($invoices);
        $result = $service->import($user->company, 'invoices', $parsed['rows'], $service->suggestMapping('invoices', $parsed['headers']));
        $this->assertSame(2, $result['created'], implode(' | ', $result['errors']));

        $open = Invoice::where('number', 'OUD-2026-001')->firstOrFail();
        $this->assertSame('overdue', $open->status);
        $this->assertEquals(1000.0, (float) $open->subtotal);
        $this->assertEquals(210.0, (float) $open->vat_total);
        $this->assertSame(1, $open->lines()->count());
        $this->assertSame('paid', Invoice::where('number', 'OUD-2026-002')->firstOrFail()->status);
        $this->assertSame(1, Customer::where('name', 'Overgestapte Klant')->count());

        // Nog een keer: alles overgeslagen, geen dubbele facturen.
        $again = $service->import($user->company, 'invoices', $parsed['rows'], $service->suggestMapping('invoices', $parsed['headers']));
        $this->assertSame(0, $again['created']);
    }
}

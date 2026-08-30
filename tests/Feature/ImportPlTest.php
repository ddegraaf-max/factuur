<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ImportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Overstapwizard voor de Poolse markt (merk lopra_pl): Poolse kopregels uit
 * Fakturownia/iFirma/wFirma/inFakt herkennen (met diakrieten), NIP → vat_number,
 * REGON → kvk_number, btw 23/8/5/0 met "23%", "23 %" en "zw", bedragen als
 * "1 234,56" en datums als 12.09.2026 / 2026-09-12 / 12-09-2026.
 */
class ImportPlTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'lopra_pl']);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/../fixtures/import-pl/' . $name);
    }

    public function test_polish_headers_with_diacritics_are_normalised_and_mapped(): void
    {
        $service = app(ImportService::class);
        $parsed = $service->parse($this->fixture('klienci.csv'));
        $this->assertSame(';', $parsed['delimiter']);
        $this->assertCount(4, $parsed['rows']);

        // "Miejscowość", "Termin płatności (dni)" → miejscowosc, terminplatnoscidni.
        $this->assertSame([
            'name' => 0, 'contact_name' => 1, 'email' => 2, 'phone' => 3, 'address_line' => 4, 'postal_code' => 5,
            'city' => 6, 'country' => 7, 'kvk_number' => 9, 'vat_number' => 8, 'payment_terms' => 10, 'notes' => 11,
        ], $service->suggestMapping('customers', $parsed['headers']));

        $mapping = $service->suggestMapping('invoices', ['Numer faktury', 'Kontrahent', 'Data wystawienia', 'Data sprzedaży', 'Termin płatności', 'Netto', 'VAT', 'Wartość brutto', 'Stawka VAT', 'Zapłacono', 'Uwagi']);
        $this->assertSame(0, $mapping['number']);
        $this->assertSame(1, $mapping['customer']);
        $this->assertSame(2, $mapping['date'], '"Data wystawienia" wint van "Data sprzedaży"');
        $this->assertSame(4, $mapping['due_date']);
        $this->assertSame(7, $mapping['total']);
        $this->assertSame(8, $mapping['vat_rate'], '"Stawka VAT" wint van de kolom "VAT" (het btw-bedrag)');
        $this->assertSame(9, $mapping['paid']);
        $this->assertSame(10, $mapping['reference']);
        $this->assertArrayNotHasKey('vat_number', $mapping);

        $this->assertSame(
            ['name' => 0, 'description' => 1, 'sku' => 2, 'unit' => 3, 'price' => 4, 'vat_rate' => 5],
            $service->suggestMapping('products', ['Nazwa towaru', 'Opis', 'Kod produktu', 'J.m.', 'Cena jednostkowa netto', 'Stawka VAT'])
        );

        // Nederlandse koppen blijven in de Poolse markt gewoon werken.
        $mapping = $service->suggestMapping('customers', ['Bedrijfsnaam', 'E-mailadres', 'KvK-nummer', 'BTW-nummer']);
        $this->assertSame(['name' => 0, 'email' => 1, 'kvk_number' => 2, 'vat_number' => 3], $mapping);
    }

    public function test_customers_import_with_nip_regon_and_polish_defaults(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $service = app(ImportService::class);

        $parsed = $service->parse($this->fixture('klienci.csv'));
        $result = $service->import($user->company, 'customers', $parsed['rows'], $service->suggestMapping('customers', $parsed['headers']));
        $this->assertSame(3, $result['created'], implode(' | ', $result['errors']));
        $this->assertSame(1, $result['skipped'], 'de dubbele Piekarnia-regel wordt overgeslagen');

        $piekarnia = Customer::where('name', 'Piekarnia Kowalski Sp. z o.o.')->firstOrFail();
        $this->assertSame('business', $piekarnia->type);
        $this->assertSame('5252248481', $piekarnia->vat_number, 'NIP zonder streepjes in vat_number');
        $this->assertSame('146830300', $piekarnia->kvk_number, 'REGON in kvk_number');
        $this->assertSame('PL', $piekarnia->country);
        $this->assertSame('pl', $piekarnia->language);
        $this->assertSame('Jan Kowalski', $piekarnia->contact_name);
        $this->assertSame('jan@piekarnia-kowalski.pl', $piekarnia->email);
        $this->assertSame('+48 22 123 45 67', $piekarnia->phone);
        $this->assertSame('ul. Długa 12/3', $piekarnia->address_line);
        $this->assertSame('00-238', $piekarnia->postal_code);
        $this->assertSame('Warszawa', $piekarnia->city);
        $this->assertSame(14, (int) $piekarnia->payment_terms);
        $this->assertStringContainsString('Stały klient', (string) $piekarnia->notes);

        $anna = Customer::where('name', 'Anna Wiśniewska')->firstOrFail();
        $this->assertSame('consumer', $anna->type);
        $this->assertSame('PL', $anna->country);
        $this->assertSame('Kraków', $anna->city);
        $this->assertNull($anna->vat_number);
        $this->assertNull($anna->kvk_number);

        $blysk = Customer::where('name', 'Zakład Usług Elektrycznych "Błysk" s.c.')->firstOrFail();
        $this->assertSame('business', $blysk->type);
        $this->assertSame('7792442093', $blysk->vat_number);
        $this->assertSame(30, (int) $blysk->payment_terms, '"30 dni" → 30');
    }

    public function test_products_import_with_polish_amounts_and_vat_codes(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $service = app(ImportService::class);

        $parsed = $service->parse($this->fixture('produkty.csv'));
        $mapping = $service->suggestMapping('products', $parsed['headers']);
        $this->assertSame(2, $mapping['sku'], '"Symbol" is het artikelnummer, niet "PKWiU"');
        $result = $service->import($user->company, 'products', $parsed['rows'], $mapping);
        $this->assertSame(4, $result['created'], implode(' | ', $result['errors']));

        $abonament = Product::where('sku', 'USL-001')->firstOrFail();
        $this->assertSame('Usługa księgowa – abonament miesięczny', $abonament->name);
        $this->assertEquals(1234.56, (float) $abonament->price, '"1 234,56" met spatie als duizendtal');
        $this->assertSame(23, (int) $abonament->vat_rate, '"23%"');
        $this->assertSame('m-c', $abonament->unit);
        $this->assertSame('Prowadzenie KPiR i deklaracje VAT', $abonament->description);
        $this->assertSame(23, (int) Product::where('sku', 'USL-002')->firstOrFail()->vat_rate, '"23 %"');
        $this->assertSame(0, (int) Product::where('sku', 'SZK-001')->firstOrFail()->vat_rate, '"zw" (zwolniony) → 0');
        $this->assertSame(5, (int) Product::where('sku', 'KS-001')->firstOrFail()->vat_rate, '5% op boeken');

        // Zonder eenheid/btw: standaard van de markt (szt., 23%); "np" → 0; vaste spatie (U+00A0) als duizendtal.
        $parsed = $service->parse("Nazwa;Cena;VAT\nDostawa kurierska;1\u{00A0}234,56;\nUsługa eksportowa;100,00;np\nNocleg w hotelu;200,00 zł;8\n");
        $result = $service->import($user->company, 'products', $parsed['rows'], $service->suggestMapping('products', $parsed['headers']));
        $this->assertSame(3, $result['created'], implode(' | ', $result['errors']));
        $dostawa = Product::where('name', 'Dostawa kurierska')->firstOrFail();
        $this->assertSame('szt.', $dostawa->unit);
        $this->assertSame(23, (int) $dostawa->vat_rate);
        $this->assertEquals(1234.56, (float) $dostawa->price);
        $this->assertSame(0, (int) Product::where('name', 'Usługa eksportowa')->firstOrFail()->vat_rate);
        $nocleg = Product::where('name', 'Nocleg w hotelu')->firstOrFail();
        $this->assertSame(8, (int) $nocleg->vat_rate);
        $this->assertEquals(200.0, (float) $nocleg->price, '"200,00 zł"');
    }

    public function test_open_invoices_import_with_polish_dates_amounts_and_vat(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $service = app(ImportService::class);

        $parsed = $service->parse($this->fixture('klienci.csv'));
        $service->import($user->company, 'customers', $parsed['rows'], $service->suggestMapping('customers', $parsed['headers']));

        $parsed = $service->parse($this->fixture('faktury.csv'));
        $this->assertSame('fakturownia', ImportService::detectPackage($parsed['headers']));
        $mapping = $service->suggestMapping('invoices', $parsed['headers']);
        $this->assertSame(0, $mapping['number']);
        $this->assertSame(1, $mapping['date']);
        $this->assertSame(3, $mapping['due_date']);
        $this->assertSame(4, $mapping['customer']);
        $this->assertSame(5, $mapping['vat_number']);
        $this->assertSame(6, $mapping['email']);
        $this->assertSame(9, $mapping['total']);
        $this->assertSame(10, $mapping['vat_rate']);
        $this->assertSame(11, $mapping['paid']);
        $this->assertSame(12, $mapping['reference']);

        $result = $service->import($user->company, 'invoices', $parsed['rows'], $mapping);
        $this->assertSame(3, $result['created'], implode(' | ', $result['errors']));

        $open = Invoice::where('number', 'FV/2026/08/0012')->firstOrFail();
        $this->assertSame('2026-08-12', Carbon::parse($open->invoice_date)->toDateString(), '12.08.2026');
        $this->assertSame('2026-08-26', Carbon::parse($open->due_date)->toDateString(), '26.08.2026');
        $this->assertSame('overdue', $open->status);
        $this->assertEquals(1230.0, (float) $open->total, '"1 230,00"');
        $this->assertEquals(1000.0, (float) $open->subtotal);
        $this->assertEquals(230.0, (float) $open->vat_total);
        $this->assertSame(23, (int) $open->lines()->firstOrFail()->vat_rate);
        $this->assertSame('szt.', $open->lines()->firstOrFail()->unit);
        $this->assertSame('pl', $open->language);
        $this->assertSame('Abonament sierpień 2026', $open->reference);
        $this->assertSame('5252248481', $open->customer_vat_number);
        $this->assertSame(1, Customer::where('name', 'Piekarnia Kowalski Sp. z o.o.')->count(), 'bestaande klant hergebruikt, niet gedubbeld');

        $partial = Invoice::where('number', 'FV/2026/09/0003')->firstOrFail();
        $this->assertSame('2026-09-12', Carbon::parse($partial->invoice_date)->toDateString(), '2026-09-12');
        $this->assertSame('2026-09-26', Carbon::parse($partial->due_date)->toDateString());
        $this->assertSame('partial', $partial->status);
        $this->assertEquals(2500.0, (float) $partial->total, '"2 500,00"');
        $this->assertEquals(2032.52, (float) $partial->subtotal);
        $this->assertEquals(467.48, (float) $partial->vat_total);
        $this->assertEquals(500.0, (float) $partial->paid_total);
        $this->assertSame(1, Payment::where('invoice_id', $partial->id)->count());
        $nowy = Customer::where('name', 'Nowy Kontrahent S.A.')->firstOrFail();
        $this->assertSame('1234563218', $nowy->vat_number, 'NIP nabywcy → vat_number van de aangemaakte klant');
        $this->assertSame('PL', $nowy->country);
        $this->assertSame('pl', $nowy->language);
        $this->assertSame('business', $nowy->type);

        $paid = Invoice::where('number', 'FV/2026/09/0004')->firstOrFail();
        $this->assertSame('2026-09-12', Carbon::parse($paid->invoice_date)->toDateString(), '12-09-2026');
        $this->assertSame('2026-09-19', Carbon::parse($paid->due_date)->toDateString());
        $this->assertSame('paid', $paid->status);
        $this->assertEquals(350.0, (float) $paid->subtotal, '"zw" → 0% btw');
        $this->assertEquals(0.0, (float) $paid->vat_total);
        $this->assertSame(0, (int) $paid->lines()->firstOrFail()->vat_rate);

        // Nog een keer: alles overgeslagen, geen dubbele facturen.
        $again = $service->import($user->company, 'invoices', $parsed['rows'], $mapping);
        $this->assertSame(0, $again['created']);
        $this->assertSame(3, $again['skipped']);
    }

    public function test_detect_package_recognises_typical_exports_conservatively(): void
    {
        $this->assertSame('fakturownia', ImportService::detectPackage(['Numer', 'Data wystawienia', 'Nabywca', 'NIP nabywcy', 'Brutto']));
        $this->assertSame('fakturownia', ImportService::detectPackage(['number', 'buyer_name', 'buyer_tax_no', 'price_gross']));
        $this->assertSame('infakt', ImportService::detectPackage(['Numer', 'Klient', 'NIP', 'Kwota netto', 'Kwota brutto']));
        $this->assertSame('wfirma', ImportService::detectPackage(['Numer', 'Kontrahent', 'Wartość netto', 'Wartość brutto', 'Pozostało do zapłaty']));
        $this->assertSame('wfirma', ImportService::detectPackage(['ID wFirma', 'Nazwa', 'NIP']));
        $this->assertSame('ifirma', ImportService::detectPackage(['Nr faktury', 'Kontrahent', 'Netto', 'Brutto', 'Do zapłaty']));
        $this->assertSame('ifirma', ImportService::detectPackage(['Nazwa', 'Identyfikator iFirma']));
        $this->assertSame('wefact', ImportService::detectPackage(['Debiteurcode', 'Bedrijfsnaam', 'E-mailadres']));
        $this->assertSame('wefact', ImportService::detectPackage(['Bedrijfsnaam', 'Contactpersoon', 'E-mailadres', 'Adres', 'Huisnummer', 'Postcode', 'Plaats', 'Land', 'KvK-nummer', 'BTW-nummer', 'Telefoon']));
        $this->assertSame('moneybird', ImportService::detectPackage(['Bedrijfsnaam', 'Voornaam', 'Achternaam', 'Adres 1', 'Klantnummer']));
        $this->assertSame('e-boekhouden', ImportService::detectPackage(['Relatiecode', 'Bedrijf', 'Contactpersoon']));

        // Generieke koppen: geen gok.
        $this->assertNull(ImportService::detectPackage(['Naam', 'E-mail', 'Plaats']));
        $this->assertNull(ImportService::detectPackage(['Nazwa', 'NIP', 'Ulica', 'Miasto']));
        $this->assertNull(ImportService::detectPackage([]));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Ksef\FaXmlBuilder;
use App\Services\VatCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * KSeF (Poolse markt): FA-XML per factuur downloaden en het KSeF-nummer opslaan.
 * Het XML moet well-formed zijn, in de FA(2)-namespace staan en per btw-tarief
 * sluiten op de factuurregels.
 */
class KsefTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_fa_xml_download_contains_the_invoice_and_marks_it_exported(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $user = $this->polishUser();
        $invoice = $this->polishInvoice($user, [
            ['Usługa programistyczna', 2, 100, 23],
            ['Usługa hotelowa', 1, 50, 8],
            ['Książka', 3, 20, 5],
            ['Eksport towarów', 1, 30, 0],
        ]);

        $response = $this->actingAs($user)->get(route('ksef.xml', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('faktura-FV-2026-001-ksef.xml', (string) $response->headers->get('content-disposition'));

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML((string) $response->getContent()), 'geen geldige XML');
        $this->assertSame('Faktura', $doc->documentElement->localName);
        $this->assertSame(FaXmlBuilder::SCHEMA_NAMESPACE, $doc->documentElement->namespaceURI);

        $xp = $this->xpath($doc);
        $this->assertSame('FA', $xp->evaluate('string(/f:Faktura/f:Naglowek/f:KodFormularza)'));
        $this->assertSame(FaXmlBuilder::SCHEMA_CODE, $xp->evaluate('string(/f:Faktura/f:Naglowek/f:KodFormularza/@kodSystemowy)'));
        $this->assertSame(FaXmlBuilder::SCHEMA_VARIANT, $xp->evaluate('string(/f:Faktura/f:Naglowek/f:WariantFormularza)'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $xp->evaluate('string(/f:Faktura/f:Naglowek/f:DataWytworzeniaFa)'));

        $this->assertSame('5261040828', $xp->evaluate('string(/f:Faktura/f:Podmiot1/f:DaneIdentyfikacyjne/f:NIP)'));
        $this->assertSame('PL', $xp->evaluate('string(/f:Faktura/f:Podmiot1/f:Adres/f:KodKraju)'));
        $this->assertSame('1234563218', $xp->evaluate('string(/f:Faktura/f:Podmiot2/f:DaneIdentyfikacyjne/f:NIP)'));

        $this->assertSame('PLN', $xp->evaluate('string(/f:Faktura/f:Fa/f:KodWaluty)'));
        $this->assertSame($invoice->number, $xp->evaluate('string(/f:Faktura/f:Fa/f:P_2)'));
        $this->assertSame($invoice->invoice_date->format('Y-m-d'), $xp->evaluate('string(/f:Faktura/f:Fa/f:P_1)'));
        $this->assertSame(number_format((float) $invoice->total, 2, '.', ''), $xp->evaluate('string(/f:Faktura/f:Fa/f:P_15)'));
        $this->assertSame('393.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_15)'));
        $this->assertSame('VAT', $xp->evaluate('string(/f:Faktura/f:Fa/f:RodzajFaktury)'));

        // Sommen per tarief, berekend uit de regels.
        $this->assertSame('200.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_13_1)'));
        $this->assertSame('46.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_14_1)'));
        $this->assertSame('50.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_13_2)'));
        $this->assertSame('4.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_14_2)'));
        $this->assertSame('60.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_13_3)'));
        $this->assertSame('3.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_14_3)'));
        $this->assertSame('30.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_13_6_1)'));
        $this->assertSame(0, $xp->query('/f:Faktura/f:Fa/f:P_13_7')->length);

        $rows = $xp->query('/f:Faktura/f:Fa/f:FaWiersz');
        $this->assertSame($invoice->lines()->count(), $rows->length);
        $this->assertSame('1', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[1]/f:NrWierszaFa)'));
        $this->assertSame('Usługa programistyczna', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[1]/f:P_7)'));
        $this->assertSame('2', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[1]/f:P_8B)'));
        $this->assertSame('100.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[1]/f:P_9A)'));
        $this->assertSame('200.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[1]/f:P_11)'));
        $this->assertSame('23', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[1]/f:P_12)'));
        $this->assertSame('0', $xp->evaluate('string(/f:Faktura/f:Fa/f:FaWiersz[4]/f:P_12)'));

        $this->assertSame($invoice->due_date->format('Y-m-d'), $xp->evaluate('string(/f:Faktura/f:Fa/f:Platnosc/f:TerminPlatnosci/f:Termin)'));
        $this->assertSame('6', $xp->evaluate('string(/f:Faktura/f:Fa/f:Platnosc/f:FormaPlatnosci)'));
        $this->assertSame('PL61109010140000071219812874', $xp->evaluate('string(/f:Faktura/f:Fa/f:Platnosc/f:RachunekBankowy/f:NrRB)'));

        $invoice->refresh();
        $this->assertSame('exported', $invoice->ksef_status);
        $this->assertNotNull($invoice->ksef_sent_at);
    }

    public function test_credit_note_becomes_a_kor_invoice_that_refers_to_the_original(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $user = $this->polishUser();
        $original = $this->polishInvoice($user, [['Usługa', 1, 100, 23]]);
        $credit = $this->polishInvoice($user, [['Korekta: za dużo godzin', 1, -40, 23]], [
            'number' => 'FK/2026/001',
            'is_credit' => true,
            'credits_invoice_id' => $original->id,
            'notes' => 'Pomyłka w liczbie godzin',
        ]);

        $xp = $this->xpath($this->xmlFor($user, $credit));
        $this->assertSame('KOR', $xp->evaluate('string(/f:Faktura/f:Fa/f:RodzajFaktury)'));
        $this->assertSame('-49.20', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_15)'));
        $this->assertSame('-40.00', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_13_1)'));
        $this->assertSame('-9.20', $xp->evaluate('string(/f:Faktura/f:Fa/f:P_14_1)'));
        $this->assertSame('Pomyłka w liczbie godzin', $xp->evaluate('string(/f:Faktura/f:Fa/f:PrzyczynaKorekty)'));
        $this->assertSame($original->number, $xp->evaluate('string(/f:Faktura/f:Fa/f:DaneFaKorygowanej/f:NrFaKorygowanej)'));
        $this->assertSame($original->invoice_date->format('Y-m-d'), $xp->evaluate('string(/f:Faktura/f:Fa/f:DaneFaKorygowanej/f:DataWystFaKorygowanej)'));
        $this->assertSame('1', $xp->evaluate('string(/f:Faktura/f:Fa/f:DaneFaKorygowanej/f:NrKSeFN)'));

        // Heeft het origineel al een KSeF-nummer, dan verwijst de correctie daarnaar.
        $original->forceFill(['ksef_number' => '5261040828-20260830-A1B2C3-D4E5F6-AB', 'ksef_status' => 'accepted'])->save();
        $xp = $this->xpath($this->xmlFor($user, $credit));
        $this->assertSame('1', $xp->evaluate('string(/f:Faktura/f:Fa/f:DaneFaKorygowanej/f:NrKSeF)'));
        $this->assertSame('5261040828-20260830-A1B2C3-D4E5F6-AB', $xp->evaluate('string(/f:Faktura/f:Fa/f:DaneFaKorygowanej/f:NrKSeFFaKorygowanej)'));
    }

    public function test_customer_without_nip_gets_brakid_and_drafts_are_refused(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $user = $this->polishUser();
        $invoice = $this->polishInvoice($user, [['Usługa', 1, 100, 23]], ['customer_vat_number' => null]);

        $xp = $this->xpath($this->xmlFor($user, $invoice));
        $this->assertSame('1', $xp->evaluate('string(/f:Faktura/f:Podmiot2/f:DaneIdentyfikacyjne/f:BrakID)'));
        $this->assertSame(0, $xp->query('/f:Faktura/f:Podmiot2/f:DaneIdentyfikacyjne/f:NIP')->length);

        $draft = $this->polishInvoice($user, [['Usługa', 1, 100, 23]], ['number' => 'FV/2026/002', 'status' => 'draft']);
        $this->actingAs($user)->get(route('ksef.xml', $draft))->assertStatus(422);
        $this->assertNull($draft->fresh()->ksef_status);
    }

    public function test_ksef_number_is_validated_and_stored(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $user = $this->polishUser();
        $invoice = $this->polishInvoice($user, [['Usługa', 1, 100, 23]]);

        $this->actingAs($user)->patch(route('ksef.number', $invoice), ['ksef_number' => 'abc'])
            ->assertSessionHasErrors('ksef_number');
        $this->assertNull($invoice->fresh()->ksef_number);

        $this->actingAs($user)->patch(route('ksef.number', $invoice), ['ksef_number' => ' 5261040828-20260830-a1b2c3-d4e5f6-ab '])
            ->assertSessionHas('flash');

        $invoice->refresh();
        $this->assertSame('5261040828-20260830-A1B2C3-D4E5F6-AB', $invoice->ksef_number);
        $this->assertSame('accepted', $invoice->ksef_status);
        $this->assertNotNull($invoice->ksef_sent_at);
    }

    public function test_ksef_routes_are_hidden_in_the_dutch_market(): void
    {
        $user = $this->demoUser();
        $invoice = Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)->where('status', '!=', 'draft')->first();

        $this->actingAs($user)->get(route('ksef.xml', $invoice))->assertNotFound();
        $this->actingAs($user)->patch(route('ksef.number', $invoice), ['ksef_number' => '5261040828-20260830-A1B2C3-D4E5F6-AB'])->assertNotFound();
        $this->assertNull($invoice->fresh()->ksef_number);
    }

    /* ---------------------------------------------------------------- */

    /** Demo-administratie omgebouwd tot een Poolse onderneming (NIP, PLN, Pools IBAN). */
    private function polishUser(): User
    {
        $user = $this->demoUser();
        $user->company->forceFill([
            'name' => 'Lopra Testowa Sp. z o.o.',
            'vat_number' => 'PL 526-104-08-28',
            'country' => 'PL',
            'currency' => 'PLN',
            'address_line' => 'ul. Świętokrzyska 12',
            'postal_code' => '00-916',
            'city' => 'Warszawa',
            'iban' => 'PL61 1090 1014 0000 0712 1981 2874',
        ])->save();

        return $user->fresh();
    }

    /** Factuur met regels [omschrijving, aantal, stuksprijs, btw%] voor de Poolse administratie. */
    private function polishInvoice(User $user, array $lines, array $attributes = []): Invoice
    {
        $customer = Customer::withoutGlobalScopes()->where('company_id', $user->company_id)->orderBy('id')->firstOrFail();
        $vat = app(VatCalculator::class);

        $subtotal = $vatTotal = 0.0;
        $breakdown = [];
        $calculated = [];
        foreach ($lines as $i => [$description, $qty, $price, $rate]) {
            $calc = $vat->calculateLine((float) $qty, (float) $price, (float) $rate);
            $subtotal += $calc['subtotal'];
            $vatTotal += $calc['vat'];
            $breakdown[(string) $rate] = round(($breakdown[(string) $rate] ?? 0) + $calc['vat'], 2);
            $calculated[] = ['sort_order' => $i, 'description' => $description, 'quantity' => $qty, 'unit' => 'szt.', 'unit_price' => $price, 'vat_rate' => $rate, 'line_subtotal' => $calc['subtotal'], 'line_vat' => $calc['vat'], 'line_total' => $calc['total']];
        }

        $invoice = Invoice::create(array_merge([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'number' => 'FV/2026/001',
            'status' => 'sent',
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->addDays(4),
            'payment_terms' => 14,
            'customer_name' => 'Klient Testowy S.A.',
            'customer_address_line' => 'ul. Długa 5',
            'customer_postal_code' => '31-147',
            'customer_city' => 'Kraków',
            'customer_country' => 'PL',
            'customer_vat_number' => '123-456-32-18',
            'subtotal' => round($subtotal, 2),
            'vat_total' => round($vatTotal, 2),
            'total' => round($subtotal + $vatTotal, 2),
            'paid_total' => 0,
            'vat_breakdown' => $breakdown,
        ], $attributes));

        foreach ($calculated as $line) {
            $invoice->lines()->create($line);
        }

        return $invoice->fresh();
    }

    private function xmlFor(User $user, Invoice $invoice): \DOMDocument
    {
        $xml = (string) $this->actingAs($user)->get(route('ksef.xml', $invoice))->assertOk()->getContent();
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'geen geldige XML');

        return $doc;
    }

    private function xpath(\DOMDocument $doc): \DOMXPath
    {
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('f', FaXmlBuilder::SCHEMA_NAMESPACE);

        return $xp;
    }
}

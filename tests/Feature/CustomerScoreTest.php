<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerCheck;
use App\Models\Invoice;
use App\Models\ReminderLog;
use App\Services\Checks\InsolvencyService;
use App\Services\CustomerScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Klantscore (1.71.0): het betaalgedrag in de eigen administratie plus de
 * openbare bronnen (btw-nummer via VIES, Handelsregister via de KvK-API,
 * insolventieregister via de webservice van de Rechtspraak) worden één
 * indicatie van 0 tot 100 met een letter, met de signalen erbij.
 */
class CustomerScoreTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const CIR_LIST = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"><soap:Body><searchUndertakingResponse xmlns="http://www.rechtspraak.nl/namespaces/cir01"><searchUndertakingResult><publicatieLijst xmlns="http://www.rechtspraak.nl/namespaces/inspubber01"><publicatieKenmerk>01.dha.11.7788.F.1300.1.17</publicatieKenmerk></publicatieLijst></searchUndertakingResult></searchUndertakingResponse></soap:Body></soap:Envelope>';

    private const CIR_EMPTY = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"><soap:Body><searchUndertakingResponse xmlns="http://www.rechtspraak.nl/namespaces/cir01"><searchUndertakingResult><publicatieLijst xmlns="http://www.rechtspraak.nl/namespaces/inspubber01"></publicatieLijst></searchUndertakingResult></searchUndertakingResponse></soap:Body></soap:Envelope>';

    private const CIR_CASE = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"><soap:Body><getCaseResponse xmlns="http://www.rechtspraak.nl/namespaces/cir01"><getCaseResult><inspubWebserviceInsolvente xmlns="http://www.rechtspraak.nl/namespaces/inspubber01"><insolvente><insolventienummer>F.01/11/7788</insolventienummer><behandelendeInstantieNaam>Rechtbank Den Haag</behandelendeInstantieNaam><persoon><rechtspersoonlijkheid>rechtspersoon</rechtspersoonlijkheid><achternaam>DUIF Psychiatrie</achternaam><KvKNummer>11998877</KvKNummer></persoon><publicatiegeschiedenis><publicatie><publicatieDatum>2017-12-04</publicatieDatum><publicatieKenmerk>01.dha.11.7788.F.1300.1.17</publicatieKenmerk><publicatieOmschrijving>Uitspraak faillissement op 01 november 2017</publicatieOmschrijving><publicatieSoortCode>1300</publicatieSoortCode></publicatie></publicatiegeschiedenis></insolvente></inspubWebserviceInsolvente></getCaseResult></getCaseResponse></soap:Body></soap:Envelope>';

    private function fakeSources(bool $bankrupt = false, bool $vatValid = true, ?string $endedOn = null): void
    {
        config([
            'services.cir.username' => 'proef', 'services.cir.password' => 'geheim',
            'services.kvk.key' => 'test', 'services.kvk.base' => 'https://api.kvk.nl/test',
        ]);
        Http::fake([
            'ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response(['valid' => $vatValid, 'name' => 'DE VRIES BOUW B.V.', 'address' => 'DORPSSTRAAT 1 3441AB WOERDEN'], 200),
            'api.kvk.nl/test/api/v1/basisprofielen/*' => Http::response([
                'kvkNummer' => '11998877', 'naam' => 'De Vries Bouw B.V.', 'formeleRegistratiedatum' => '20120519',
                'materieleRegistratie' => array_filter(['datumAanvang' => '20120519', 'datumEinde' => $endedOn]),
                'totaalWerkzamePersonen' => 4,
                '_embedded' => ['eigenaar' => ['rechtsvorm' => 'Besloten vennootschap', 'uitgebreideRechtsvorm' => 'Besloten vennootschap met gewone structuur']],
            ], 200),
            'webservice.rechtspraak.nl/*' => $bankrupt
                ? Http::sequence()
                    ->push(self::CIR_LIST, 200, ['Content-Type' => 'application/soap+xml'])
                    ->push(self::CIR_CASE, 200, ['Content-Type' => 'application/soap+xml'])
                    ->whenEmpty(Http::response(self::CIR_EMPTY, 200))
                : Http::response(self::CIR_EMPTY, 200, ['Content-Type' => 'application/soap+xml']),
        ]);
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'De Vries Bouw B.V.', 'type' => 'business', 'email' => 'info@devries.test',
            'kvk_number' => '11998877', 'vat_number' => 'NL001234567B01',
            'address_line' => 'Dorpsstraat 1', 'postal_code' => '3441 AB', 'city' => 'Woerden', 'country' => 'NL',
        ], $attributes));
    }

    private function invoice(Customer $customer, string $status, int $dueDaysAgo, ?int $paidDaysAfterDue = null, float $total = 500): Invoice
    {
        $due = now()->subDays($dueDaysAgo)->startOfDay();

        return Invoice::create([
            'customer_id' => $customer->id, 'customer_name' => $customer->name, 'customer_email' => $customer->email,
            'number' => 'T-' . random_int(1000, 999999), 'status' => $status,
            'invoice_date' => $due->copy()->subDays(14)->toDateString(), 'due_date' => $due->toDateString(), 'payment_terms' => 14,
            'subtotal' => $total, 'vat_total' => 0, 'total' => $total, 'paid_total' => $status === 'paid' ? $total : 0,
            'paid_at' => $paidDaysAfterDue !== null ? $due->copy()->addDays($paidDaysAfterDue)->setTime(12, 0) : null,
            'language' => 'nl',
        ]);
    }

    public function test_a_good_payer_with_clean_sources_scores_an_a(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeSources();
        $customer = $this->customer();
        foreach ([40, 70, 100] as $ago) {
            $this->invoice($customer, 'paid', $ago, -2);
        }

        $score = app(CustomerScoreService::class)->score($customer);

        $this->assertSame(100, $score['score']);
        $this->assertSame('A', $score['grade']);
        $this->assertSame('business', $score['kind']);
        $labels = array_column($score['signals'], 'label');
        $this->assertContains('Alles binnen de termijn betaald', $labels);
        $this->assertContains('Btw-nummer geldig', $labels);
        $this->assertContains('Niet in het insolventieregister', $labels);
        $this->assertSame(['ok', 'ok', 'ok'], array_column($score['sources'], 'status'));
        $this->assertStringContainsString('Al 14 jaar', implode(' ', $labels));

        // Bewaard, en de bronnen zijn een week goed: opnieuw laden bevraagt ze niet nog eens.
        $this->assertSame(1, CustomerCheck::count());
        Http::assertSentCount(3);
        app(CustomerScoreService::class)->score($customer);
        Http::assertSentCount(3);

        // De klantpagina toont de score; opnieuw controleren bevraagt de bronnen wel weer.
        $this->get(route('customers.show', $customer))->assertOk()->assertInertia(fn ($page) => $page
            ->where('score.grade', 'A')->where('score.score', 100)->has('score.signals')->has('score.sources', 3));
        $this->post(route('customers.score', $customer))->assertRedirect()->assertSessionHas('flash');
        Http::assertSentCount(6);
    }

    public function test_late_payment_reminders_and_arrears_cost_points(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeSources();
        $customer = $this->customer();
        $this->invoice($customer, 'paid', 90, 20);
        $this->invoice($customer, 'paid', 60, 30);
        $overdue = $this->invoice($customer, 'overdue', 45, null, 1200);
        ReminderLog::create(['company_id' => $user->company_id, 'invoice_id' => $overdue->id, 'type' => 'Herinnering', 'kind' => 'reminder', 'channel' => 'email', 'sent_to' => 'x@y.test', 'amount_open' => 1200, 'sent_at' => now()->subDays(20)]);
        ReminderLog::create(['company_id' => $user->company_id, 'invoice_id' => $overdue->id, 'type' => 'Herinnering', 'kind' => 'reminder', 'channel' => 'email', 'sent_to' => 'x@y.test', 'amount_open' => 1200, 'sent_at' => now()->subDays(10)]);

        $score = app(CustomerScoreService::class)->score($customer);

        // 100 - 20 (gemiddeld 25 dagen te laat) - 10 (alles te laat) - 18 (45 dagen achterstand) - 6 (twee herinneringen) = 46
        $this->assertSame(46, $score['score']);
        $this->assertSame('D', $score['grade']);
        $this->assertSame('Risico', $score['label']);
        $impacts = collect($score['signals'])->pluck('impact', 'label');
        $this->assertSame(-20, $impacts['2 betaalde facturen, gemiddeld 25 dagen na de vervaldatum betaald']);
        $this->assertSame(-10, $impacts['Meer dan de helft van de facturen te laat betaald']);
        $this->assertSame(-6, $impacts['2 herinneringen in de laatste twaalf maanden']);
        $this->assertSame(45, $score['history']['max_days_overdue']);
    }

    public function test_a_bankruptcy_in_the_register_weighs_heaviest(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeSources(bankrupt: true);
        $customer = $this->customer();
        $this->invoice($customer, 'paid', 40, 0);

        $score = app(CustomerScoreService::class)->score($customer);

        $this->assertSame('E', $score['grade']);
        $this->assertSame(30, $score['score'], '100 - 70 voor het faillissement');
        $cir = collect($score['sources'])->firstWhere('key', 'cir');
        $this->assertSame('bad', $cir['status']);
        $this->assertStringContainsString('Faillissement F.01/11/7788 (DUIF Psychiatrie): Uitspraak faillissement op 01 november 2017', $cir['text']);

        // De webservice kreeg de gebruikersnaam in de kop en het KvK-nummer als zoeksleutel.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'webservice.rechtspraak.nl')
            && str_contains($request->body(), '<wsse:Username>proef</wsse:Username>')
            && str_contains($request->body(), '<commercialRegisterID>11998877</commercialRegisterID>'));
        Http::assertSent(fn ($request) => str_contains($request->body(), '<publicationNumber>01.dha.11.7788.F.1300.1.17</publicationNumber>'));
    }

    public function test_an_invalid_vat_number_and_a_deregistered_company_are_flagged(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeSources(vatValid: false, endedOn: '20250301');
        $customer = $this->customer();
        $this->invoice($customer, 'paid', 40, 0);

        $score = app(CustomerScoreService::class)->score($customer);

        $this->assertSame('E', $score['grade']);
        $this->assertSame(35, $score['score'], '100 - 15 (btw-nummer) - 50 (uitgeschreven)');
        $sources = collect($score['sources'])->keyBy('key');
        $this->assertSame('bad', $sources['vies']['status']);
        $this->assertSame('bad', $sources['kvk']['status']);
        $this->assertStringContainsString('Uitgeschreven uit het Handelsregister op 1 maart 2025', $sources['kvk']['text']);
    }

    public function test_without_data_there_is_no_score_and_a_consumer_is_searched_by_name_and_address(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        // Geen bronnen gekoppeld en niets betaald: geen score, wel uitleg.
        $customer = $this->customer(['vat_number' => null, 'kvk_number' => null]);
        $score = app(CustomerScoreService::class)->score($customer);
        $this->assertNull($score['score']);
        $this->assertNull($score['grade']);
        $this->assertSame('Nog niet te beoordelen', $score['label']);
        $this->assertSame(['off', 'off', 'off'], array_column($score['sources'], 'status'));

        // Een particulier: alleen het insolventieregister, op achternaam met postcode en huisnummer.
        $this->fakeSources();
        $person = $this->customer(['name' => 'Jan van der Berg', 'type' => 'consumer', 'kvk_number' => null, 'vat_number' => null, 'address_line' => 'Kerkstraat 12a']);
        $score = app(CustomerScoreService::class)->score($person);
        $this->assertSame('consumer', $score['kind']);
        $this->assertSame(['cir'], array_column($score['sources'], 'key'));
        Http::assertSent(fn ($request) => str_contains($request->body(), '<searchNaturalPerson')
            && str_contains($request->body(), '<prefix>van der</prefix><surname>Berg</surname><postalCode>3441AB</postalCode><houseNumber>12</houseNumber>'));
        $this->assertSame(['prefix' => null, 'surname' => 'Jansen'], InsolvencyService::splitName('Piet Jansen'));
        $this->assertNull(InsolvencyService::splitName('Jansen'));
    }
}

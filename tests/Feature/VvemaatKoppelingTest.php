<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\VvemaatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * De koppeling met VvEMaat.
 *
 * VvEMaat weet over geld maar één ding: tot wanneer er betaald is. Zolang die
 * datum in de toekomst ligt heeft de vereniging toegang; daarna kan een bestuur
 * niets meer wijzigen. Aan deze ene melding hangt dus of iemand zijn eigen
 * administratie kan bijwerken, en dat maakt de randen belangrijker dan het
 * gelukkige geval:
 *
 * - een gewone klant mag hier nooit een verzoek opleveren;
 * - een factuur zonder periode levert géén gok op, maar niets;
 * - een storing aan de andere kant mag de betaling hier niet stukmaken.
 */
class VvemaatKoppelingTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('vvemaat.url', 'https://vvemaat.test');
        config()->set('vvemaat.sleutel', 'test-sleutel');
    }

    /**
     * De gebruiker van de administratie waarin de factuur is gemaakt.
     *
     * `demoUser()` bouwt elke aanroep een nieuw bedrijf op. Wie hem twee keer
     * roept en dan de factuur van de eerste opvraagt, krijgt een 404 door de
     * bedrijfsscope — en zoekt die fout in de verkeerde hoek.
     */
    private ?\App\Models\User $eigenaar = null;

    /** Een openstaande factuur voor een klant, met of zonder VvE-omgeving. */
    private function factuur(?string $slug, bool $metPeriode = true): Invoice
    {
        $user = $this->eigenaar = $this->demoUser();

        $klant = Customer::withoutGlobalScope('company')->create([
            'company_id' => $user->company_id,
            'name' => $slug ? 'VvE Keizersgracht 214' : 'Bakkerij Van Dam',
            'email' => 'post@example.test',
            'vvemaat_slug' => $slug,
        ]);

        return Invoice::withoutGlobalScope('company')->create([
            'company_id' => $user->company_id,
            'customer_id' => $klant->id,
            'customer_name' => $klant->name, // momentopname, verplicht (not null)
            'number' => '2026-0451',
            'status' => 'sent',
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-30',
            'subtotal' => 247.52,
            'vat_total' => 51.98,
            'total' => 299.50,
            'paid_total' => 0,
            'period_start' => $metPeriode ? '2026-09-01' : null,
            'period_end' => $metPeriode ? '2027-08-31' : null,
        ]);
    }

    private function betaal(Invoice $factuur, ?float $bedrag = null): void
    {
        Payment::withoutGlobalScope('company')->create([
            'company_id' => $factuur->company_id,
            'invoice_id' => $factuur->id,
            'kind' => 'payment',
            'amount' => $bedrag ?? (float) $factuur->total,
            'paid_on' => '2026-09-05',
            'method' => 'bank_transfer',
        ]);
    }

    public function test_betaalde_abonnementsfactuur_wordt_gemeld(): void
    {
        Http::fake(['vvemaat.test/*' => Http::response(['ok' => true], 200)]);

        $factuur = $this->factuur('keizersgracht214');
        $this->betaal($factuur);

        Http::assertSent(function ($verzoek) {
            $lijf = $verzoek->data();

            return $verzoek->url() === 'https://vvemaat.test/koppelvlak/abonnement/betaald'
                && $verzoek->hasHeader('X-Koppelvlak-Sleutel', 'test-sleutel')
                && $lijf['klant'] === 'keizersgracht214'
                // Tot het einde van de periode die deze factuur dekt — niet de
                // factuurdatum, en niet de vervaldatum.
                && $lijf['betaald_tot'] === '2027-08-31'
                && $lijf['bedrag_cent'] === 29950
                && $lijf['factuurnummer'] === '2026-0451';
        });

        $this->assertNotNull($factuur->fresh()->vvemaat_notified_at);
    }

    public function test_gewone_klant_levert_geen_enkel_verzoek_op(): void
    {
        Http::fake();

        $this->betaal($this->factuur(null));

        Http::assertNothingSent();
    }

    public function test_deelbetaling_meldt_nog_niets(): void
    {
        Http::fake();

        $factuur = $this->factuur('keizersgracht214');
        $this->betaal($factuur, 100.00);

        // De factuur staat op 'partial': er is nog niet voldaan, dus er is ook
        // nog geen periode gedekt.
        $this->assertSame('partial', $factuur->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_factuur_zonder_periode_wordt_niet_gegokt(): void
    {
        Http::fake();

        /*
         * Een handmatige factuur draagt geen periode. Dan is niet te zeggen tot
         * wanneer er toegang is. Raden zou betekenen dat iemand te vroeg wordt
         * buitengesloten of te lang doorwerkt zonder te betalen; er gaat dus
         * niets uit, en het staat in de log.
         */
        $factuur = $this->factuur('keizersgracht214', metPeriode: false);
        $this->betaal($factuur);

        Http::assertNothingSent();
        $this->assertNull($factuur->fresh()->vvemaat_notified_at);
    }

    public function test_storing_aan_de_andere_kant_breekt_de_betaling_niet(): void
    {
        Http::fake(['vvemaat.test/*' => Http::response('kapot', 500)]);

        $factuur = $this->factuur('keizersgracht214');
        $this->betaal($factuur);

        // De betaling is en blijft vastgelegd; alleen de melding ontbreekt nog.
        $vers = $factuur->fresh();
        $this->assertSame('paid', $vers->status);
        $this->assertEqualsWithDelta(299.50, (float) $vers->paid_total, 0.001);
        $this->assertNull($vers->vvemaat_notified_at);
    }

    public function test_de_planner_stuurt_een_gemiste_melding_alsnog(): void
    {
        // Eerst een storing aan de andere kant, daarna is het weer in de lucht.
        // Eén reeks, want een tweede Http::fake() overschrijft de eerste niet:
        // de eerste passende stub wint.
        Http::fake(['vvemaat.test/*' => Http::sequence()->push('kapot', 500)->push(['ok' => true], 200)]);

        $factuur = $this->factuur('keizersgracht214');
        $this->betaal($factuur);
        $this->assertNull($factuur->fresh()->vvemaat_notified_at);
        // refreshStatus() zet paid_at; daar zoekt de planner op.
        $this->assertNotNull($factuur->fresh()->paid_at);

        $this->artisan('vvemaat:meld-betalingen')->assertSuccessful();

        $this->assertNotNull($factuur->fresh()->vvemaat_notified_at);
    }

    public function test_zonder_sleutel_doet_de_koppeling_niets(): void
    {
        config()->set('vvemaat.sleutel', '');
        Http::fake();

        $this->betaal($this->factuur('keizersgracht214'));

        Http::assertNothingSent();
        $this->assertFalse(app(VvemaatService::class)->actief());
    }

    public function test_de_factuurpagina_toont_de_koppeling_bij_een_vve_klant(): void
    {
        Http::fake(['vvemaat.test/*' => Http::response(['ok' => true], 200)]);

        $factuur = $this->factuur('keizersgracht214');
        $this->betaal($factuur);

        $this->actingAs($this->eigenaar)
            ->get(route('invoices.show', $factuur))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoice.vvemaat.slug', 'keizersgracht214')
                ->where('invoice.vvemaat.paid_through', '2027-08-31')
                // Alles klopt, dus er hoort geen waarschuwing te staan.
                ->where('invoice.vvemaat.waarschuwing', null));
    }

    public function test_de_factuurpagina_waarschuwt_als_er_geen_periode_op_staat(): void
    {
        /*
         * Dit is de reden dat dit blok er is. Een factuur die met de hand is
         * gemaakt draagt geen periode, en dan geeft EasyInvoice bewust niets
         * door. Zonder deze melding zou de vereniging op slot gaan zonder dat
         * iemand begrijpt waarom.
         */
        $factuur = $this->factuur('keizersgracht214', metPeriode: false);

        $this->actingAs($this->eigenaar)
            ->get(route('invoices.show', $factuur))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoice.vvemaat.period_label', null)
                ->whereContains('invoice.vvemaat.waarschuwing', 'terugkerend profiel'));
    }

    public function test_een_gewone_klant_krijgt_geen_vvemaat_blok(): void
    {
        $factuur = $this->factuur(null);

        $this->actingAs($this->eigenaar)
            ->get(route('invoices.show', $factuur))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('invoice.vvemaat', null));
    }

    public function test_de_omgeving_is_via_het_klantformulier_in_te_stellen(): void
    {
        /*
         * Zonder dit veld in het formulier bestaat de koppeling wel in de
         * database maar kan niemand hem aanzetten. Dat is precies het soort gat
         * waardoor iets "af" lijkt en niets doet.
         */
        $user = $this->demoUser();
        $klant = Customer::withoutGlobalScope('company')->create([
            'company_id' => $user->company_id,
            'name' => 'VvE Keizersgracht 214',
            'country' => 'NL',
            'type' => 'business',
        ]);

        $this->actingAs($user)
            ->put(route('customers.update', $klant), [
                'name' => $klant->name,
                'type' => 'business',
                'country' => 'NL',
                // Met hoofdletters en spaties eromheen: dat hoort gewoon goed
                // te komen in plaats van een foutmelding op te leveren.
                'vvemaat_slug' => '  Keizersgracht214  ',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('keizersgracht214', $klant->fresh()->vvemaat_slug);
    }

    public function test_een_onmogelijk_subdomein_wordt_geweigerd(): void
    {
        $user = $this->demoUser();
        $klant = Customer::withoutGlobalScope('company')->create([
            'company_id' => $user->company_id,
            'name' => 'VvE Keizersgracht 214',
            'country' => 'NL',
            'type' => 'business',
        ]);

        // Een typefout hier stuurt de melding naar een omgeving die niet
        // bestaat, terwijl de juiste op slot blijft staan.
        $this->actingAs($user)
            ->put(route('customers.update', $klant), [
                'name' => $klant->name,
                'type' => 'business',
                'country' => 'NL',
                'vvemaat_slug' => 'niet dit_mag',
            ])
            ->assertSessionHasErrors('vvemaat_slug');

        $this->assertNull($klant->fresh()->vvemaat_slug);
    }

    public function test_een_al_gemelde_factuur_wordt_niet_opnieuw_gemeld(): void
    {
        Http::fake(['vvemaat.test/*' => Http::response(['ok' => true], 200)]);

        $factuur = $this->factuur('keizersgracht214');
        $this->betaal($factuur);

        // Een tweede boeking op dezelfde factuur (bijvoorbeeld een correctie)
        // hoort geen tweede melding op te leveren.
        $this->betaal($factuur, 0.01);

        Http::assertSentCount(1);
    }
}

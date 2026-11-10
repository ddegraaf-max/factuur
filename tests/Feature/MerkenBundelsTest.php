<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MerkBundelRegel;
use App\Models\MerkOphaling;
use App\Models\Payment;
use App\Models\User;
use App\Services\MerkenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 1.110: extra verbruik van de merken van het VvE/NextPro-pakket factureren.
 *
 * Met een nep-koppelvlak (Http::fake): er gaat niets naar buiten. Het merk
 * NextPro heeft een sleutel, de andere merken niet.
 */
class MerkenBundelsTest extends TestCase
{
    use RefreshDatabase;

    private Company $napoleon;

    /** Wat het nep-koppelvlak teruggeeft op GET /koppelvlak/bundels. */
    private array $klanten = [];

    /** Status van POST /koppelvlak/bundels/gefactureerd. */
    private int $afmeldStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->napoleon = new Company;
        $this->napoleon->forceFill(['name' => 'NapoleonSoftware B.V.', 'email' => 'factuur@napoleon.test'])->save();

        config()->set('merken.team_id', $this->napoleon->id);
        foreach (array_keys(config('merken.merken')) as $code) {
            config()->set("merken.merken.{$code}.sleutel", '');
            config()->set("merken.merken.{$code}.team_id", null);
        }
        config()->set('merken.merken.nextpro.url', 'https://nextpro.test');
        config()->set('merken.merken.nextpro.sleutel', 'np-sleutel');
        config()->set('vvemaat.sleutel', '');

        $this->klanten = [$this->kantoor('beheer-zuid', [
            $this->bundel('11111111-1111-1111-1111-111111111111', 'AI-bundel: 200 extra vragen', 2500),
            $this->bundel('pingen:2026-10', 'Brieven per post via Pingen, oktober 2026 (3 brieven)', 456),
        ])];

        Http::fake(function (Request $verzoek) {
            if ($verzoek->method() === 'GET' && $verzoek->url() === 'https://nextpro.test/koppelvlak/bundels') {
                return $verzoek->hasHeader('X-Koppelvlak-Sleutel', 'np-sleutel')
                    ? Http::response(['merk' => 'nextpro', 'klanten' => $this->klanten])
                    : Http::response(['fout' => 'sleutel_onjuist'], 401);
            }
            if ($verzoek->method() === 'POST' && $verzoek->url() === 'https://nextpro.test/koppelvlak/bundels/gefactureerd') {
                return Http::response(['ok' => $this->afmeldStatus === 200, 'gemarkeerd' => count($verzoek['ids'] ?? [])], $this->afmeldStatus);
            }

            return Http::response(['fout' => 'onbekend'], 404);
        });
    }

    private function kantoor(string $kenmerk, array $bundels): array
    {
        return [
            'kenmerk' => $kenmerk, 'slug' => $kenmerk, 'soort' => 'huur',
            'naam' => 'Beheer Zuid B.V.', 't_a_v' => 'J. Jansen',
            'adres' => 'Kerkstraat 1', 'postcode' => '1234 AB', 'plaats' => 'Utrecht', 'land' => 'NL',
            'kvk_nummer' => '12345678', 'btw_nummer' => 'NL001234567B01', 'email' => 'administratie@beheerzuid.test',
            'bundels' => $bundels,
        ];
    }

    private function bundel(string $id, string $omschrijving, int $cent): array
    {
        return ['id' => $id, 'omschrijving' => $omschrijving, 'aantal' => 1, 'bedrag_excl_btw_cent' => $cent, 'btw_pct' => 21];
    }

    private function concepten()
    {
        return Invoice::withoutGlobalScope('company')->where('company_id', $this->napoleon->id)->get();
    }

    public function test_ophalen_zet_per_klant_een_concept_klaar_met_een_regel_per_bundel(): void
    {
        $o = app(MerkenService::class)->haalOp('nextpro');

        $this->assertSame('ok', $o->status, (string) $o->melding);
        $this->assertSame(1, $o->facturen);
        $this->assertSame(2, $o->regels);

        $facturen = $this->concepten();
        $this->assertCount(1, $facturen);
        $f = $facturen->first();
        $this->assertSame('draft', $f->status, 'nooit automatisch versturen');
        $this->assertNull($f->number);
        $this->assertEqualsWithDelta(29.56, (float) $f->subtotal, 0.001);
        $this->assertEqualsWithDelta(6.21, (float) $f->vat_total, 0.001);
        $this->assertStringContainsString('NextPro', (string) $f->reference);

        $regels = $f->lines()->orderBy('sort_order')->get();
        $this->assertCount(2, $regels);
        $this->assertSame('AI-bundel: 200 extra vragen', $regels[0]->description);
        $this->assertEqualsWithDelta(25.00, (float) $regels[0]->unit_price, 0.001);
        $this->assertEqualsWithDelta(21.0, (float) $regels[0]->vat_rate, 0.001);

        $klant = Customer::withoutGlobalScope('company')->find($f->customer_id);
        $this->assertSame($this->napoleon->id, $klant->company_id);
        $this->assertSame(['nextpro', 'beheer-zuid'], [$klant->merk_code, $klant->merk_kenmerk]);
        $this->assertSame('Beheer Zuid B.V.', $klant->name);
        $this->assertSame('12345678', $klant->kvk_number);
        $this->assertSame('NL001234567B01', $klant->vat_number);
        $this->assertSame('administratie@beheerzuid.test', $klant->email);
        $this->assertSame('Kerkstraat 1', $klant->address_line);

        Mail::assertNothingSent();
    }

    public function test_een_bundel_komt_nooit_twee_keer_op_een_factuur(): void
    {
        $service = app(MerkenService::class);
        $service->haalOp('nextpro');

        // Het merk geeft dezelfde regels nog eens (bijv. omdat terugmelden misging).
        $tweede = $service->haalOp('nextpro');
        $this->assertSame(0, $tweede->regels);
        $this->assertCount(1, $this->concepten());
        $this->assertSame(2, MerkBundelRegel::count());
        $this->assertSame(2, $this->concepten()->first()->lines()->count());

        // Een nieuwe bundel in dezelfde maand komt bij hetzelfde concept.
        $this->klanten[0]['bundels'][] = $this->bundel('22222222-2222-2222-2222-222222222222', 'Sms-bundel: 100 berichten', 900);
        $derde = $service->haalOp('nextpro');
        $this->assertSame(1, $derde->regels);
        $this->assertSame(0, $derde->facturen);
        $this->assertCount(1, $this->concepten());
        $this->assertSame(3, $this->concepten()->first()->lines()->count());

        // En één klant, niet drie.
        $this->assertSame(1, Customer::withoutGlobalScope('company')->where('company_id', $this->napoleon->id)->count());
    }

    public function test_is_het_concept_al_verstuurd_dan_komt_er_een_nieuw(): void
    {
        $service = app(MerkenService::class);
        $service->haalOp('nextpro');
        $this->concepten()->first()->forceFill(['status' => 'sent', 'number' => '2026-0001'])->save();

        $this->klanten[0]['bundels'] = [$this->bundel('33333333-3333-3333-3333-333333333333', 'Opslagbundel', 500)];
        $o = $service->haalOp('nextpro');

        $this->assertSame(1, $o->facturen);
        $this->assertCount(2, $this->concepten());
    }

    public function test_het_pakket_hoort_welke_regels_gefactureerd_zijn(): void
    {
        app(MerkenService::class)->haalOp('nextpro');
        $f = $this->concepten()->first();

        Http::assertSent(fn (Request $v) => $v->method() === 'POST'
            && $v->url() === 'https://nextpro.test/koppelvlak/bundels/gefactureerd'
            && $v->hasHeader('X-Koppelvlak-Sleutel', 'np-sleutel')
            && $v['klant'] === 'beheer-zuid'
            && $v['ids'] === ['11111111-1111-1111-1111-111111111111', 'pingen:2026-10']
            && $v['factuurnummer'] === 'EI-concept-'.$f->id);

        $this->assertSame(0, MerkBundelRegel::whereNull('afgemeld_op')->count());
    }

    public function test_mislukt_terugmelden_dan_volgende_keer_opnieuw_zonder_dubbele_regel(): void
    {
        $service = app(MerkenService::class);
        $this->afmeldStatus = 500;
        $o = $service->haalOp('nextpro');
        $this->assertSame(1, $o->afmeldfouten);
        $this->assertSame(2, MerkBundelRegel::whereNull('afgemeld_op')->whereNotNull('afmeld_fout')->count());

        $this->afmeldStatus = 200;
        $tweede = $service->haalOp('nextpro');
        $this->assertSame(0, $tweede->afmeldfouten);
        $this->assertSame(0, MerkBundelRegel::whereNull('afgemeld_op')->count());
        $this->assertSame(2, $this->concepten()->first()->lines()->count());
    }

    public function test_merk_zonder_sleutel_wordt_overgeslagen_met_melding(): void
    {
        $o = app(MerkenService::class)->haalOp('vveklik');

        $this->assertSame('overgeslagen', $o->status);
        $this->assertStringContainsString('MERK_VVEKLIK_KOPPELVLAK_SLEUTEL', (string) $o->melding);
        Http::assertNothingSent();
        $this->assertCount(0, $this->concepten());
    }

    public function test_zonder_administratie_wordt_niets_opgehaald(): void
    {
        config()->set('merken.team_id', null);
        $o = app(MerkenService::class)->haalOp('nextpro');

        $this->assertSame('overgeslagen', $o->status);
        $this->assertStringContainsString('MERKEN_TEAM_ID', (string) $o->melding);
        Http::assertNothingSent();
    }

    public function test_een_verkeerde_sleutel_staat_als_fout_met_uitleg(): void
    {
        config()->set('merken.merken.nextpro.sleutel', 'verkeerd');
        $o = app(MerkenService::class)->haalOp('nextpro');

        $this->assertSame('fout', $o->status);
        $this->assertStringContainsString('401', (string) $o->melding);
        $this->assertCount(0, $this->concepten());
    }

    public function test_het_commando_haalt_alle_merken_op(): void
    {
        $this->artisan('merken:bundels-factureren')->assertSuccessful();

        $this->assertSame(count(config('merken.merken')), MerkOphaling::count());
        $this->assertSame(1, MerkOphaling::where('status', 'ok')->count());
        $this->assertCount(1, $this->concepten());
    }

    public function test_eigenaarsscherm_en_handmatig_ophalen(): void
    {
        config(['services.marketing_stats.emails' => '']);
        $eigen = new Company;
        $eigen->forceFill(['name' => 'EasyInvoice zelf', 'email' => 'eigenaar@easyinvoice.test', 'is_exempt' => true])->save();
        $eigenaar = new User;
        $eigenaar->forceFill(['name' => 'Eigenaar', 'email' => 'eigenaar@easyinvoice.test', 'password' => bcrypt('geheim-wachtwoord'), 'company_id' => $eigen->id])->save();
        $klant = new User;
        $klant->forceFill(['name' => 'Klant', 'email' => 'klant@example.test', 'password' => bcrypt('geheim-wachtwoord'), 'company_id' => $this->napoleon->id])->save();

        $this->actingAs($klant)->get(route('owner.merken.index'))->assertForbidden();
        $this->actingAs($klant)->post(route('owner.merken.ophalen'), ['merk' => 'nextpro'])->assertForbidden();

        // De eigenaar zit in zijn eigen administratie; het concept komt toch in die van het merk.
        $this->actingAs($eigenaar)->post(route('owner.merken.ophalen'), ['merk' => 'nextpro'])->assertRedirect();
        $this->assertCount(1, $this->concepten());
        $this->assertSame('eigenaar@easyinvoice.test', MerkOphaling::first()->door);
        $this->assertSame($eigenaar->id, auth()->id(), 'na het ophalen is de eigenaar nog ingelogd');

        $this->actingAs($eigenaar)->get(route('owner.merken.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Stats/Merken')
                ->where('merken.0.code', 'nextpro')
                ->where('merken.0.laatste.status', 'ok')
                ->where('merken.0.concepten.0.regels', 2)
                ->where('merken.0.niet_afgemeld', 0)
                ->where('merken.1.ontbreekt.0', fn ($v) => str_contains($v, 'MERK_NAPOLEON_KOPPELVLAK_SLEUTEL')));
    }

    public function test_betaalmelding_gaat_naar_het_merk_van_de_klant(): void
    {
        $klant = Customer::withoutGlobalScope('company')->create([
            'company_id' => $this->napoleon->id, 'name' => 'Beheer Zuid B.V.', 'email' => 'a@b.test',
            'merk_code' => 'nextpro', 'merk_kenmerk' => 'beheer-zuid',
        ]);
        $factuur = Invoice::withoutGlobalScope('company')->create([
            'company_id' => $this->napoleon->id, 'customer_id' => $klant->id, 'customer_name' => $klant->name,
            'number' => '2026-0100', 'status' => 'sent', 'invoice_date' => '2026-11-01', 'due_date' => '2026-11-30',
            'subtotal' => 100, 'vat_total' => 21, 'total' => 121, 'paid_total' => 0,
            'period_start' => '2026-11-01', 'period_end' => '2026-11-30',
        ]);
        Payment::withoutGlobalScope('company')->create([
            'company_id' => $this->napoleon->id, 'invoice_id' => $factuur->id, 'kind' => 'payment',
            'amount' => 121, 'paid_on' => '2026-11-05', 'method' => 'bank_transfer',
        ]);

        Http::assertSent(fn (Request $v) => $v->url() === 'https://nextpro.test/koppelvlak/abonnement/betaald'
            && $v->hasHeader('X-Koppelvlak-Sleutel', 'np-sleutel')
            && $v['klant'] === 'beheer-zuid' && $v['betaald_tot'] === '2026-11-30');
    }
}

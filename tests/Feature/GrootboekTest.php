<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Services\BookYearService;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerReportService;
use App\Services\LedgerService;
use App\Support\Rgs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Het grootboek.
 *
 * ── Waar deze tests op letten ─────────────────────────────────────────────
 *
 * Niet op het gelukkige geval — dat een boeking van € 100 tegen € 100 erin gaat,
 * is niet waar een boekhouding op stukloopt. Waar het op stukloopt:
 *
 * - een boeking die níet sluit, die er dan alsnog in komt;
 * - een vastgesteld jaar waar toch nog in geboekt kan worden;
 * - een factuur die twee keer in de omzet belandt;
 * - een RGS-code die verzonnen blijkt, wat pas opvalt als de accountant de
 *   auditfile niet kan inlezen.
 *
 * Op Postgres houdt de database dit zelf tegen (zie de migratie). Deze tests
 * draaien op SQLite, waar dat niet kan; hier wordt dus de laag erboven getoetst.
 * Dat de database het óók tegenhoudt, wordt op de server nagegaan met
 * `php artisan ledger:check`.
 */
class GrootboekTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        // Company::created legt het schema al aan; dit is dus ook de test dat
        // een nieuwe administratie meteen kan boeken.
        $this->company = Company::create(['name' => 'Grootboek BV', 'country' => 'NL']);
        $this->ledger = app(LedgerService::class);
    }

    // ---------------------------------------------------------------- schema

    public function test_een_nieuwe_administratie_krijgt_het_rgs_startschema(): void
    {
        $aantal = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->count();

        $this->assertGreaterThan(100, $aantal, 'het startschema hoort ruim honderd rekeningen te hebben');

        // De rekeningen waar het pakket zelf op boekt moeten er zijn, anders
        // mislukt de eerste factuur.
        foreach ([Rgs::DEBITEUREN, Rgs::CREDITEUREN, Rgs::BANK, Rgs::BTW_1A_HOOG,
            Rgs::BTW_5B_VOORBELASTING, Rgs::OMZET_DIENST_HOOG, Rgs::KOSTEN_ALGEMEEN,
            Rgs::NOG_TE_VERDELEN] as $code) {
            $this->assertNotNull(
                LedgerAccount::withoutGlobalScope('company')
                    ->where('company_id', $this->company->id)->where('rgs_code', $code)->first(),
                "rekening {$code} hoort in het startschema te staan"
            );
        }
    }

    public function test_elke_rgs_code_die_de_code_gebruikt_bestaat_echt(): void
    {
        /*
         * De belangrijkste test van dit bestand.
         *
         * Een verzonnen RGS-code ziet er precies zo uit als een echte. Hij valt
         * niet op bij het boeken, niet in de proefbalans en niet in de balans —
         * pas wanneer de accountant de auditfile inleest en er niets uit komt.
         * Eerder is in dit huis een schema met verzonnen codes gebouwd waarvan
         * zes van de negen steekproeven fout waren. Daarom: elke code die in de
         * code staat, moet in de officiële lijst voorkomen.
         */
        $constanten = (new \ReflectionClass(Rgs::class))->getConstants();

        $codes = [];
        foreach ($constanten as $naam => $waarde) {
            if (is_string($waarde) && preg_match('/^[BW][A-Za-z]{3,}$/', $waarde)) {
                $codes[$naam] = $waarde;
            }
            if ($naam === 'EXTRA_IN_STARTSCHEMA') {
                foreach ($waarde as $i => $extra) {
                    $codes["EXTRA_IN_STARTSCHEMA[{$i}]"] = $extra;
                }
            }
        }

        $this->assertGreaterThan(20, count($codes), 'de constanten horen gevonden te worden');

        foreach ($codes as $naam => $code) {
            $this->assertNotNull(Rgs::vind($code),
                "Rgs::{$naam} = '{$code}' staat niet in de officiële RGS-lijst");
        }

        // Ook de codes die de boekingslogica los van de constanten gebruikt.
        foreach (['WOmzNodOdo', 'WOmzNohOlo', 'WBedKanTef', 'WBedAutOak', 'WBedHuiOhv',
            'WBedKanOka', 'WBedVkkRep', 'WBedVkkOvr', 'WFbeRlsRef', 'WBedAdlBev',
            'BSchOpaNtb'] as $code) {
            $this->assertNotNull(Rgs::vind($code), "'{$code}' staat niet in de officiële RGS-lijst");
        }
    }

    public function test_de_naamcorrecties_horen_bij_een_bestaande_code(): void
    {
        // Een correctie op een code die niet bestaat, doet niets en verstopt dat
        // de bron is veranderd.
        foreach (Rgs::NAAM_CORRECTIES as $code => $naam) {
            $this->assertNotNull(Rgs::vind($code), "naamcorrectie voor onbekende code '{$code}'");
            $this->assertSame($naam, Rgs::vind($code)['naam'], "de correctie voor {$code} komt niet door");
        }
    }

    public function test_het_schema_aanleggen_is_idempotent(): void
    {
        $voor = LedgerAccount::withoutGlobalScope('company')->where('company_id', $this->company->id)->count();

        app(ChartOfAccountsService::class)->seed($this->company);
        app(ChartOfAccountsService::class)->seed($this->company);

        $na = LedgerAccount::withoutGlobalScope('company')->where('company_id', $this->company->id)->count();
        $this->assertSame($voor, $na, 'twee keer aanleggen hoort niets te veranderen');
    }

    // ---------------------------------------------------------------- boeken

    public function test_een_sluitende_boeking_gaat_erin(): void
    {
        $post = $this->ledger->post($this->company, 'MEM', '2026-03-01', 'Testboeking', [
            ['rgs' => Rgs::BANK, 'debit' => 10000],
            ['rgs' => Rgs::KAPITAAL, 'credit' => 10000],
        ]);

        $this->assertSame('MEM 2026-0001', $post->number);
        $this->assertCount(2, $post->lines);
        $this->assertTrue($post->isBalanced());
        $this->assertSame(10000, $post->totalCents());
    }

    public function test_een_boeking_die_niet_sluit_wordt_geweigerd(): void
    {
        $this->expectExceptionMessageMatches('/niet in balans/');

        $this->ledger->post($this->company, 'MEM', '2026-03-01', 'Scheef', [
            ['rgs' => Rgs::BANK, 'debit' => 10000],
            ['rgs' => Rgs::KAPITAAL, 'credit' => 9900],
        ]);

        // En er hoort niets halfs te blijven staan.
        $this->assertSame(0, JournalEntry::withoutGlobalScope('company')->count());
    }

    public function test_er_wordt_niet_op_een_rubriek_geboekt(): void
    {
        $rubriek = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('postable', false)->first();

        $this->assertNotNull($rubriek, 'het schema hoort rubrieken te hebben');
        $this->expectExceptionMessageMatches('/rubriek/');

        $this->ledger->post($this->company, 'MEM', '2026-03-01', 'Op een rubriek', [
            ['account' => $rubriek->id, 'debit' => 100],
            ['rgs' => Rgs::KAPITAAL, 'credit' => 100],
        ]);
    }

    public function test_een_boeking_met_een_regel_wordt_geweigerd(): void
    {
        $this->expectExceptionMessageMatches('/twee regels/');

        $this->ledger->post($this->company, 'MEM', '2026-03-01', 'Half', [
            ['rgs' => Rgs::BANK, 'debit' => 100],
        ]);
    }

    public function test_het_boekstuknummer_loopt_door_per_dagboek_en_jaar(): void
    {
        $regels = [['rgs' => Rgs::BANK, 'debit' => 100], ['rgs' => Rgs::KAPITAAL, 'credit' => 100]];

        $een = $this->ledger->post($this->company, 'MEM', '2026-01-05', 'Een', $regels);
        $twee = $this->ledger->post($this->company, 'MEM', '2026-06-05', 'Twee', $regels);
        $ander = $this->ledger->post($this->company, 'BNK', '2026-06-05', 'Bank', $regels);
        $volgend = $this->ledger->post($this->company, 'MEM', '2027-01-05', 'Volgend jaar', $regels);

        $this->assertSame('MEM 2026-0001', $een->number);
        $this->assertSame('MEM 2026-0002', $twee->number);
        $this->assertSame('BNK 2026-0001', $ander->number);
        $this->assertSame('MEM 2027-0001', $volgend->number);
    }

    public function test_een_rekening_die_niet_in_het_startschema_zit_wordt_bijgezet(): void
    {
        // 1c (overige tarieven) zit niet in de basis van RGS, maar moet er komen
        // zodra iemand een factuur met een ander tarief boekt.
        $this->assertNull(LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', 'WOmzNodOdo')->first());

        $this->ledger->post($this->company, 'VRK', '2026-03-01', 'Oud tarief', [
            ['rgs' => 'WOmzNodOdo', 'credit' => 10000],
            ['rgs' => Rgs::DEBITEUREN, 'debit' => 10000],
        ]);

        $bij = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', 'WOmzNodOdo')->first();

        $this->assertNotNull($bij, 'de rekening hoort bijgezet te zijn');
        $this->assertSame('8003030', $bij->number, 'met het officiële nummer erbij');
        $this->assertFalse($bij->is_system, 'bijgekozen, dus niet van het systeem');
    }

    // ----------------------------------------------------------- boekjaar dicht

    public function test_in_een_vastgesteld_jaar_kan_niet_meer_geboekt_worden(): void
    {
        $regels = [['rgs' => Rgs::BANK, 'debit' => 5000], ['rgs' => Rgs::KAPITAAL, 'credit' => 5000]];
        $this->ledger->post($this->company, 'MEM', '2025-05-01', 'In 2025', $regels);

        app(BookYearService::class)->close($this->company, 2025);

        $this->expectExceptionMessageMatches('/vastgesteld/');
        $this->ledger->post($this->company, 'MEM', '2025-06-01', 'Te laat', $regels);
    }

    public function test_bij_vaststellen_gaat_het_resultaat_naar_het_vermogen(): void
    {
        // Omzet van € 1.000 zonder kosten: winst € 1.000.
        $this->ledger->post($this->company, 'VRK', '2025-04-01', 'Omzet', [
            ['rgs' => Rgs::DEBITEUREN, 'debit' => 100000],
            ['rgs' => Rgs::OMZET_DIENST_HOOG, 'credit' => 100000],
        ]);

        $reports = app(LedgerReportService::class);
        $voor = $reports->balanceSheet($this->company, 2025);
        $this->assertSame(100000, $voor['result'], 'de winst hoort € 1.000 te zijn');
        $this->assertTrue($voor['totals']['balanced'], 'de balans hoort te sluiten');

        app(BookYearService::class)->close($this->company, 2025);

        $na = $reports->balanceSheet($this->company, 2025);
        $this->assertSame(0, $na['result'], 'na vaststellen is het resultaat van het jaar nul');
        $this->assertTrue($na['totals']['balanced'], 'en de balans sluit nog steeds');

        // Het bedrag staat nu op het ondernemingsvermogen.
        $kapitaal = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', Rgs::KAPITAAL)->first();
        $saldo = JournalLine::withoutGlobalScope('company')
            ->where('ledger_account_id', $kapitaal->id)->sum('credit_cents');
        $this->assertSame(100000, (int) $saldo);
    }

    public function test_de_balans_sluit_ook_met_winst_uit_een_jaar_dat_nog_openstaat(): void
    {
        /*
         * De valkuil: de balans telt de balansrekeningen vanaf het begin van de
         * administratie, maar de winst-en-verliesrekening alleen dit jaar. Staat
         * vorig jaar nog open, dan zit die winst nog in de debiteuren en moet hij
         * ook aan de vermogenskant terugkomen — anders sluit de balans niet.
         */
        $this->ledger->post($this->company, 'VRK', '2025-06-01', 'Omzet vorig jaar', [
            ['rgs' => Rgs::DEBITEUREN, 'debit' => 70000],
            ['rgs' => Rgs::OMZET_DIENST_HOOG, 'credit' => 70000],
        ]);
        $this->ledger->post($this->company, 'VRK', '2026-06-01', 'Omzet dit jaar', [
            ['rgs' => Rgs::DEBITEUREN, 'debit' => 30000],
            ['rgs' => Rgs::OMZET_DIENST_HOOG, 'credit' => 30000],
        ]);

        $balans = app(LedgerReportService::class)->balanceSheet($this->company, 2026);

        $this->assertTrue($balans['totals']['balanced'],
            "actief {$balans['totals']['assets']} tegen passief {$balans['totals']['liabilities']}");
        $this->assertSame(30000, $balans['result'], 'het resultaat van 2026 is € 300');

        // En er hoort een aparte regel te staan voor wat uit 2025 komt.
        $eerder = collect($balans['liabilities'])
            ->first(fn ($r) => str_contains($r['name'], 'eerdere jaren'));
        $this->assertNotNull($eerder, 'de winst van 2025 hoort een eigen regel te krijgen');
        $this->assertSame(70000, $eerder['amount']);
    }

    public function test_na_vaststellen_staat_het_resultaat_niet_dubbel_op_de_balans(): void
    {
        $this->ledger->post($this->company, 'VRK', '2025-06-01', 'Omzet 2025', [
            ['rgs' => Rgs::DEBITEUREN, 'debit' => 70000],
            ['rgs' => Rgs::OMZET_DIENST_HOOG, 'credit' => 70000],
        ]);

        app(BookYearService::class)->close($this->company, 2025);

        $balans = app(LedgerReportService::class)->balanceSheet($this->company, 2026);

        $this->assertTrue($balans['totals']['balanced']);
        $this->assertNull(
            collect($balans['liabilities'])->first(fn ($r) => str_contains($r['name'], 'eerdere jaren')),
            'een vastgesteld jaar hoort niet meer als los resultaat op de balans te staan'
        );

        // Het staat nu op het ondernemingsvermogen.
        $kapitaal = collect($balans['liabilities'])
            ->first(fn ($r) => str_contains(mb_strtolower($r['name']), 'ondernemingsvermogen'));
        $this->assertNotNull($kapitaal);
        $this->assertSame(70000, $kapitaal['amount']);
    }

    public function test_een_jaar_dat_niet_sluit_kan_niet_worden_vastgesteld(): void
    {
        /*
         * Dit kan alleen als er buiten het grootboek om iets is gewijzigd. Op
         * Postgres kan dat niet; hier zetten we het na om te toetsen dat
         * vaststellen het dan weigert. Vaststellen is onomkeerbaar-achtig, dus
         * dit is precies het moment om níet door te gaan.
         */
        $post = $this->ledger->post($this->company, 'MEM', '2025-05-01', 'Straks scheef', [
            ['rgs' => Rgs::BANK, 'debit' => 5000],
            ['rgs' => Rgs::KAPITAAL, 'credit' => 5000],
        ]);

        JournalLine::withoutGlobalScope('company')
            ->where('journal_entry_id', $post->id)->where('credit_cents', '>', 0)
            ->update(['credit_cents' => 4000]);

        $this->expectExceptionMessageMatches('/sluit niet/');
        app(BookYearService::class)->close($this->company, 2025);
    }

    public function test_heropenen_haalt_de_afsluitboeking_weg(): void
    {
        $this->ledger->post($this->company, 'VRK', '2025-04-01', 'Omzet', [
            ['rgs' => Rgs::DEBITEUREN, 'debit' => 50000],
            ['rgs' => Rgs::OMZET_DIENST_HOOG, 'credit' => 50000],
        ]);

        app(BookYearService::class)->close($this->company, 2025);
        $this->assertNotNull($this->ledger->findBySource($this->company, 'close', 2025));

        app(BookYearService::class)->reopen($this->company, 2025);
        $this->assertNull($this->ledger->findBySource($this->company, 'close', 2025));
        $this->assertSame(50000, app(LedgerReportService::class)->balanceSheet($this->company, 2025)['result']);
    }

    // ---------------------------------------------------------------- facturen

    public function test_een_verstuurde_factuur_wordt_geboekt(): void
    {
        $factuur = $this->factuur(1000.00, 21);

        $post = $this->ledger->findBySource($this->company, 'invoice', $factuur->id);
        $this->assertNotNull($post, 'een verstuurde factuur hoort geboekt te zijn');
        $this->assertTrue($post->isBalanced());

        // € 1.000 omzet + € 210 btw = € 1.210 op debiteuren.
        $this->assertSame(121000, $post->totalCents());

        $opRekening = fn (string $rgs) => $post->lines
            ->first(fn ($l) => $l->account->rgs_code === $rgs);

        $this->assertSame(121000, $opRekening(Rgs::DEBITEUREN)->debit_cents);
        $this->assertSame(100000, $opRekening(Rgs::OMZET_DIENST_HOOG)->credit_cents);
        $this->assertSame(21000, $opRekening(Rgs::BTW_1A_HOOG)->credit_cents);
    }

    public function test_een_concept_wordt_niet_geboekt(): void
    {
        $factuur = $this->factuur(500.00, 21, 'draft');

        $this->assertNull($this->ledger->findBySource($this->company, 'invoice', $factuur->id));
    }

    public function test_een_factuur_wordt_niet_twee_keer_geboekt(): void
    {
        $factuur = $this->factuur(1000.00, 21);

        // Nog eens opslaan, en nog eens handmatig boeken.
        $factuur->touch();
        app(\App\Services\LedgerPostingService::class)->postInvoice($factuur->fresh());

        $aantal = JournalEntry::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)
            ->where('source_type', 'invoice')->where('source_id', $factuur->id)->count();

        $this->assertSame(1, $aantal, 'dezelfde factuur hoort één keer in de omzet te staan');
    }

    public function test_een_ontvangst_boekt_de_bank_en_de_debiteur(): void
    {
        $factuur = $this->factuur(1000.00, 21);

        $betaling = Payment::withoutGlobalScope('company')->create([
            'company_id' => $this->company->id,
            'invoice_id' => $factuur->id,
            'kind' => 'payment',
            'amount' => 1210.00,
            'paid_on' => '2026-04-01',
            'method' => 'bank_transfer',
        ]);

        $post = $this->ledger->findBySource($this->company, 'payment', $betaling->id);
        $this->assertNotNull($post);
        $this->assertSame(121000, $post->totalCents());

        // De debiteur staat daarna op nul.
        $debiteuren = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', Rgs::DEBITEUREN)->first();
        $regels = JournalLine::withoutGlobalScope('company')->where('ledger_account_id', $debiteuren->id)->get();
        $this->assertSame(0, (int) $regels->sum('debit_cents') - (int) $regels->sum('credit_cents'));
    }

    public function test_een_inkoopfactuur_boekt_kosten_en_voorbelasting(): void
    {
        $inkoop = PurchaseInvoice::withoutGlobalScope('company')->create([
            'company_id' => $this->company->id,
            'supplier_name' => 'De Leverancier',
            'supplier_reference' => 'INK-1',
            'category' => 'telefoon',
            'invoice_date' => '2026-02-01',
            'status' => 'open',
            'subtotal' => 100.00,
            'vat_total' => 21.00,
            'total' => 121.00,
            'vat_lines' => [['base' => 100.00, 'rate' => 21, 'vat' => 21.00]],
        ]);

        $post = $this->ledger->findBySource($this->company, 'purchase_invoice', $inkoop->id);
        $this->assertNotNull($post);
        $this->assertSame(12100, $post->totalCents());

        // "telefoon" hoort op de telefoonrekening te belanden, niet op algemene
        // kosten: dat is waar de categorieënlijst voor is.
        $kosten = $post->lines->first(fn ($l) => $l->debit_cents === 10000);
        $this->assertSame('WBedKanTef', $kosten->account->rgs_code);

        $btw = $post->lines->first(fn ($l) => $l->account->rgs_code === Rgs::BTW_5B_VOORBELASTING);
        $this->assertSame(2100, $btw->debit_cents);
    }

    public function test_een_creditnota_draait_de_omzet_terug(): void
    {
        $this->factuur(1000.00, 21);
        $credit = $this->factuur(1000.00, 21, 'sent', true);

        $post = $this->ledger->findBySource($this->company, 'invoice', $credit->id);
        $this->assertNotNull($post);

        $omzet = $post->lines->first(fn ($l) => $l->account->rgs_code === Rgs::OMZET_DIENST_HOOG);
        $this->assertSame(100000, $omzet->debit_cents, 'bij een creditnota staat de omzet debet');

        // Samen: omzet weer nul.
        $rekening = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', Rgs::OMZET_DIENST_HOOG)->first();
        $regels = JournalLine::withoutGlobalScope('company')->where('ledger_account_id', $rekening->id)->get();
        $this->assertSame(0, (int) $regels->sum('credit_cents') - (int) $regels->sum('debit_cents'));
    }

    // ---------------------------------------------------------------- stukken

    public function test_de_proefbalans_sluit(): void
    {
        $this->factuur(1000.00, 21);
        $this->factuur(250.50, 9);

        $proef = app(LedgerReportService::class)->trialBalance(
            $this->company,
            Carbon::create(2026, 1, 1),
            Carbon::create(2026, 12, 31)
        );

        $this->assertTrue($proef['totals']['balanced'],
            "debet {$proef['totals']['debit']} tegen credit {$proef['totals']['credit']}");
        $this->assertNotEmpty($proef['rows']);
    }

    public function test_de_grootboekkaart_komt_uit_op_de_proefbalans(): void
    {
        $this->factuur(1000.00, 21);
        $this->factuur(500.00, 21);

        $reports = app(LedgerReportService::class);
        $debiteuren = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', Rgs::DEBITEUREN)->first();

        $kaart = $reports->accountCard($this->company, $debiteuren,
            Carbon::create(2026, 1, 1), Carbon::create(2026, 12, 31));

        $proef = $reports->trialBalance($this->company,
            Carbon::create(2026, 1, 1), Carbon::create(2026, 12, 31));
        $regel = collect($proef['rows'])->firstWhere('id', $debiteuren->id);

        $this->assertSame($regel['balance'], $kaart['closing'],
            'het eindsaldo van de kaart hoort gelijk te zijn aan het saldo in de proefbalans');
    }

    public function test_de_btw_aangifte_komt_uit_het_grootboek(): void
    {
        $this->factuur(1000.00, 21);
        $this->factuur(200.00, 9);

        $aangifte = app(LedgerReportService::class)->vatReturn(
            $this->company, Carbon::create(2026, 1, 1), Carbon::create(2026, 12, 31)
        );

        $this->assertSame(100000, $aangifte['1a']['base']);
        $this->assertSame(21000, $aangifte['1a']['vat']);
        $this->assertSame(20000, $aangifte['1b']['base']);
        $this->assertSame(1800, $aangifte['1b']['vat']);
    }

    // -------------------------------------------------------------- beginbalans

    public function test_een_beginbalans_die_niet_sluit_komt_zichtbaar_op_het_kapitaal(): void
    {
        $bank = $this->rekening(Rgs::BANK);

        $post = app(BookYearService::class)->setOpeningBalance($this->company, 2026, [
            ['account_id' => $bank->id, 'debit' => 5000.00],
        ]);

        $this->assertTrue($post->isBalanced());

        $verschil = $post->lines->first(fn ($l) => $l->account->rgs_code === Rgs::KAPITAAL_BEGINBALANS);
        $this->assertNotNull($verschil, 'het verschil hoort op de beginbalansregel van het kapitaal te staan');
        $this->assertSame(500000, $verschil->credit_cents);
        $this->assertStringContainsString('uit te zoeken', $verschil->description);
    }

    public function test_een_tweede_beginbalans_vervangt_de_eerste(): void
    {
        $bank = $this->rekening(Rgs::BANK);

        app(BookYearService::class)->setOpeningBalance($this->company, 2026, [
            ['account_id' => $bank->id, 'debit' => 1000.00],
        ]);
        app(BookYearService::class)->setOpeningBalance($this->company, 2026, [
            ['account_id' => $bank->id, 'debit' => 2000.00],
        ]);

        $aantal = JournalEntry::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('source_type', 'opening')->count();
        $this->assertSame(1, $aantal);

        $post = $this->ledger->findBySource($this->company, 'opening', 2026);
        $this->assertSame(200000, $post->load('lines')->lines
            ->first(fn ($l) => $l->ledger_account_id === $bank->id)->debit_cents);
    }

    // ------------------------------------------------------------------ scheiding

    public function test_een_administratie_ziet_het_grootboek_van_een_ander_niet(): void
    {
        $ander = Company::create(['name' => 'Iemand anders', 'country' => 'NL']);

        $this->ledger->post($this->company, 'MEM', '2026-01-01', 'Van ons', [
            ['rgs' => Rgs::BANK, 'debit' => 100], ['rgs' => Rgs::KAPITAAL, 'credit' => 100],
        ]);
        $this->ledger->post($ander, 'MEM', '2026-01-01', 'Van hen', [
            ['rgs' => Rgs::BANK, 'debit' => 999], ['rgs' => Rgs::KAPITAAL, 'credit' => 999],
        ]);

        $proef = app(LedgerReportService::class)->trialBalance(
            $this->company, Carbon::create(2026, 1, 1), Carbon::create(2026, 12, 31)
        );

        $this->assertSame(100, $proef['totals']['debit'],
            'het bedrag van de andere administratie hoort hier niet in mee te tellen');

        // En een rekening van de ander is geen geldige bestemming.
        $vreemd = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $ander->id)->where('rgs_code', Rgs::BANK)->firstOrFail();

        $this->expectExceptionMessageMatches('/hoort niet bij deze administratie/');
        $this->ledger->post($this->company, 'MEM', '2026-02-01', 'Naar de buren', [
            ['account' => $vreemd->id, 'debit' => 100],
            ['rgs' => Rgs::KAPITAAL, 'credit' => 100],
        ]);
    }

    // -------------------------------------------------------------------- hulp

    private function rekening(string $rgs): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->where('rgs_code', $rgs)->firstOrFail();
    }

    private function factuur(float $bedrag, float $tarief, string $status = 'sent', bool $credit = false): Invoice
    {
        $klant = Customer::withoutGlobalScope('company')->firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'De Klant'],
            ['country' => 'NL']
        );

        $btw = round($bedrag * $tarief / 100, 2);

        $factuur = Invoice::withoutGlobalScope('company')->create([
            'company_id' => $this->company->id,
            'customer_id' => $klant->id,
            'customer_name' => $klant->name,
            'customer_country' => 'NL',
            'number' => ($credit ? 'C' : 'F') . '-' . uniqid(),
            'status' => $status,
            'is_credit' => $credit,
            'invoice_date' => '2026-03-01',
            'due_date' => '2026-03-31',
            'subtotal' => $bedrag,
            'vat_total' => $btw,
            'total' => $bedrag + $btw,
        ]);

        $factuur->lines()->create([
            'description' => 'Werk',
            'quantity' => 1,
            'unit_price' => $bedrag,
            'vat_rate' => $tarief,
            'line_subtotal' => $bedrag,
            'line_vat' => $btw,
            'line_total' => $bedrag + $btw,
        ]);

        // De regels bestaan nu; opnieuw opslaan zet de boeking erin.
        $factuur->refresh()->save();

        return $factuur->fresh('lines');
    }
}

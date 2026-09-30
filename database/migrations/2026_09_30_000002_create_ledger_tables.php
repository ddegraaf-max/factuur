<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Het grootboek (1.72.0) — dubbel boekhouden.
 *
 * ── Waarom dit naast de facturen komt en die niet vervangt ────────────────
 *
 * EasyInvoice weet wat er is gefactureerd, wat er is ingekocht en wat er is
 * betaald. Daar is een overzicht uit te maken, en dat deden we ook: de
 * auditfile werd tot nu toe bij het exporteren ter plekke afgeleid uit die
 * documenten. Dat is geen boekhouding. Het breekt zodra iemand iets boekt wat
 * geen factuur is — een afschrijving, een privé-opname, een beginbalans, een
 * correctie van vorig jaar — en het kan per definitie niet uit balans lopen,
 * want er is niets om uit balans mee te raken.
 *
 * Vanaf hier is er een echt grootboek: rekeningen, dagboeken, journaalposten
 * en journaalregels. De facturen blijven waar ze zijn en blijven de bron; elke
 * boeking draagt bij welk document hem veroorzaakte, zodat de twee werelden
 * naar elkaar terug te rekenen zijn en een herbouw niets dubbel telt.
 *
 * ── Bedragen in centen ────────────────────────────────────────────────────
 *
 * De rest van het pakket rekent in decimal(10,2); een grootboek doet dat in
 * hele centen. Debet moet exact gelijk zijn aan credit, en "exact" bestaat niet
 * bij een getal dat onderweg is afgerond. Daarom bigint, en daarom heten de
 * kolommen _cents: dan kan niemand ze per ongeluk voor euro's aanzien.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Het rekeningschema. De hele RGS-boom staat erin, ook de rubrieken
         * waarop niet geboekt wordt (niveau 2 en 3): die dragen de indeling van
         * de balans en de winst-en-verliesrekening. Op niveau 4 en 5 wordt
         * geboekt — postable.
         */
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Het rekeningnummer zoals de gebruiker het ziet. Bij een rekening
            // uit het standaardschema is dit het RGS-referentienummer.
            $table->string('number', 20);
            $table->string('name', 200);

            // De officiële RGS-referentiecode, bijv. BVorDebHad. Leeg bij een
            // rekening die de gebruiker zelf heeft toegevoegd; die heeft dan
            // geen plek in de auditfile-indeling en valt onder zijn rubriek.
            $table->string('rgs_code', 20)->nullable();

            // D of C: de kant waar het saldo normaal op staat. Nodig om een
            // saldo als positief te kunnen tonen zonder dat de gebruiker moet
            // weten dat een schuld aan de creditzijde hoort.
            $table->char('side', 1);

            // balans | resultaat — afgeleid uit de eerste letter van de
            // RGS-code (B of W), maar opgeslagen omdat eigen rekeningen geen
            // RGS-code hebben.
            $table->string('statement', 10);

            // 2 = hoofdrubriek, 3 = rubriek, 4 = rekening, 5 = subrekening.
            $table->unsignedTinyInteger('level')->default(4);
            $table->foreignId('parent_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();

            // Alleen hierop mag geboekt worden.
            $table->boolean('postable')->default(true);
            // Uit het standaardschema: naam en nummer zijn aan te passen, maar
            // de rekening mag niet verdwijnen zolang de rest van het pakket
            // erop rekent.
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'rgs_code']);
            $table->index(['company_id', 'statement', 'sort']);
        });

        /*
         * Dagboeken. Eén per soort brondocument, zodat een boekstuknummer
         * zegt waar de boeking vandaan komt: VRK 2026-0001 is een verkoop.
         */
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            // verkoop | inkoop | bank | kas | memoriaal
            $table->string('kind', 20);
            // De vaste tegenrekening: bij een bankdagboek de bankrekening,
            // bij kas de kas. Bij verkoop en inkoop leeg (dat is de debiteur
            // of crediteur van het document zelf).
            $table->foreignId('ledger_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        /*
         * Doorlopende nummering per dagboek per jaar. Een eigen tabel in plaats
         * van max()+1, want twee boekingen op hetzelfde moment krijgen dan
         * hetzelfde boekstuknummer. De UPDATE vergrendelt de rij; dat kan niet
         * misgaan.
         */
        Schema::create('journal_sequences', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last')->default(0);

            $table->primary(['company_id', 'journal_id', 'year']);
        });

        /*
         * Boekjaren. Een jaar dat is vastgesteld gaat dicht: daarna hoort een
         * correctie in het lopende jaar, anders betekent een jaarrekening niets
         * en klopt de aangifte die al is ingediend niet meer met de boeken.
         */
        Schema::create('book_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            // open | closed
            $table->string('status', 10)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'year']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            // Boekstuk: dagboekcode + jaar + teller, bijv. VRK 2026-0001.
            $table->string('number', 30);
            $table->date('date');
            $table->string('description', 300);

            /*
             * Waar deze boeking vandaan komt. Daarmee is elke regel terug te
             * voeren op een document, en ziet een herbouw wat er al geboekt is.
             * invoice | purchase_invoice | payment | bank_transaction |
             * opening | manual | close
             */
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'year']);
        });

        /*
         * Eén boeking per brondocument. Maakt opnieuw opbouwen herhaalbaar
         * zonder dubbeltellen, en voorkomt dat dezelfde factuur twee keer in de
         * omzet staat. Een gedeeltelijke index, want handmatige boekingen
         * hebben geen bron en mogen elkaar niet uitsluiten.
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX journal_entries_source_unique
                ON journal_entries (company_id, source_type, source_id)
                WHERE source_id IS NOT NULL');
        } else {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->unique(['company_id', 'source_type', 'source_id'], 'journal_entries_source_unique');
            });
        }

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('description', 300)->nullable();

            $table->bigInteger('debit_cents')->default(0);
            $table->bigInteger('credit_cents')->default(0);

            // Het btw-tarief waaronder deze regel valt, en het btw-bedrag dat
            // erbij hoort. Hiermee komt de aangifte uit het grootboek in
            // plaats van uit de facturen.
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->bigInteger('vat_cents')->default(0);

            // Op wie slaat deze regel? Voor de subadministratie debiteuren en
            // crediteuren, en voor de auditfile.
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_name', 200)->nullable();

            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'ledger_account_id']);
            $table->index('journal_entry_id');
        });

        /*
         * Een regel staat aan één kant en is niet leeg. Zonder dit kan een
         * boeking van nul euro of een regel met debet én credit erin, en dan
         * klopt het saldo wel maar zegt de grootboekkaart niets.
         */
        $this->check('journal_lines', 'journal_lines_niet_negatief', 'debit_cents >= 0 AND credit_cents >= 0');
        $this->check('journal_lines', 'journal_lines_een_kant', 'NOT (debit_cents > 0 AND credit_cents > 0)');
        $this->check('journal_lines', 'journal_lines_niet_leeg', 'debit_cents > 0 OR credit_cents > 0');

        if (DB::getDriverName() === 'pgsql') {
            $this->postgresInvarianten();
        }
    }

    /**
     * Een CHECK toevoegen. Laravel heeft er geen bouwer voor, en de syntax is
     * voor Postgres en SQLite gelijk — alleen kan SQLite geen constraint aan een
     * bestaande tabel toevoegen, dus daar doen we het via een index-loze
     * omweg: de controle staat dan in de laag erboven (LedgerService) en in
     * de test. In productie draait Postgres.
     */
    private function check(string $table, string $name, string $expression): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
    }

    /**
     * De twee invarianten die nooit mogen breken, in de database zelf.
     *
     * Waarom hier en niet alleen in LedgerService: een import, een handmatige
     * correctie via de console, een latere module of een AI-hulpje kan de
     * service omzeilen. De database niet. Dit is precies de opzet die in
     * VvEMaat al draait.
     */
    private function postgresInvarianten(): void
    {
        /*
         * Debet moet credit dekken. Uitgesteld tot het eind van de transactie,
         * want na het invoegen van de eerste regel klopt het per definitie nog
         * niet.
         */
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION journaal_in_balans() RETURNS TRIGGER AS $$
DECLARE
  doelen BIGINT[];
  doel BIGINT;
  d BIGINT;
  c BIGINT;
BEGIN
  -- Bij een DELETE bestaat NEW niet en bij een INSERT bestaat OLD niet; een
  -- veld uitlezen van de verkeerde geeft "record is not assigned yet". Daarom
  -- eerst bepalen welke post(en) opnieuw geteld moeten worden. Verhuist een
  -- regel van de ene post naar de andere, dan kunnen beide uit balans raken.
  IF TG_OP = 'DELETE' THEN
    doelen := ARRAY[OLD.journal_entry_id];
  ELSIF TG_OP = 'UPDATE' AND OLD.journal_entry_id IS DISTINCT FROM NEW.journal_entry_id THEN
    doelen := ARRAY[OLD.journal_entry_id, NEW.journal_entry_id];
  ELSE
    doelen := ARRAY[NEW.journal_entry_id];
  END IF;

  FOREACH doel IN ARRAY doelen LOOP
    -- Een post die in dezelfde transactie is verwijderd hoeft niet te kloppen.
    IF NOT EXISTS (SELECT 1 FROM journal_entries WHERE id = doel) THEN
      CONTINUE;
    END IF;
    SELECT COALESCE(sum(debit_cents), 0), COALESCE(sum(credit_cents), 0)
      INTO d, c FROM journal_lines WHERE journal_entry_id = doel;
    IF d <> c THEN
      RAISE EXCEPTION 'journaalpost % is niet in balans: debet % cent, credit % cent', doel, d, c;
    END IF;
  END LOOP;
  RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS journal_lines_balans ON journal_lines;
CREATE CONSTRAINT TRIGGER journal_lines_balans
  AFTER INSERT OR UPDATE OR DELETE ON journal_lines
  DEFERRABLE INITIALLY DEFERRED
  FOR EACH ROW EXECUTE FUNCTION journaal_in_balans();
SQL);

        /*
         * Een vastgesteld boekjaar is dicht. Ook een boeking die per ongeluk
         * een oude datum krijgt, komt er niet in.
         */
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION boekjaar_open() RETURNS TRIGGER AS $$
DECLARE
  stand TEXT;
BEGIN
  SELECT status INTO stand FROM book_years
   WHERE company_id = NEW.company_id AND year = NEW.year;
  IF stand = 'closed' THEN
    RAISE EXCEPTION 'boekjaar % is vastgesteld en kan niet meer worden geboekt', NEW.year;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS journal_entries_boekjaar ON journal_entries;
CREATE TRIGGER journal_entries_boekjaar
  BEFORE INSERT OR UPDATE ON journal_entries
  FOR EACH ROW EXECUTE FUNCTION boekjaar_open();
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS journal_lines_balans ON journal_lines');
            DB::unprepared('DROP TRIGGER IF EXISTS journal_entries_boekjaar ON journal_entries');
            DB::unprepared('DROP FUNCTION IF EXISTS journaal_in_balans()');
            DB::unprepared('DROP FUNCTION IF EXISTS boekjaar_open()');
        }

        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('book_years');
        Schema::dropIfExists('journal_sequences');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('ledger_accounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De uitkomst van een controle in het Centraal Curatele- en Bewindregister.
 *
 * ── Waarom hier een bewaardatum in staat ──────────────────────────────────
 *
 * De gebruiksvoorwaarden van de Rechtspraak (artikel 2) eisen dat gegevens
 * binnen zes maanden na beëindiging van de maatregel worden vernietigd. Wanneer
 * een maatregel eindigt weet je alleen als je opnieuw bevraagt — dus kun je die
 * termijn niet halen door af te wachten.
 *
 * Daarom staat `vernietigen_op` als kolom in de tabel en niet als regel in een
 * opruimscript: de termijn is dan af te lezen, en de opruimtaak is een simpele
 * vergelijking die niet stilletjes kan verlopen. Hij is de vroegste van twee:
 *
 *  - zes maanden na de controle zelf. Loopt de maatregel dan nog, dan hoeft het
 *    niet van de voorwaarden, maar het mag wel — en het dwingt een verse
 *    controle af in plaats van een antwoord van een half jaar oud;
 *  - zes maanden na de einddatum, als het register die al meegaf.
 *
 * Zo wordt de termijn altijd gehaald, ook als er na deze controle nooit meer
 * iemand kijkt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ccbr_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at');

            // Waarop is gezocht. Vastgelegd omdat een uitkomst zonder de vraag
            // niets zegt: "niets gevonden" op de verkeerde geboortedatum is
            // geen geruststelling.
            $table->string('achternaam', 120);
            $table->string('voorvoegsel', 40)->nullable();
            $table->date('geboortedatum')->nullable();
            $table->unsignedSmallInteger('geboortejaar')->nullable();

            // Wat het register zei.
            $table->boolean('gevonden')->default(false);
            $table->string('maatregel', 20)->nullable();       // bewind | curatele | null als het niet eenduidig is
            $table->string('grond', 4)->nullable();
            $table->string('grond_tekst', 120)->nullable();
            $table->string('kaartnummer', 40)->nullable();
            $table->date('ingangsdatum')->nullable();
            $table->date('einddatum')->nullable();
            $table->string('rechtbank', 200)->nullable();
            $table->boolean('beperkt_bewind')->default(false);
            $table->json('vertegenwoordigers')->nullable();
            $table->boolean('volledige_match')->default(false);

            $table->date('vernietigen_op')->index();

            $table->timestamps();

            $table->index(['company_id', 'customer_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ccbr_checks');
    }
};

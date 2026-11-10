<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1.110: extra verbruik van de merken van het VvE/NextPro-pakket factureren.
 *
 * - customers.merk_code / merk_kenmerk: bij welk merk en welk kantoor of welke
 *   vereniging deze klant hoort (het kenmerk is wat het koppelvlak "klant" noemt).
 * - merk_ophalingen: elke ophaling per merk, voor het eigenaarsscherm.
 * - merk_bundel_regels: elke bundel of Pingen-maand die op een factuur kwam.
 *   Uniek op merk + kenmerk + bundel-id: zo komt een bundel nooit twee keer op
 *   een factuur. Het kenmerk hoort erbij omdat een Pingen-id ("pingen:2026-10")
 *   alleen binnen één klant uniek is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('merk_code', 20)->nullable()->after('vvemaat_slug');
            $table->string('merk_kenmerk', 80)->nullable()->after('merk_code');
            $table->index(['company_id', 'merk_code', 'merk_kenmerk']);
        });

        Schema::create('merk_ophalingen', function (Blueprint $table) {
            $table->id();
            $table->string('merk', 20)->index();
            $table->string('status', 20);          // bezig | ok | fout | overgeslagen
            $table->text('melding')->nullable();
            $table->unsignedInteger('klanten')->default(0);
            $table->unsignedInteger('regels')->default(0);
            $table->unsignedInteger('facturen')->default(0);
            $table->unsignedInteger('afmeldfouten')->default(0);
            $table->string('door', 80)->nullable();   // planner | e-mailadres van de eigenaar
            $table->timestamp('klaar_op')->nullable();
            $table->timestamps();
        });

        Schema::create('merk_bundel_regels', function (Blueprint $table) {
            $table->id();
            $table->string('merk', 20);
            $table->string('klant_kenmerk', 80);
            $table->string('bundel_id', 80);
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ophaling_id')->nullable()->constrained('merk_ophalingen')->nullOnDelete();
            $table->string('periode', 7);           // JJJJ-MM van de factuur
            $table->string('omschrijving', 500);
            $table->unsignedBigInteger('bedrag_excl_cent');
            $table->decimal('btw_pct', 5, 2)->default(21);
            $table->timestamp('afgemeld_op')->nullable();
            $table->text('afmeld_fout')->nullable();
            $table->timestamps();
            $table->unique(['merk', 'klant_kenmerk', 'bundel_id'], 'merk_bundel_uniek');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merk_bundel_regels');
        Schema::dropIfExists('merk_ophalingen');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'merk_code', 'merk_kenmerk']);
            $table->dropColumn(['merk_code', 'merk_kenmerk']);
        });
    }
};

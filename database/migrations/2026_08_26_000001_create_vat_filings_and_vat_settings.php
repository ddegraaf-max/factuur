<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Aangiftetijdvak zoals de Belastingdienst het heeft toegewezen:
            // quarter (standaard), month of year.
            $table->string('vat_period', 10)->default('quarter')->after('fiscal_year_start');
            // Omzetbelastingnummer (bijv. 123456789B01) — nodig om het
            // betalingskenmerk te berekenen. Voor eenmanszaken is dit
            // BSN-gebaseerd, dus versleuteld opgeslagen en nooit naar de browser.
            $table->text('ob_number')->nullable()->after('vat_period');
            // Herinnering per e-mail vóór de aangiftedeadline.
            $table->boolean('vat_reminder_enabled')->default(true)->after('ob_number');
        });

        // Status en aanvullingen per aangiftetijdvak.
        Schema::create('vat_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('period_type', 10);      // quarter | month | year
            $table->unsignedTinyInteger('period');   // 1-4, 1-12 of 1
            $table->timestamp('filed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            // Handmatig ingevuld betalingskenmerk (als er geen OB-nummer is,
            // of als de Belastingdienst een afwijkend kenmerk heeft gegeven).
            $table->string('payment_reference', 20)->nullable();
            // Rubrieken die EasyInvoice niet zelf kan afleiden (1c, 1d, 2a,
            // 4a, 4b en extra voorbelasting): {"2a": {"base": 0, "vat": 0}, …}
            $table->json('manual')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('reminded_final_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'period_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_filings');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['vat_period', 'ob_number', 'vat_reminder_enabled']);
        });
    }
};

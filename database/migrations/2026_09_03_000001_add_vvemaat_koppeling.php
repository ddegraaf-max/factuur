<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * De koppeling met VvEMaat.
 *
 * VvEMaat weet over geld maar één ding: tot wanneer er betaald is. Staat die
 * datum in de toekomst, dan heeft de vereniging toegang. EasyInvoice factureert
 * en int; zodra een factuur voldaan is geeft het die datum door.
 *
 * Daarvoor zijn drie dingen nodig:
 *
 * - `customers.vvemaat_slug` — welke klant hóórt bij een VvE-omgeving. Is dit
 *   leeg, dan is het een gewone klant en gebeurt er niets. Dat onderscheid moet
 *   hier staan en niet in code: EasyInvoice factureert veel meer dan VvE's.
 *
 * - `invoices.period_start` / `period_end` — welke periode een factuur dekt.
 *   Zonder dat zouden we de betaaldatum moeten raden uit de factuurdatum en de
 *   frequentie, en een gok over toegang tot andermans administratie is precies
 *   wat je niet wilt. De terugkerende facturatie weet het exact.
 *
 * - `invoices.vvemaat_notified_at` — of de melding is aangekomen. Er draait in
 *   deze omgeving geen queue-worker, dus de melding gaat direct mee. Lukt dat
 *   niet — VvEMaat even plat, netwerk eruit — dan blijft dit veld leeg en pikt
 *   de planner hem later opnieuw op. Anders zou een vereniging op slot blijven
 *   staan die gewoon betaald heeft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('vvemaat_slug', 63)->nullable()->index();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('vvemaat_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('vvemaat_slug');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['period_start', 'period_end', 'vvemaat_notified_at']);
        });
    }
};

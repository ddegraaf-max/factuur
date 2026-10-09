<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Btw-aangifte in EasyInvoice vanaf een datum (1.77.0).
 *
 * Wie zijn btw tot een bepaald moment nog ergens anders doet (de eigenaar:
 * "ga pas over per 1-1-2027 om alles zelf in te boeken"), wil tot die tijd
 * geen aangifte-melding op het dashboard, geen btw-kaart en geen
 * herinneringsmail. Leeg = zoals altijd: elk tijdvak telt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('vat_filing_from')->nullable()->after('vat_reminder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('vat_filing_from');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kleineondernemersregeling (KOR).
 *
 * Wie meedoet rekent geen btw, doet geen aangifte en zet op zijn factuur dat
 * een vrijstelling geldt. 'kor' op het bedrijf is de instelling; 'vat_exempt'
 * op het document legt vast dat het onder de regeling is gemaakt, zodat een
 * oude factuur blijft kloppen als het bedrijf zich later afmeldt.
 *
 * De tabellen staan voluit en niet in een lus: de statische analyse leest de
 * kolommen van een model uit de migraties en volgt geen variabelen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('kor')->default(false);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('vat_exempt')->default(false);
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('vat_exempt')->default(false);
        });
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->boolean('vat_exempt')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('kor');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('vat_exempt');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('vat_exempt');
        });
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn('vat_exempt');
        });
    }
};

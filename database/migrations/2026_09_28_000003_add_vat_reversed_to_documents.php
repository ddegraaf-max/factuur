<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Btw verlegd op facturen en offertes.
 *
 * Tot nu toe was een verlegde factuur niet te onderscheiden van een factuur
 * tegen 0%: de vermelding 'btw verlegd' ontbrak, en die is wettelijk verplicht
 * (onderaanneming in de bouw, diensten aan ondernemers in een ander EU-land).
 * Het is een eigenschap van het hele document; alle regels staan dan op 0%.
 *
 * De tabellen staan voluit en niet in een lus: de statische analyse (Larastan)
 * leest de kolommen van een model uit de migraties en volgt geen variabelen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('vat_reversed')->default(false);
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('vat_reversed')->default(false);
        });
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->boolean('vat_reversed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('vat_reversed');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('vat_reversed');
        });
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn('vat_reversed');
        });
    }
};

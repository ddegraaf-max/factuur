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
 */
return new class extends Migration
{
    private const TABLES = ['invoices', 'quotes', 'recurring_invoices'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('vat_reversed')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('vat_reversed');
            });
        }
    }
};

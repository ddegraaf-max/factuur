<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kortingen op factuur- en offerteregels: een percentage per regel.
 * De stuksprijs blijft de originele prijs; de korting wordt apart bewaard
 * en getoond, zodat de klant ziet wat hij bespaart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->decimal('discount_pct', 5, 2)->nullable()->after('vat_rate');
        });
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->decimal('discount_pct', 5, 2)->nullable()->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn('discount_pct');
        });
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->dropColumn('discount_pct');
        });
    }
};

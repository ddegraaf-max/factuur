<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voetnoot per documenttaal (1.53.0): een Engelse of Poolse voetnoot naast de
 * standaard voetnoot, voor facturen en offertes in die taal. Per bedrijf en
 * per handelsnaam; json {taal: tekst}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('invoice_footers')->nullable()->after('invoice_footer');
        });
        Schema::table('brand_profiles', function (Blueprint $table) {
            $table->json('invoice_footers')->nullable()->after('invoice_footer');
        });
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('invoice_footers'));
        Schema::table('brand_profiles', fn (Blueprint $table) => $table->dropColumn('invoice_footers'));
    }
};

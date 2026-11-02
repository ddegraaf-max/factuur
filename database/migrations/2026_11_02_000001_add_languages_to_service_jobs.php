<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fachora 1.95: de klant kiest meerdere talen. Pools staat altijd aan (alle
 * bedrijven werken in het Pools, de omschrijving wordt vertaald); de overige
 * talen zijn een voorkeur: bedrijven die zo'n taal spreken komen vooraan en
 * worden bij het aanbieden eerst geprobeerd. 'language' blijft de voorkeurstaal
 * (eerste taal naast het Pools, anders 'pl') voor bestaande schermen en mails.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->json('languages')->nullable()->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn('languages');
        });
    }
};

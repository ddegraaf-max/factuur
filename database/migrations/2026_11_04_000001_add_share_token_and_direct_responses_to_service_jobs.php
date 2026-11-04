<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1.98: de klant zet in zijn bericht aan een registerbedrijf een link naar een
 * aparte, alleen-lezen opdrachtpagina (/z/{share_token}/{bedrijf}-{code}). Dat
 * is een eigen token, los van het klanttoken (dat geeft beheer over de opdracht).
 * direct_responses: per bedrijf of het de pagina opende en wat het antwoordde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('token');
            $table->json('direct_responses')->nullable()->after('direct_contacted');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['share_token', 'direct_responses']);
        });
    }
};

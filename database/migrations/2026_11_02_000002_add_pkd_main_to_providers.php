<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fachora 1.96: hoofdactiviteit uit CEIDG. pkd_main/pkd_main_name is de
 * hoofd-PKD van het bedrijf, pkd_codes de volledige lijst, source_id het
 * CEIDG-id (voor een latere detailopvraag), pkd_checked_at wanneer dat is
 * bepaald. Bedrijven waarvan de hoofdactiviteit geen vakwerk is, komen niet
 * (meer) in de keuzelijst en worden binnen een dag verwijderd (met logregel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('pkd_main', 10)->nullable()->after('source_fetched_at');
            $table->string('pkd_main_name', 200)->nullable()->after('pkd_main');
            $table->json('pkd_codes')->nullable()->after('pkd_main_name');
            $table->string('source_id', 64)->nullable()->after('pkd_codes');
            $table->timestamp('pkd_checked_at')->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['pkd_main', 'pkd_main_name', 'pkd_codes', 'source_id', 'pkd_checked_at']);
        });
    }
};

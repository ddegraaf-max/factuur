<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fachora 1.103: bedrijven met een goede online reputatie (Google Business-profiel via de
 * Places API). De beoordeling en het aantal reviews staan bij het bedrijf; ext_refreshed_at
 * bewaakt het Places-beleid (opgeslagen gegevens binnen 30 dagen verversen of weggooien).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->decimal('ext_rating', 2, 1)->nullable()->after('website');
            $table->unsignedInteger('ext_rating_count')->nullable()->after('ext_rating');
            $table->string('ext_maps_url', 300)->nullable()->after('ext_rating_count');
            $table->timestamp('ext_refreshed_at')->nullable()->after('ext_maps_url');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['ext_rating', 'ext_rating_count', 'ext_maps_url', 'ext_refreshed_at']);
        });
    }
};

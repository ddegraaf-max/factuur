<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fachora 1.96.4: welke registerbedrijven de klant al zelf benaderde
 * ({provider_id: {channels: [...], at: ...}}), zodat de knoppen op zijn
 * opdrachtpagina groen blijven en hij ziet wie hij al heeft gehad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->json('direct_contacted')->nullable()->after('selected_providers');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn('direct_contacted');
        });
    }
};

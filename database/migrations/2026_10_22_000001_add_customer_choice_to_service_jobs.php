<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Fachora 1.86: de klant kiest zelf tot vijf bedrijven en betaalt een klein
 * bedrag per aanvraag (FACHORA_REQUEST_FEE). De keuze en de betaling staan op
 * de opdracht; de aanbiedingen zelf blijven ServiceJobOffer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('service_jobs', 'selected_providers')) {
                $table->json('selected_providers')->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'request_fee')) {
                $table->decimal('request_fee', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'request_session_id')) {
                $table->string('request_session_id', 120)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'request_paid_at')) {
                $table->timestamp('request_paid_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            foreach (['selected_providers', 'request_fee', 'request_session_id', 'request_paid_at'] as $column) {
                if (Schema::hasColumn('service_jobs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

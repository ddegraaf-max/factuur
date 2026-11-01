<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Fachora 1.87: gratis voor de klant (met sms-code), het bedrijf betaalt per
 * lead. Het ontgrendelen staat op de aanbieding; de sms-controle op de opdracht.
 * Daarnaast import op aanvraag: bedrijven rond een nieuwe klant uit CEIDG halen,
 * en de informatiemail (art. 14 AVG) aan bedrijven uit het register.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_job_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('service_job_offers', 'lead_fee')) {
                $table->decimal('lead_fee', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('service_job_offers', 'lead_free')) {
                $table->boolean('lead_free')->default(false);
            }
            if (! Schema::hasColumn('service_job_offers', 'lead_session_id')) {
                $table->string('lead_session_id', 120)->nullable();
            }
            if (! Schema::hasColumn('service_job_offers', 'unlocked_at')) {
                $table->timestamp('unlocked_at')->nullable();
            }
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('service_jobs', 'phone_code')) {
                $table->string('phone_code', 12)->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'phone_code_sent_at')) {
                $table->timestamp('phone_code_sent_at')->nullable();
            }
            if (! Schema::hasColumn('service_jobs', 'phone_code_attempts')) {
                $table->unsignedTinyInteger('phone_code_attempts')->default(0);
            }
            if (! Schema::hasColumn('service_jobs', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable();
            }
            // Zolang gevuld: Fachora zoekt bedrijven rond deze klant in CEIDG (import op aanvraag).
            if (! Schema::hasColumn('service_jobs', 'provider_search_at')) {
                $table->timestamp('provider_search_at')->nullable();
            }
        });

        $this->providerColumns();
    }

    // Informatieplicht art. 14 AVG (1.87): wanneer het bedrijf uit het register is geïnformeerd, en een verwijderd profiel.
    private function providerColumns(): void
    {
        // Verantwoording (art. 5 lid 2 AVG): wat er wanneer met registergegevens is gedaan, zonder persoonsgegevens.
        if (! Schema::hasTable('fachora_privacy_log')) {
            Schema::create('fachora_privacy_log', function (Blueprint $table) {
                $table->id();
                $table->string('action', 40);
                $table->unsignedInteger('count')->default(0);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
        Schema::table('providers', function (Blueprint $table) {
            if (! Schema::hasColumn('providers', 'register_notice_at')) {
                $table->timestamp('register_notice_at')->nullable();
            }
            if (! Schema::hasColumn('providers', 'removed_at')) {
                $table->timestamp('removed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fachora_privacy_log');
        Schema::table('providers', function (Blueprint $table) {
            foreach (['register_notice_at', 'removed_at'] as $column) {
                if (Schema::hasColumn('providers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('service_job_offers', function (Blueprint $table) {
            foreach (['lead_fee', 'lead_free', 'lead_session_id', 'unlocked_at'] as $column) {
                if (Schema::hasColumn('service_job_offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('service_jobs', function (Blueprint $table) {
            foreach (['phone_code', 'phone_code_sent_at', 'phone_code_attempts', 'phone_verified_at', 'provider_search_at'] as $column) {
                if (Schema::hasColumn('service_jobs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

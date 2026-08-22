<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eigen, privacyvriendelijke bezoekersstatistieken voor de marketingpagina's.
 *
 * Geen cookies, geen externe dienst: per bezochte pagina één regel met datum,
 * pad, herkomst en een dagelijks wisselende bezoekershash (geen IP-opslag).
 * Zie App\Http\Middleware\TrackPageView voor het vullen en
 * MarketingStatsController voor het dashboard op /marketing-inzichten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->date('viewed_on')->index();
            $table->string('path', 190)->index();
            $table->string('referrer_host', 190)->nullable();
            $table->string('utm_source', 80)->nullable();
            $table->string('utm_medium', 80)->nullable();
            $table->string('utm_campaign', 80)->nullable();
            $table->string('device', 10)->default('desktop');
            $table->string('visitor_hash', 32);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['viewed_on', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};

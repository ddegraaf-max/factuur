<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kostenplafond op betaalde koppelingen (KvK, AI, Peppol, sms, post, CCBR).
 *
 * Eén regel per dienst + bereik + periode. Het bereik is 'platform' (alle
 * gebruikers samen), 'team:<id>' (één administratie) of 'alarm:<soort>' (de
 * alarmmail van die dag is verstuurd). De periode is een dag (2026-11-10) of
 * een maand (2026-11). CostGuard hoogt de tellers op met één UPDATE … WHERE
 * calls < plafond, zodat twee processen tegelijk nooit samen over het plafond
 * gaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_counters', function (Blueprint $table) {
            $table->id();
            $table->string('service', 30);
            $table->string('scope', 60);
            $table->string('period', 10);
            $table->unsignedInteger('calls')->default(0);
            $table->integer('cents')->default(0);
            $table->timestamps();
            $table->unique(['service', 'scope', 'period']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_counters');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online aanmaning (1.65.0): de laatste aanmaning vóór de deurwaarder, met een
 * eigen pagina waarop het bedrag elke dag met de wettelijke rente oploopt en
 * de klant met één klik reageert. Alles wat er gebeurt — verstuurd, geopend,
 * gereageerd — komt in het logboek en gaat mee in het dossier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('sent'); // sent | paid | transferred | withdrawn
            $table->string('debtor_type', 20)->default('business'); // business | consumer
            $table->string('sent_to', 180);
            $table->decimal('principal', 12, 2); // openstaand bij het versturen
            $table->boolean('with_interest')->default(true);
            $table->decimal('costs', 12, 2)->default(0); // incassokosten volgens de staffel, excl. btw
            $table->decimal('costs_vat', 12, 2)->default(0); // alleen als de schuldeiser geen btw kan verrekenen
            $table->unsignedSmallInteger('term_days');
            $table->date('deadline'); // tot en met deze dag kan de klant zonder incassokosten betalen
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->string('response', 20)->nullable(); // paid | promise | dispute
            $table->date('response_date')->nullable(); // betaald op, of belooft te betalen op
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expiry_notified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('payment_demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_demand_id')->constrained()->cascadeOnDelete();
            $table->string('event', 30); // sent | opened | paid | promise | dispute | expired | transferred | withdrawn | settled
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['payment_demand_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_demand_events');
        Schema::dropIfExists('payment_demands');
    }
};

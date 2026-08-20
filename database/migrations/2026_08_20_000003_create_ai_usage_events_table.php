<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Elke AI-actie (bonscan, offerteherkenning) is één regel: zo is het
        // gebruik per administratie en per maand te tellen — voor inzicht,
        // fair use en het bewaken van de AI-kosten.
        Schema::create('ai_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);            // receipt_scan | quote_parse
            $table->string('source', 20)->nullable(); // form | inbox_auto
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_events');
    }
};

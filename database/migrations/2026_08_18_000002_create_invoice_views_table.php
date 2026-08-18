<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inzagelog van het klantenportaal: elke keer dat de klant de factuur
        // bekijkt of de PDF downloadt komt hier een regel bij, zodat de
        // ondernemer kan zien óf en wanneer zijn factuur is ingezien.
        Schema::create('invoice_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('event', 20)->default('viewed'); // viewed | pdf
            $table->string('email', 180)->nullable();       // geverifieerd e-mailadres van de kijker
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('viewed_at');
            $table->index(['invoice_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_views');
    }
};

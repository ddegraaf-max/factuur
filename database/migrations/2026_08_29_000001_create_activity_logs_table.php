<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logboek: wie deed wat, wanneer — per administratie. Geen foreign keys naar
 * users/companies met cascade: een logregel moet blijven bestaan (en leesbaar
 * zijn) ook als de gebruiker of het onderwerp inmiddels is verwijderd.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 120)->nullable();
            $table->string('action', 40)->index();              // created, updated, deleted, sent, login, …
            $table->string('subject_type', 80)->nullable();     // factuur, offerte, klant, …
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label', 160)->nullable();   // "Factuur 2026-0004", "Klant Vries Design"
            $table->string('description', 255);
            $table->json('changes')->nullable();                // gewijzigde velden (zonder waarden van gevoelige velden)
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['company_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

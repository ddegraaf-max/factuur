<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Website per administratie (inhoud door AI gegenereerd, door de gebruiker bewerkt) en de leads van het contactformulier. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('published')->default(false);
            $table->json('content')->nullable();
            $table->json('answers')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('site_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            $table->text('message');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_leads');
        Schema::dropIfExists('company_sites');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Klantscore (1.71.0): per klant de laatste beoordeling — de score, de
 * signalen erachter en wat de openbare bronnen zeiden. Eén rij per klant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('grade', 2)->nullable();
            $table->json('signals')->nullable();
            // Wat de openbare bronnen zeiden, per bron (btw-nummer, KvK, insolventieregister).
            $table->json('sources')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('sources_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_checks');
    }
};

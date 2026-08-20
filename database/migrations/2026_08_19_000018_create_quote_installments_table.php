<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Termijnfacturen: een offerte in delen factureren (bijv. 30% bij opdracht,
 * 70% bij oplevering). Elke termijn wordt een gewone (concept)factuur;
 * invoice_id verwijst ernaar zodra hij is gemaakt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('description', 200);
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 12, 2); // incl. btw, momentopname bij aanmaak
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_installments');
    }
};

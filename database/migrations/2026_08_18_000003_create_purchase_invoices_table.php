<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inkoopfacturen (crediteuren): binnengekomen facturen van leveranciers,
        // handmatig ingeboekt of met een foto/PDF erbij. De BTW hieruit is de
        // voorbelasting (rubriek 5b) in het BTW-overzicht.
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('supplier_name', 180);
            $table->string('supplier_reference', 100)->nullable(); // factuurnummer van de leverancier
            $table->string('category', 60)->nullable();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->string('status', 20)->default('open'); // open | paid
            $table->date('paid_at')->nullable();
            $table->string('payment_method', 30)->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('vat_lines')->nullable(); // [{base, rate, vat}]

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'invoice_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};

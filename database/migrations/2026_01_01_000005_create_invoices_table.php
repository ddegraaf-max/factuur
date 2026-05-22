<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('number')->nullable(); // assigned when sent
            $table->string('reference')->nullable();
            $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->integer('payment_terms')->default(30);

            // Snapshot of customer details at time of invoicing (immutable for legal reasons)
            $table->string('customer_name');
            $table->string('customer_address_line')->nullable();
            $table->string('customer_postal_code')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_country', 2)->default('NL');
            $table->string('customer_vat_number')->nullable();
            $table->string('customer_kvk_number')->nullable();
            $table->string('customer_email')->nullable();

            // Totals (calculated and stored)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->json('vat_breakdown')->nullable(); // {"21": 105.00, "9": 9.00}

            $table->text('notes')->nullable();
            $table->text('footer')->nullable();

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'invoice_date']);
            $table->unique(['company_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

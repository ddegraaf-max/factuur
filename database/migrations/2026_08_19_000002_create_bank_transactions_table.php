<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banktransacties, geïmporteerd uit bankafschriften (CAMT.053 / MT940)
        // of later via een automatische bankkoppeling. Worden gekoppeld aan
        // facturen (bij + ontvangsten) of inkoopfacturen (afschrijvingen).
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->date('booking_date');
            $table->decimal('amount', 12, 2); // positief = bij, negatief = af
            $table->string('currency', 3)->default('EUR');
            $table->string('counterparty_name', 190)->nullable();
            $table->string('counterparty_iban', 40)->nullable();
            $table->text('description')->nullable();

            $table->string('status', 20)->default('open'); // open | matched | ignored
            $table->foreignId('matched_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('matched_purchase_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->string('source', 20)->default('camt053'); // camt053 | mt940
            $table->string('import_hash', 64); // dubbele import voorkomen
            $table->timestamps();

            $table->unique(['company_id', 'import_hash']);
            $table->index(['company_id', 'status', 'booking_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};

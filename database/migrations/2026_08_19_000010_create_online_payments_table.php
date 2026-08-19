<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Online betalingen (iDEAL via Mollie): elke betaalpoging vanuit het
        // klantenportaal wordt hier gevolgd. Zodra Mollie 'paid' meldt, wordt
        // er een gewone Payment op de factuur geboekt (idempotent).
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete(); // de geboekte betaling

            $table->string('mollie_id', 64)->unique();     // tr_xxx
            $table->string('checkout_url', 500)->nullable(); // om een open betaling te hervatten
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('open'); // open | paid | failed | canceled | expired
            $table->string('method', 30)->nullable();       // ideal, creditcard, ...
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        // Eigen Mollie-account per administratie; de key wordt versleuteld
        // opgeslagen (encrypted cast) en nooit naar de browser gestuurd.
        Schema::table('companies', function (Blueprint $table) {
            $table->text('mollie_api_key')->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('mollie_api_key');
        });
    }
};

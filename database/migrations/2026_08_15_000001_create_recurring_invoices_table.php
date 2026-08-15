<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            // Planning
            $table->string('frequency', 20)->default('monthly'); // weekly|monthly|quarterly|halfyearly|yearly
            $table->date('start_date');            // anker voor de dag-van-de-maand (bijv. altijd de 31e waar mogelijk)
            $table->date('next_run_on');           // eerstvolgende factuurdatum
            $table->date('end_date')->nullable();  // stopt automatisch na deze datum
            $table->boolean('auto_send')->default(false); // true = direct versturen, false = als concept klaarzetten
            $table->boolean('active')->default(true);

            // Snapshot van de factuurinhoud
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('payment_terms')->default(30);
            $table->json('lines');

            // Administratie
            $table->date('last_run_on')->nullable();
            $table->unsignedInteger('invoices_generated')->default(0);

            $table->timestamps();

            $table->index(['active', 'next_run_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Terugkerende inkoop ("vaste lasten"): huur, software, verzekeringen
        // e.d. worden automatisch periodiek als inkoopfactuur ingeboekt —
        // de BTW telt dan vanzelf mee als voorbelasting in de aangifte.
        Schema::create('recurring_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('supplier_name', 180);
            $table->string('category', 60)->nullable();
            $table->string('frequency', 20)->default('monthly');
            $table->date('start_date');
            $table->date('next_run_on');
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true);

            $table->json('vat_lines'); // [{base, rate, vat}] — zelfde vorm als inkoopfacturen
            // Vaste lasten gaan meestal via automatische incasso: dan wordt de
            // inkoopfactuur direct als betaald ingeboekt.
            $table->boolean('auto_paid')->default(true);
            $table->string('payment_method', 30)->nullable();
            $table->string('notes', 2000)->nullable();

            $table->date('last_run_on')->nullable();
            $table->unsignedInteger('purchases_generated')->default(0);
            $table->timestamps();

            $table->index(['active', 'next_run_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_purchases');
    }
};

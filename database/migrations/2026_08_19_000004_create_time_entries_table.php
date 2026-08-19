<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Urenregistratie: gewerkte uren per klant/project, met één klik om
        // te zetten naar een conceptfactuur. De duur staat in minuten (integer)
        // zodat er nooit zwevende-komma-gedoe in de administratie sluipt.
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->date('work_date');
            $table->string('project', 100)->nullable();
            $table->string('description', 500);
            $table->unsignedInteger('minutes')->default(0);
            $table->decimal('hourly_rate', 8, 2)->nullable(); // leeg = tarief van klant of bedrijf
            $table->boolean('billable')->default(true);
            $table->timestamp('timer_started_at')->nullable(); // gezet = timer loopt nog

            $table->timestamps();

            $table->index(['company_id', 'work_date']);
            $table->index(['company_id', 'invoice_id']);
        });

        // Uurtarieven: standaard op bedrijfsniveau, overschrijfbaar per klant
        // en per urenregel.
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('payment_terms');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('default_hourly_rate', 8, 2)->nullable()->after('default_payment_terms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('default_hourly_rate');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Verrekeningen: bedragen die de leverancier al heeft ontvangen of
            // inhoudt (bijv. door een deurwaarder ontvangen gelden, of een
            // aanbetaling). Ze verlagen het te betalen bedrag — nooit de
            // kosten of de voorbelasting.
            $table->json('deductions')->nullable()->after('vat_lines');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('deductions');
        });
    }
};

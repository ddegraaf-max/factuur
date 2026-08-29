<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Windykacja (Poolse markt): wanneer de factuur te koop is aangeboden aan de incassopartner (wykup wierzytelności). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('sale_requested_at')->nullable()->after('incasso_phase');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('sale_requested_at');
        });
    }
};

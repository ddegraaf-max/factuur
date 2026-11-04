<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1.102.1: algemene kosten (verzekering, incasso, kantoor) horen bij geen project.
 * Gemarkeerd = niet meer aanbieden in "Inkoopfacturen koppelen" op een project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->boolean('project_exempt')->default(false)->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('project_exempt');
        });
    }
};

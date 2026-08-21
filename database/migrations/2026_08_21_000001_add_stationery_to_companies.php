<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Eigen briefpapier: een paginavullende achtergrond (data-URL, net
            // als logo_data) waarop het template "stationery" alleen de
            // factuurinhoud zet. Marges bepalen waar de content mag beginnen.
            $table->longText('stationery_data')->nullable()->after('logo_scale');
            $table->unsignedSmallInteger('stationery_margin_top')->default(45)->after('stationery_data');
            $table->unsignedSmallInteger('stationery_margin_bottom')->default(25)->after('stationery_margin_top');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['stationery_data', 'stationery_margin_top', 'stationery_margin_bottom']);
        });
    }
};

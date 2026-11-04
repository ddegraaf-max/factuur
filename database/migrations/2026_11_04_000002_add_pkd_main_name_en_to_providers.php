<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 1.100: Engelse vertaling van de Poolse hoofdactiviteit uit CEIDG (App\Services\Fachora\PkdTranslator). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('pkd_main_name_en', 200)->nullable()->after('pkd_main_name');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('pkd_main_name_en');
        });
    }
};

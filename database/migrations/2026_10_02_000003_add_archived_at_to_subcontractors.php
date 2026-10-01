<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Een bedrijf dat al prijsaanvragen heeft gehad, kan niet worden verwijderd
 * zonder die geschiedenis te wissen. Verwijderen zet het daarom uit de pool
 * (archived_at); het blijft bij oude uitvragen staan en is terug te zetten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};

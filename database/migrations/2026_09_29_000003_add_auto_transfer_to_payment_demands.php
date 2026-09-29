<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online aanmaning (1.66.0): op verzoek gaat het dossier na de termijn vanzelf
 * naar de deurwaarder — een paar werkdagen later, zodat een betaling van de
 * laatste dag nog geboekt kan worden, en alleen als de klant niet heeft gereageerd.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_demands', function (Blueprint $table) {
            $table->boolean('auto_transfer')->default(false)->after('with_interest');
        });
    }

    public function down(): void
    {
        Schema::table('payment_demands', function (Blueprint $table) {
            $table->dropColumn('auto_transfer');
        });
    }
};

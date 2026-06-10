<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eén bedrijf = één account, zodat een verlopen proefperiode niet
        // omzeild kan worden met een nieuw account op dezelfde firma.
        //
        // Het KvK-nummer is de primaire sleutel hiervoor: het is verplicht bij
        // registratie, dus dit dicht het gat dat het optionele BTW-nummer laat.
        // NULL telt niet mee als duplicaat, dus oudere bedrijven zonder
        // ingevuld nummer blijven toegestaan.
        Schema::table('companies', function (Blueprint $table) {
            $table->unique('kvk_number');
            $table->unique('vat_number');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['vat_number']);
            $table->dropUnique(['kvk_number']);
        });
    }
};

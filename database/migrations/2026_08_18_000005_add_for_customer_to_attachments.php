<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // Bijlage bestemd voor de klant: gaat mee met de factuurmail en is
            // zichtbaar in het klantenportaal. Bestaande bijlagen blijven
            // intern (false) — die waren bijv. bewijs voor het incassodossier.
            $table->boolean('for_customer')->default(false)->after('file_data');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('for_customer');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Handelsnamen ook op offertes: de offerte-PDF en -mail volgen de
        // huisstijl van het gekozen profiel, en bij omzetten naar een factuur
        // erft die factuur de handelsnaam.
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('customer_id')
                ->constrained('brand_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });
    }
};

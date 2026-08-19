<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aanpasbare e-mailteksten: eigen onderwerp en tekst voor de factuur- en
 * offertemail, per administratie. Leeg = de standaardtekst (in de taal van
 * de klant). Zelfde opzet als reminder_settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('email_texts')->nullable()->after('reminder_settings');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('email_texts');
        });
    }
};

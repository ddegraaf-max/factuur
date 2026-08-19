<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Documenttaal per klant (nl of en). Facturen en offertes krijgen bij
        // het aanmaken een momentopname, zodat een later gewijzigde
        // klantinstelling verstuurde documenten niet verandert.
        Schema::table('customers', function (Blueprint $table) {
            $table->string('language', 2)->default('nl')->after('country');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('language', 2)->default('nl')->after('payment_terms');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('language', 2)->default('nl')->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('language');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('language');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};

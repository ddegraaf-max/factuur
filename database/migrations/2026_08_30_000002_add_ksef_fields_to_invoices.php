<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KSeF (Poolse markt): per factuur het toegekende KSeF-nummer, wanneer het
 * FA-XML is geëxporteerd/ingediend en de status ('exported' = XML gedownload,
 * 'accepted' = KSeF-nummer bekend).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('ksef_number', 40)->nullable()->after('sale_requested_at');
            $table->timestamp('ksef_sent_at')->nullable()->after('ksef_number');
            $table->string('ksef_status', 20)->nullable()->after('ksef_sent_at'); // exported|accepted
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['ksef_number', 'ksef_sent_at', 'ksef_status']);
        });
    }
};

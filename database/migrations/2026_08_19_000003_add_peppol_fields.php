<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Peppol-adres van de klant. Meestal afgeleid van het KvK-nummer
            // (0106:12345678); hier kan een afwijkend ID worden vastgelegd.
            $table->string('peppol_id', 50)->nullable()->after('vat_number');
            $table->boolean('peppol_available')->nullable()->after('peppol_id');
            $table->timestamp('peppol_checked_at')->nullable()->after('peppol_available');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('peppol_sent_at')->nullable()->after('scheduled_send_on');
            $table->string('peppol_reference')->nullable()->after('peppol_sent_at');
            $table->string('peppol_status', 20)->nullable()->after('peppol_reference');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['peppol_id', 'peppol_available', 'peppol_checked_at']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['peppol_sent_at', 'peppol_reference', 'peppol_status']);
        });
    }
};

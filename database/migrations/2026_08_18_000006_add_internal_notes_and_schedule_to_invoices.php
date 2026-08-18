<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Interne notitie: alleen zichtbaar in de app — nooit op de PDF,
            // in de mail of in het klantenportaal.
            $table->text('internal_notes')->nullable()->after('footer');
            // Ingepland versturen: op deze datum verstuurt de dagelijkse
            // taak het concept automatisch.
            $table->date('scheduled_send_on')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['internal_notes', 'scheduled_send_on']);
        });
    }
};

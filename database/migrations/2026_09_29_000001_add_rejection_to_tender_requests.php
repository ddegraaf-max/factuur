<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Een offerte afwijzen met een eigen bericht (1.64.0): de ondernemer wijst
 * één prijsopgave af terwijl de uitvraag open blijft voor de andere bedrijven.
 * Het bericht dat is gemaild blijft bij de aanvraag staan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_requests', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('responded_at');
            $table->text('reject_message')->nullable()->after('decline_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tender_requests', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'reject_message']);
        });
    }
};

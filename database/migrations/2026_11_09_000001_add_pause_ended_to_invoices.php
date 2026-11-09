<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Een pauze loopt niet stilletjes af (1.108.0): na de einddatum blijft de
 * factuur stilstaan tot de ondernemer akkoord geeft ("Pauze afgelopen"),
 * en de dag ervoor krijgt hij één keer een seintje.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Wanneer de dagelijkse taak zag dat de einddatum voorbij was.
            $table->timestamp('reminders_pause_ended_at')->nullable()->after('reminders_pause_reason');
            // Wanneer het seintje "loopt morgen af" is gegeven (één keer per einddatum).
            $table->timestamp('reminders_pause_warned_at')->nullable()->after('reminders_pause_ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['reminders_pause_ended_at', 'reminders_pause_warned_at']);
        });
    }
};

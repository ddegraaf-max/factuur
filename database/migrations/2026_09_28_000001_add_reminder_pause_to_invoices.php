<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pauzeknop op de factuur (1.58.0): zolang de pauze loopt gaat er geen
 * herinnering of aanmaning uit en kan de factuur niet naar incasso —
 * bijvoorbeeld bij een betalingsregeling of een lopende klacht.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('reminders_paused_at')->nullable()->after('thanks_sent_to');
            // Tot en met deze dag; leeg = tot je zelf hervat.
            $table->date('reminders_paused_until')->nullable()->after('reminders_paused_at');
            $table->string('reminders_pause_reason', 255)->nullable()->after('reminders_paused_until');
            // Zoveel dagen schuift het herinneringsschema op door eerdere pauzes,
            // zodat de klant na het hervatten niet elke dag post krijgt.
            $table->unsignedSmallInteger('reminder_shift_days')->default(0)->after('reminders_pause_reason');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['reminders_paused_at', 'reminders_paused_until', 'reminders_pause_reason', 'reminder_shift_days']);
        });
    }
};

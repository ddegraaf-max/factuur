<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creditnota's die via het dashboard ten onrechte op 'overdue' zijn gezet
 * (1.54.0): een creditnota heeft niets te innen en is dus nooit achterstallig.
 * Terug naar 'sent'; vanaf nu slaat Invoice::markOverdue() ze over.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('is_credit', true)
            ->where('status', 'overdue')
            ->update(['status' => 'sent']);
    }

    public function down(): void
    {
        // Niets terug te draaien: de oude status was een fout.
    }
};

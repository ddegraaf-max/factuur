<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In welke uitbetaling van Mollie zit een online betaling? Mollie maakt niet
 * per betaling over maar gebundeld ("settlement"), minus kosten. Met kenmerk,
 * datum en totaal van die uitbetaling kan de boekhouder het bedrag op het
 * bankafschrift aan de facturen koppelen (rapport Mollie-betalingen, 1.81.0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_payments', function (Blueprint $table) {
            $table->string('settlement_id', 64)->nullable()->after('paid_at')->index();   // stl_xxx
            $table->string('settlement_reference', 64)->nullable()->after('settlement_id'); // 1234567.2610.03 (staat op het bankafschrift)
            $table->string('settlement_status', 20)->nullable()->after('settlement_reference'); // open | pending | paidout | failed
            $table->date('settled_on')->nullable()->after('settlement_status');
            $table->decimal('settlement_total', 12, 2)->nullable()->after('settled_on'); // totaal van de uitbetaling (na kosten)
            $table->timestamp('settlement_checked_at')->nullable()->after('settlement_total');
        });
    }

    public function down(): void
    {
        Schema::table('online_payments', function (Blueprint $table) {
            $table->dropColumn(['settlement_id', 'settlement_reference', 'settlement_status', 'settled_on', 'settlement_total', 'settlement_checked_at']);
        });
    }
};

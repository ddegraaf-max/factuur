<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Afleverstatus van een sms (1.76.0): Smstools meldt via een webhook of een
 * bericht is afgeleverd. Zo staat er bij een uitvraag niet alleen "sms
 * verstuurd" maar ook "afgeleverd" of "niet afgeleverd".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            // pending | delivered | failed | unknown
            $table->string('delivery_status', 20)->nullable()->after('error');
            $table->unsignedSmallInteger('delivery_code')->nullable()->after('delivery_status');
            $table->string('delivery_detail', 200)->nullable()->after('delivery_code');
            $table->timestamp('delivered_at')->nullable()->after('delivery_detail');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'delivery_code', 'delivery_detail', 'delivered_at']);
        });
    }
};

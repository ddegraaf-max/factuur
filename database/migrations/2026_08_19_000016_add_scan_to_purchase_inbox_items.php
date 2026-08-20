<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postvak IN automatisch verwerken: het herkenresultaat (scan & herken)
 * wordt op het postvak-item bewaard als boekingsvoorstel, zodat de
 * gebruiker alleen nog hoeft te bevestigen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_inbox_items', function (Blueprint $table) {
            $table->json('scan')->nullable()->after('status');
            $table->string('scan_error', 300)->nullable()->after('scan');
            $table->timestamp('scanned_at')->nullable()->after('scan_error');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_inbox_items', function (Blueprint $table) {
            $table->dropColumn(['scan', 'scan_error', 'scanned_at']);
        });
    }
};

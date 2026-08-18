<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Geheime sleutel voor de klantportaal-link. 64 hex-tekens (256 bits
            // entropie) — niet te raden, en uniek per factuur zodat een link
            // nooit toegang geeft tot andere facturen.
            $table->string('portal_token', 64)->nullable()->unique()->after('number');
        });

        // Bestaande, al verstuurde facturen krijgen met terugwerkende kracht een
        // token, zodat de portaalknop ook in herinneringen voor oude facturen werkt.
        DB::table('invoices')
            ->where('status', '!=', 'draft')
            ->whereNull('portal_token')
            ->orderBy('id')
            ->chunkById(200, function ($invoices) {
                foreach ($invoices as $invoice) {
                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update(['portal_token' => bin2hex(random_bytes(32))]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['portal_token']);
            $table->dropColumn('portal_token');
        });
    }
};

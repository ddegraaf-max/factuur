<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offertes digitaal ondertekenen in het klantenportaal. De combinatie
        // geverifieerd e-mailadres + naam + handtekening + tijdstip + IP vormt
        // het bewijsdossier van de elektronische handtekening.
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('portal_token', 64)->nullable()->unique()->after('number');
            $table->string('signed_name', 120)->nullable()->after('rejected_at');
            $table->longText('signature_data')->nullable()->after('signed_name'); // PNG data-URL
            $table->timestamp('signed_at')->nullable()->after('signature_data');
            $table->string('signed_ip', 45)->nullable()->after('signed_at');
            $table->string('signed_email', 180)->nullable()->after('signed_ip');
            $table->string('decline_reason', 500)->nullable()->after('signed_email');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'portal_token', 'signed_name', 'signature_data',
                'signed_at', 'signed_ip', 'signed_email', 'decline_reason',
            ]);
        });
    }
};

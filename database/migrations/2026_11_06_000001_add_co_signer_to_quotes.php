<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1.107: een offerte die door twee personen moet worden ondertekend (bijv. twee
 * bestuurders die samen tekenbevoegd zijn). De tweede ondertekenaar heeft een eigen
 * naam, e-mailadres en portaallink; zijn handtekening krijgt eigen kolommen naast die
 * van de klant. De offerte is pas geaccepteerd als beide handtekeningen zijn gezet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedTinyInteger('signers_required')->default(1)->after('decline_reason');
            $table->string('co_signer_name', 120)->nullable()->after('signers_required');
            $table->string('co_signer_email', 180)->nullable()->after('co_signer_name');
            $table->string('co_signer_token', 64)->nullable()->unique()->after('co_signer_email');
            $table->timestamp('co_signer_invited_at')->nullable()->after('co_signer_token');
            $table->string('co_signed_name', 120)->nullable()->after('co_signer_invited_at');
            $table->longText('co_signature_data')->nullable()->after('co_signed_name');
            $table->timestamp('co_signed_at')->nullable()->after('co_signature_data');
            $table->string('co_signed_ip', 45)->nullable()->after('co_signed_at');
            $table->string('co_signed_email', 180)->nullable()->after('co_signed_ip');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'signers_required', 'co_signer_name', 'co_signer_email', 'co_signer_token', 'co_signer_invited_at',
                'co_signed_name', 'co_signature_data', 'co_signed_at', 'co_signed_ip', 'co_signed_email',
            ]);
        });
    }
};

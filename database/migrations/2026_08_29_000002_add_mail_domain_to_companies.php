<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mail vanaf eigen domein: per administratie een bij Resend geverifieerd domein + afzenderadres. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('mail_domain', 190)->nullable()->after('accountant_email');
            $table->string('mail_domain_id', 80)->nullable()->after('mail_domain');
            $table->string('mail_domain_status', 30)->nullable()->after('mail_domain_id'); // pending, verified, failed
            $table->string('mail_from_address', 190)->nullable()->after('mail_domain_status');
            $table->json('mail_domain_records')->nullable()->after('mail_from_address');
            $table->timestamp('mail_domain_checked_at')->nullable()->after('mail_domain_records');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['mail_domain', 'mail_domain_id', 'mail_domain_status', 'mail_from_address', 'mail_domain_records', 'mail_domain_checked_at']);
        });
    }
};

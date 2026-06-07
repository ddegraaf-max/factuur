<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Id van de in Resend ingeplande proefherinnering (zodat we hem
            // kunnen annuleren als er eerder een abonnement wordt afgesloten).
            $table->string('trial_reminder_email_id')->nullable()->after('trial_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('trial_reminder_email_id');
        });
    }
};

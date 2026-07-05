<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Id van de in Resend ingeplande "proefperiode is afgelopen"-mail
            // (zodat we hem kunnen annuleren als er alsnog een abonnement
            // wordt afgesloten voordat de proef eindigt).
            $table->string('trial_ended_email_id')->nullable()->after('trial_reminder_email_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('trial_ended_email_id');
        });
    }
};

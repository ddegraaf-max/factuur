<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Wanneer we de "abonnement opgezegd"-mail hebben verstuurd, zodat we
            // hem niet dubbel sturen bij herhaalde webhook-events. Wordt weer op
            // null gezet zodra het abonnement opnieuw actief is.
            $table->timestamp('subscription_cancel_emailed_at')->nullable()->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('subscription_cancel_emailed_at');
        });
    }
};

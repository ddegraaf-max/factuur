<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('currency');
            $table->string('subscription_status')->nullable()->after('trial_ends_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
            $table->string('stripe_customer_id')->nullable()->after('subscription_ends_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
        });

        // Geef bestaande bedrijven een verse proefperiode van 14 dagen,
        // zodat huidige gebruikers niet meteen worden buitengesloten.
        DB::table('companies')
            ->whereNull('trial_ends_at')
            ->update(['trial_ends_at' => now()->addDays(14)]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'trial_ends_at',
                'subscription_status',
                'subscription_ends_at',
                'stripe_customer_id',
                'stripe_subscription_id',
            ]);
        });
    }
};

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
            // Abonnementssmaak: 'basis' (zonder AI) of 'slim' (met AI-functies).
            // Bestaande abonnees blijven op 'basis' tot ze overstappen.
            $table->string('plan', 20)->default('basis')->after('subscription_status');
            // Vrijgesteld: volledige toegang (incl. AI) zonder abonnement —
            // voor het eigen beheerdersaccount.
            $table->boolean('is_exempt')->default(false)->after('plan');
        });

        // Het eigen Creditline-account hoeft nooit te betalen: alle
        // administraties van de beheerder direct vrijstellen.
        $userIds = DB::table('users')->where('email', 'd.degraaf@creditline.nl')->pluck('id');
        if ($userIds->isNotEmpty()) {
            $companyIds = DB::table('users')->whereIn('id', $userIds)->pluck('company_id');
            if (Schema::hasTable('company_user')) {
                $companyIds = $companyIds->merge(
                    DB::table('company_user')->whereIn('user_id', $userIds)->pluck('company_id')
                );
            }
            $companyIds = $companyIds->filter()->unique()->values();
            if ($companyIds->isNotEmpty()) {
                DB::table('companies')->whereIn('id', $companyIds)->update(['is_exempt' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['plan', 'is_exempt']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Meerdere administraties onder één inlog: lidmaatschappen met een rol
        // per administratie. users.company_id blijft de ACTIEVE administratie
        // (en users.role de rol dáárin) — zo blijft alle bestaande
        // company-scoping ongewijzigd werken.
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('staff');
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // Backfill: elke bestaande gebruiker wordt lid van zijn huidige bedrijf.
        foreach (DB::table('users')->whereNotNull('company_id')->get(['id', 'company_id', 'role']) as $user) {
            DB::table('company_user')->insert([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'role' => $user->role ?: 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};

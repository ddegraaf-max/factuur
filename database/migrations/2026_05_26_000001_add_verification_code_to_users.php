<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_code', 6)->nullable()->after('email_verified_at');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            $table->unsignedTinyInteger('verification_code_attempts')->default(0)->after('verification_code_expires_at');
            $table->timestamp('verification_code_sent_at')->nullable()->after('verification_code_attempts');
        });

        // Backfill: existing accounts predate verification — mark them verified
        // so they aren't locked out by the new login gate.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_code_expires_at',
                'verification_code_attempts',
                'verification_code_sent_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bankkoppeling via Ponto Connect: één koppeling per administratie (OAuth-
 * tokens, versleuteld) met daaronder de geautoriseerde rekeningen. Opgehaalde
 * transacties landen in bank_transactions (source 'ponto').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponto_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 20)->default('active'); // active | needs_reauth
            $table->string('scope', 100)->nullable();
            $table->boolean('sandbox')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('ponto_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ponto_connection_id')->constrained('ponto_connections')->cascadeOnDelete();
            $table->uuid('ponto_id');
            $table->string('iban', 40)->nullable();
            $table->string('name', 190)->nullable();
            $table->string('holder_name', 190)->nullable();
            $table->string('bank_name', 190)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('current_balance', 14, 2)->nullable();
            $table->decimal('available_balance', 14, 2)->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('authorization_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'ponto_id']);
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->foreignId('ponto_account_id')->nullable()->after('import_hash')->constrained('ponto_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ponto_account_id');
        });
        Schema::dropIfExists('ponto_accounts');
        Schema::dropIfExists('ponto_connections');
    }
};

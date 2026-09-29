<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sms bij uitvragen (1.69.0): een logboek van verstuurde berichten, korte
 * adressen voor in een sms, en op de aanvraag het moment van de laatste sms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 20);
            $table->string('sender', 14);
            $table->text('body');
            $table->unsignedTinyInteger('segments')->default(1);
            // sent | failed
            $table->string('status', 20)->default('failed');
            $table->string('provider_id', 80)->nullable();
            $table->string('error', 255)->nullable();
            $table->nullableMorphs('subject');
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });

        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->text('url');
            // Om een bestaand kort adres terug te vinden zonder op de hele tekst te zoeken.
            $table->string('url_hash', 40)->index();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::table('tender_requests', function (Blueprint $table) {
            $table->timestamp('sms_at')->nullable()->after('reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('tender_requests', function (Blueprint $table) {
            $table->dropColumn('sms_at');
        });
        Schema::dropIfExists('short_links');
        Schema::dropIfExists('sms_messages');
    }
};

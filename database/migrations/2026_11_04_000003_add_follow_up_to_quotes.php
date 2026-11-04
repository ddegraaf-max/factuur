<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1.102: offerteopvolging — bijhouden of de klant de portaallink opende, automatische
 * herinneringen (log per offerte, aan/uit per offerte) en de instellingen per administratie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('portal_viewed_at')->nullable()->after('sent_at');
            $table->unsignedInteger('portal_views')->default(0)->after('portal_viewed_at');
            $table->boolean('reminders_enabled')->default(true)->after('portal_views');
            $table->json('reminder_log')->nullable()->after('reminders_enabled');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->json('quote_reminder_settings')->nullable()->after('quote_valid_days');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['portal_viewed_at', 'portal_views', 'reminders_enabled', 'reminder_log']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('quote_reminder_settings');
        });
    }
};

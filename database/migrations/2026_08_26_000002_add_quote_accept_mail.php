<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Bevestigingsmail naar de klant zodra hij een offerte accepteert
            // (digitaal ondertekent in het portaal). Een bevestiging van een
            // akkoord verwacht iedereen, dus standaard aan.
            $table->boolean('quote_accept_mail_enabled')->default(true)->after('review_url');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('accept_mail_sent_at')->nullable()->after('decline_reason');
            $table->string('accept_mail_sent_to')->nullable()->after('accept_mail_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['accept_mail_sent_at', 'accept_mail_sent_to']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('quote_accept_mail_enabled');
        });
    }
};

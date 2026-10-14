<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Een verstuurde offerte laten vervallen (bijv. omdat er een nieuwe
            // voor in de plaats komt): wanneer, waarom, welke offerte hem
            // vervangt en of de klant daarover is gemaild.
            $table->timestamp('withdrawn_at')->nullable()->after('accept_mail_sent_to');
            $table->text('withdrawal_note')->nullable()->after('withdrawn_at');
            $table->foreignId('replaced_by_quote_id')->nullable()->after('withdrawal_note')->constrained('quotes')->nullOnDelete();
            $table->timestamp('withdrawal_mail_sent_at')->nullable()->after('replaced_by_quote_id');
            $table->string('withdrawal_mail_sent_to')->nullable()->after('withdrawal_mail_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by_quote_id');
            $table->dropColumn(['withdrawn_at', 'withdrawal_note', 'withdrawal_mail_sent_at', 'withdrawal_mail_sent_to']);
        });
    }
};

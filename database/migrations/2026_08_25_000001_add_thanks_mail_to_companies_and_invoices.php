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
            // Bedankmail na betaling: automatisch versturen zodra een factuur
            // volledig is voldaan (bankkoppeling, iDEAL; handmatig via vinkje).
            $table->boolean('thanks_mail_enabled')->default(true)->after('email_texts');
            // Optionele reviewlink (Google, Trustpilot…) — geeft de bedankmail
            // een knop "Laat een review achter".
            $table->string('review_url', 500)->nullable()->after('thanks_mail_enabled');
        });

        // Bestaande administraties: niets verandert ongevraagd. Zij zetten de
        // bedankmail zelf aan bij Instellingen → E-mailteksten. Nieuwe
        // administraties (en demo's) krijgen 'm standaard aan.
        DB::table('companies')->update(['thanks_mail_enabled' => false]);

        Schema::table('invoices', function (Blueprint $table) {
            // Eén bedankmail per factuur; het adres bewaren we voor de historie.
            $table->timestamp('thanks_sent_at')->nullable()->after('paid_at');
            $table->string('thanks_sent_to')->nullable()->after('thanks_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['thanks_sent_at', 'thanks_sent_to']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['thanks_mail_enabled', 'review_url']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1.109: brieven per post automatisch versturen (fachora:letters via Pingen). Per bedrijf
 * onthouden we wanneer de brief de deur uit ging en het kenmerk bij de postdienst, zodat
 * niemand twee keer een brief krijgt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->timestamp('letter_sent_at')->nullable()->after('register_notice_at');
            $table->string('letter_ref', 64)->nullable()->after('letter_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['letter_sent_at', 'letter_ref']);
        });
    }
};

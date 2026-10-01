<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offertecheck (1.75.0): elke binnengekomen prijsopgave krijgt een beoordeling
 * van de AI — prijs, werkzaamheden, wat er wel en niet in zit, vragen en een
 * advies. De beoordeling staat als JSON bij de prijsopgave.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_requests', function (Blueprint $table) {
            $table->text('review')->nullable()->after('attachment_path');
            $table->timestamp('reviewed_at')->nullable()->after('review');
            // Kort waarom de beoordeling niet lukte, zodat de scheduler niet blijft proberen.
            $table->string('review_error', 200)->nullable()->after('reviewed_at');
            $table->unsignedTinyInteger('review_attempts')->default(0)->after('review_error');
        });
    }

    public function down(): void
    {
        Schema::table('tender_requests', function (Blueprint $table) {
            $table->dropColumn(['review', 'reviewed_at', 'review_error', 'review_attempts']);
        });
    }
};

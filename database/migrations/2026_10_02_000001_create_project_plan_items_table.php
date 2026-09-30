<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projectplanning (1.74.0): per project een tijdslijn van onderdelen. Een
 * gegunde uitvraag wordt vanzelf een onderdeel (met de onderaannemer en de
 * gewenste startweek); eigen onderdelen kunnen erbij. Per onderdeel gaat er
 * automatisch een vooraankondiging en een herinnering naar de onderaannemer,
 * en als een eerder onderdeel vlot klaar is, een verzoek om eerder te beginnen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // De gunning waar dit onderdeel uit komt; leeg bij een eigen onderdeel.
            $table->foreignId('tender_round_id')->nullable()->constrained('tender_rounds')->nullOnDelete();
            $table->foreignId('subcontractor_id')->nullable()->constrained('subcontractors')->nullOnDelete();
            $table->string('title', 160);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            // planned | started | done
            $table->string('status', 20)->default('planned');
            $table->date('done_on')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->text('notes')->nullable();
            // Geheime link voor de onderaannemer: bevestigen, een probleem melden
            // of antwoorden op een verzoek om eerder te beginnen.
            $table->string('token', 64)->unique();
            // Wat er automatisch is verstuurd, zodat het één keer gebeurt.
            $table->timestamp('headsup_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('problem')->nullable();
            $table->timestamp('problem_at')->nullable();
            // Het lopende verzoek om eerder te beginnen.
            $table->date('request_start')->nullable();
            $table->timestamp('request_sent_at')->nullable();
            // accepted | counter | declined
            $table->string('request_answer', 20)->nullable();
            $table->date('request_answer_start')->nullable();
            $table->text('request_message')->nullable();
            $table->timestamp('request_answered_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'sort']);
            $table->index(['starts_on', 'status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            // Automatisch vragen of de volgende partij eerder kan als een onderdeel vlot klaar is.
            $table->boolean('auto_earlier')->default(true)->after('agreed_price');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('auto_earlier');
        });
        Schema::dropIfExists('project_plan_items');
    }
};

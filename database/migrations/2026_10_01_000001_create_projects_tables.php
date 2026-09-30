<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projecten (1.73.0): de paraplu over offertes, facturen, inkoopfacturen,
 * uren, ritten en uitvragen, met een voorcalculatie per kostensoort. Zo is per
 * project te zien wat er is afgesproken, gefactureerd en uitgegeven, en wat
 * er overblijft.
 *
 * De tabellen staan voluit (geen lus): Larastan leest de kolommen uit de
 * migraties en volgt geen lus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 20);
            $table->string('name', 160);
            // open | closed
            $table->string('status', 20)->default('open');
            $table->string('location', 160)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('description')->nullable();
            // De afgesproken verkoopprijs, als die niet uit een offerte komt.
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
        });

        // De voorcalculatie: per regel een kostensoort en een bedrag exclusief btw.
        Schema::create('project_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // labour | material | subcontract | other
            $table->string('kind', 20);
            $table->string('description', 200);
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->nullOnDelete();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->nullOnDelete();
        });
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->nullOnDelete();
        });
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->nullOnDelete();
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->nullOnDelete();
        });
        Schema::table('tender_rounds', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tender_rounds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::dropIfExists('project_budget_lines');
        Schema::dropIfExists('projects');
    }
};

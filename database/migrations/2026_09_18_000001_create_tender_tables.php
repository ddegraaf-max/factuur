<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uitvragen bij onderaannemers (1.57.0): werkpakketten (fundering, HSB,
 * metselwerk …), een pool van bedrijven per pakket, en per project een
 * uitvraagronde met één aanvraag per bedrijf — elk met een geheime tokenlink
 * waarop het bedrijf prijs en beschikbaarheid doorgeeft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable(); // wat een bedrijf nodig heeft om te kunnen prijzen
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['company_id', 'sort_order']);
        });

        Schema::create('subcontractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('contact_name', 120)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('website', 180)->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 20)->default('manual'); // manual | import | suggested
            $table->timestamps();
            $table->index(['company_id', 'name']);
        });

        Schema::create('subcontractor_work_package', function (Blueprint $table) {
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_package_id')->constrained()->cascadeOnDelete();
            $table->primary(['subcontractor_id', 'work_package_id']);
        });

        Schema::create('tender_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_package_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('location', 160)->nullable(); // postcode + plaats; het volledige adres pas bij gunning
            $table->string('start_week', 12)->nullable();
            $table->date('deadline');
            $table->decimal('budget', 12, 2)->nullable(); // eigen calculatie excl. btw — alleen ter vergelijking
            $table->string('status', 20)->default('open'); // open | awarded | closed
            $table->unsignedBigInteger('awarded_request_id')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('tender_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('sent'); // sent | responded | declined | awarded | rejected
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->decimal('price', 12, 2)->nullable(); // excl. btw
            $table->string('available_week', 12)->nullable();
            $table->date('valid_until')->nullable();
            $table->text('remarks')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->timestamps();
            $table->index(['tender_round_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_requests');
        Schema::dropIfExists('tender_rounds');
        Schema::dropIfExists('subcontractor_work_package');
        Schema::dropIfExists('subcontractors');
        Schema::dropIfExists('work_packages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Demo op aanvraag (1.86.0): wie vroeg wanneer toegang, of de eigenaar
        // het goedkeurde, en de persoonlijke link (token) met vervaldatum.
        // review_token zit in de mail aan de eigenaar (beoordelen zonder inloggen).
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 40);
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('token', 64)->unique();
            $table->string('review_token', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('starts')->default(0);
            $table->timestamp('last_started_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['brand', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};

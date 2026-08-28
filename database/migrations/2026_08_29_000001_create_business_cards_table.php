<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Digitaal visitekaartje: publieke pagina per administratie onder een eigen
 * slug (easyinvoice.nl/k/{slug}); dezelfde slug dient straks voor de website.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('public_slug', 60)->nullable()->unique();
        });

        Schema::create('business_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('published')->default(false);
            $table->string('contact_name', 120)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('tagline', 160)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('linkedin_url', 200)->nullable();
            $table->boolean('show_kvk')->default(true);
            $table->boolean('show_vat')->default(false);
            $table->boolean('show_address')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_cards');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['public_slug']);
            $table->dropColumn('public_slug');
        });
    }
};

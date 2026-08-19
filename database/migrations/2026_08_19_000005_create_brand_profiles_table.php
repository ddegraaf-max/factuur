<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Handelsnamen: één administratie (zelfde KvK/BTW/nummering), maar
        // facturen versturen onder verschillende namen met elk hun eigen
        // huisstijl — naam, logo, kleur, sjabloon en voetnoot.
        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 190);                      // de handelsnaam op de factuur
            $table->longText('logo_data')->nullable();        // eigen logo (base64-data-URL)
            $table->unsignedSmallInteger('logo_scale')->default(100);
            $table->string('brand_color', 7)->nullable();     // leeg = kleur van het bedrijf
            $table->string('invoice_template', 20)->nullable(); // leeg = sjabloon van het bedrijf
            $table->text('invoice_footer')->nullable();       // leeg = voetnoot van het bedrijf

            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // Wordt een handelsnaam verwijderd, dan vallen de facturen terug op
        // de standaard huisstijl (nullOnDelete).
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('customer_id')
                ->constrained('brand_profiles')->nullOnDelete();
        });
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('customer_id')
                ->constrained('brand_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });
        Schema::dropIfExists('brand_profiles');
    }
};

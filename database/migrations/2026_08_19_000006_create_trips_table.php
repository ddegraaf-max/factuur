<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kilometerregistratie: zakelijke ritten, optioneel door te belasten
        // aan een klant (conceptfactuur) of puur voor de eigen administratie.
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->date('trip_date');
            $table->string('from_location', 190);
            $table->string('to_location', 190);
            $table->boolean('round_trip')->default(false); // informatief op de factuurregel
            $table->string('description', 500)->nullable(); // doel van de rit (optioneel)
            $table->decimal('kilometers', 8, 1);            // totaal (retour zit er al in)
            $table->decimal('rate', 6, 2)->nullable();      // leeg = standaardtarief bedrijf
            $table->boolean('billable')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'trip_date']);
            $table->index(['company_id', 'invoice_id']);
        });

        // Standaard kilometervergoeding; € 0,23 is het onbelaste tarief van
        // de Belastingdienst. Per rit te overschrijven.
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('default_km_rate', 6, 2)->default(0.23)->after('default_hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('default_km_rate');
        });
    }
};

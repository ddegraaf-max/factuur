<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Strippenkaarten: vooraf betaalde urenbundels per klant. Geschreven
        // uren worden automatisch van het tegoed afgeschreven (time_card_id op
        // de urenregel) en komen dan niet meer in "Klaar om te factureren".
        Schema::create('time_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete(); // de verkoopfactuur

            $table->string('name', 190);              // bijv. "Strippenkaart 10 uur"
            $table->unsignedInteger('total_minutes'); // het gekochte tegoed
            $table->decimal('price', 12, 2);          // bundelprijs (volgens de prijsmodus)
            $table->date('valid_until')->nullable();  // optioneel: geldigheid van het tegoed

            $table->timestamps();

            $table->index(['company_id', 'customer_id']);
        });

        // Wordt een kaart verwijderd, dan komen de gedekte uren weer vrij
        // als gewone factureerbare uren (nullOnDelete).
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('time_card_id')->nullable()->after('invoice_id')
                ->constrained('time_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('time_card_id');
        });
        Schema::dropIfExists('time_cards');
    }
};

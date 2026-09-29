<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online aanmaning zonder account (1.67.0): iedereen maakt op de website een
 * aanmaning, bevestigt zijn e-mailadres via een link en pas dan staat de
 * pagina online en gaat de mail naar de klant. Zo'n aanmaning hoort niet bij
 * een administratie of factuur: schuldeiser, klant en factuur staan erbij.
 * De schuldeiser volgt haar via een eigen geheime link (creditor_key).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_demands', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->unsignedBigInteger('invoice_id')->nullable()->change();

            $table->string('creditor_key', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirm_ip', 45)->nullable();
            $table->string('creator_ip', 45)->nullable();
            $table->unsignedSmallInteger('confirm_mails')->default(0);

            $table->string('creditor_name', 160)->nullable();
            $table->string('creditor_email', 180)->nullable();
            $table->string('creditor_kvk', 20)->nullable();
            $table->string('creditor_iban', 40)->nullable();
            $table->string('creditor_address', 300)->nullable();
            $table->string('creditor_phone', 40)->nullable();
            $table->boolean('creditor_no_vat')->default(false); // kan geen btw verrekenen: btw over de incassokosten

            $table->string('debtor_name', 160)->nullable();
            $table->string('debtor_kvk', 20)->nullable();
            $table->string('debtor_address', 300)->nullable();
            $table->json('debtor_facts')->nullable(); // wat het handelsregister over de klant zegt

            $table->string('invoice_number', 60)->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2)->nullable();

            $table->index('creditor_email');
        });

        Schema::table('payment_demand_events', function (Blueprint $table) {
            // Wie het was: debtor | creditor | bot. Alleen de klant telt als 'geopend'.
            $table->string('actor', 20)->nullable()->after('event');
        });

        // Kopie van de factuur die de schuldeiser meestuurt. Apart, zodat het
        // bestand niet bij elke weergave van de aanmaning wordt geladen.
        Schema::create('payment_demand_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_demand_id')->constrained()->cascadeOnDelete();
            $table->string('filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->longText('file_data'); // base64, net als bij de andere bijlagen
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_demand_files');

        Schema::table('payment_demand_events', function (Blueprint $table) {
            $table->dropColumn('actor');
        });

        Schema::table('payment_demands', function (Blueprint $table) {
            $table->dropIndex(['creditor_email']);
            $table->dropColumn([
                'creditor_key', 'confirmed_at', 'confirm_ip', 'creator_ip', 'confirm_mails',
                'creditor_name', 'creditor_email', 'creditor_kvk', 'creditor_iban', 'creditor_address', 'creditor_phone', 'creditor_no_vat',
                'debtor_name', 'debtor_kvk', 'debtor_address', 'debtor_facts',
                'invoice_number', 'invoice_date', 'due_date', 'amount',
            ]);
        });
    }
};

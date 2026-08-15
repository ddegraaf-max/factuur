<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('number')->nullable();          // pas bij versturen toegekend
            $table->string('reference')->nullable();
            // concept | verstuurd | geaccepteerd | afgewezen | verlopen
            $table->string('status', 20)->default('draft');

            $table->date('quote_date');
            $table->date('valid_until');

            // Snapshot van de klantgegevens, net als bij een factuur
            $table->string('customer_name');
            $table->string('customer_address_line')->nullable();
            $table->string('customer_postal_code')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_country', 2)->default('NL');
            $table->string('customer_vat_number')->nullable();
            $table->string('customer_kvk_number')->nullable();
            $table->string('customer_email')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('vat_breakdown')->nullable();

            $table->text('intro')->nullable();   // begeleidende tekst bovenaan
            $table->text('notes')->nullable();
            $table->text('footer')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Zodra de offerte een factuur wordt, leggen we de koppeling vast.
            $table->foreignId('converted_invoice_id')->nullable()
                ->constrained('invoices')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'quote_date']);
            $table->unique(['company_id', 'number']);
        });

        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->string('unit', 30)->default('stuk');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->decimal('line_subtotal', 12, 2)->default(0);
            $table->decimal('line_vat', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('quote_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'year']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('quote_number_format')->nullable()->after('invoice_number_format');
            $table->unsignedInteger('quote_valid_days')->default(30)->after('quote_number_format');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['quote_number_format', 'quote_valid_days']);
        });
        Schema::dropIfExists('quote_sequences');
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
    }
};

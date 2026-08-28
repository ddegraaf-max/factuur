<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatische incasso (SEPA Direct Debit): incassant-ID op de administratie,
 * machtiging per klant, en incassobatches (pain.008-bestanden voor de bank).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('sepa_creditor_id', 35)->nullable()->after('iban');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('mandate_reference', 35)->nullable()->after('payment_terms');
            $table->string('mandate_iban', 34)->nullable()->after('mandate_reference');
            $table->string('mandate_holder', 70)->nullable()->after('mandate_iban');
            $table->date('mandate_signed_on')->nullable()->after('mandate_holder');
            $table->string('mandate_type', 4)->nullable()->after('mandate_signed_on');      // CORE (particulier/zakelijk) of B2B
            $table->string('mandate_status', 12)->nullable()->after('mandate_type');      // active | revoked
            $table->timestamp('mandate_first_collected_at')->nullable()->after('mandate_status');
        });

        Schema::create('direct_debit_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 35);                 // MsgId / PmtInfId-basis
            $table->date('collection_date');
            $table->unsignedInteger('count');
            $table->decimal('total', 12, 2);
            $table->json('lines');                          // per factuur: id, nummer, klant, bedrag, e2e, machtiging, iban, seq
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'collection_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('direct_debit_batch_id')->nullable()->after('incasso_sent_at')->constrained('direct_debit_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropConstrainedForeignId('direct_debit_batch_id'));
        Schema::dropIfExists('direct_debit_batches');
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['mandate_reference', 'mandate_iban', 'mandate_holder', 'mandate_signed_on', 'mandate_type', 'mandate_status', 'mandate_first_collected_at']));
        Schema::table('companies', fn (Blueprint $t) => $t->dropColumn('sepa_creditor_id'));
    }
};

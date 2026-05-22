<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Credit note support
            $table->boolean('is_credit')->default(false)->after('status');
            $table->foreignId('credits_invoice_id')->nullable()->after('is_credit')
                ->constrained('invoices')->nullOnDelete();

            // Incasso fields
            $table->timestamp('incasso_sent_at')->nullable()->after('paid_at');
            $table->string('incasso_reference', 32)->nullable()->after('incasso_sent_at');
            $table->string('incasso_handler')->nullable()->after('incasso_reference');
            $table->string('incasso_phase', 32)->nullable()->after('incasso_handler'); // minnelijk / gerechtelijk / executie
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['credits_invoice_id']);
            $table->dropColumn([
                'is_credit', 'credits_invoice_id',
                'incasso_sent_at', 'incasso_reference', 'incasso_handler', 'incasso_phase',
            ]);
        });
    }
};

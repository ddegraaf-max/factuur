<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Peppol via Recommand: elke administratie is een eigen deelnemer
            // (company) onder het EasyInvoice-team. Verzenden/ontvangen kan
            // pas na de identiteitscontrole van een bevoegd persoon.
            $table->string('peppol_company_id', 60)->nullable()->after('inbound_token');
            $table->string('peppol_verification_status', 20)->nullable()->after('peppol_company_id'); // pending|verified|rejected|error
            $table->text('peppol_verification_url')->nullable()->after('peppol_verification_status');
            $table->timestamp('peppol_registered_at')->nullable()->after('peppol_verification_url');
            $table->timestamp('peppol_verified_at')->nullable()->after('peppol_registered_at');
        });

        Schema::table('purchase_inbox_items', function (Blueprint $table) {
            // Ontvangen e-facturen: idempotent per Peppol-document.
            $table->string('peppol_document_id', 60)->nullable()->unique()->after('purchase_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_inbox_items', function (Blueprint $table) {
            $table->dropColumn('peppol_document_id');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['peppol_company_id', 'peppol_verification_status', 'peppol_verification_url', 'peppol_registered_at', 'peppol_verified_at']);
        });
    }
};

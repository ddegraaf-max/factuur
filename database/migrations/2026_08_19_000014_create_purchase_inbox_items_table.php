<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Postvak IN voor inkoop: bijlagen (PDF/foto) uit doorgestuurde
        // e-mails, wachtend om te worden ingeboekt. Bestanden staan als
        // base64 in de database (zelfde afweging als attachments: het
        // bestandssysteem van Railway is niet persistent).
        Schema::create('purchase_inbox_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->string('from_email', 180)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes')->default(0);
            $table->longText('file_data'); // base64
            $table->string('status', 20)->default('pending'); // pending | processed | dismissed
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        // Uniek inboek-adres per administratie: bon-<token>@<inboekdomein>.
        Schema::table('companies', function (Blueprint $table) {
            $table->string('inbound_token', 24)->nullable()->unique()->after('mollie_api_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_inbox_items');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('inbound_token');
        });
    }
};

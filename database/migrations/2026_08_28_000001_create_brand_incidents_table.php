<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Merkbewaking (platformbreed, geen company-scope): verwarringsincidenten
        // met een ander "EasyInvoice" — datum, van wie, wat, waaruit het blijkt.
        Schema::create('brand_incidents', function (Blueprint $table) {
            $table->id();
            $table->date('occurred_on');
            $table->string('source', 30);            // contactformulier | verwarringspagina | telefoon | e-mail | handmatig
            $table->string('name', 160)->nullable();
            $table->string('email', 180)->nullable();
            $table->text('summary');                 // wat er precies werd gezegd of gevraagd
            $table->text('evidence')->nullable();    // waaruit de verwarring blijkt
            $table->string('attachment_name', 255)->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->longText('attachment_data')->nullable(); // base64 (screenshot)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('occurred_on');
        });

        // Maandelijks merkgebruik-dossier: cijfers + bestandsmanifest, ook als de
        // bestanden zelf (ephemere containeropslag) verdwenen zijn.
        Schema::create('brand_dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7)->unique();   // YYYY-MM
            $table->json('stats');
            $table->json('manifest');
            $table->string('mailed_to')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_dossiers');
        Schema::dropIfExists('brand_incidents');
    }
};

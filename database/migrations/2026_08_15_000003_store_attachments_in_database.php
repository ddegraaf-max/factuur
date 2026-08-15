<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // Base64 in de database — net als logo_data op companies. Het
            // bestandssysteem van Railway is ephemeral: alles wat op schijf
            // staat verdwijnt bij elke deploy. In de database overleeft het,
            // en het gaat gewoon mee in de back-ups.
            $table->longText('file_data')->nullable()->after('storage_path');
        });

        // Oude bijlagen staan nog met een pad op schijf; nieuwe hebben dat niet meer.
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('file_data');
        });
    }
};

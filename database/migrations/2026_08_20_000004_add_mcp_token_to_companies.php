<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Claude-koppeling (MCP): geheim deel van de koppel-URL waarmee
            // Claude namens deze administratie concepten mag aanmaken.
            // Null = koppeling uitgeschakeld.
            $table->string('mcp_token', 64)->nullable()->unique()->after('is_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('mcp_token');
        });
    }
};

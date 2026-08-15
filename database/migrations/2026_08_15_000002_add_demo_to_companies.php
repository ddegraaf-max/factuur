<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Demo-omgevingen: elke bezoeker krijgt een eigen sandbox-bedrijf
            // dat na afloop automatisch wordt opgeruimd.
            $table->boolean('is_demo')->default(false)->after('name');
            $table->timestamp('demo_expires_at')->nullable()->after('is_demo');

            $table->index(['is_demo', 'demo_expires_at']);
        });

        // De statuskolom is ooit als enum aangemaakt zónder 'incasso', terwijl de
        // incassofunctie die status wél zet. Op PostgreSQL blokkeert de bijbehorende
        // check-constraint dat. Hier verruimen we de kolom naar een gewone string.
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
                DB::statement('ALTER TABLE invoices ALTER COLUMN status TYPE varchar(32)');
            } catch (\Throwable $e) {
                // Kolom is al ruim genoeg — niets aan de hand.
            }
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['is_demo', 'demo_expires_at']);
            $table->dropColumn(['is_demo', 'demo_expires_at']);
        });
    }
};

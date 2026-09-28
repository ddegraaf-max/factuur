<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mensen en robots uit elkaar houden in de bezoekersteller.
 *
 * De teller schreef elk verzoek met een gewone browsernaam weg; in de praktijk
 * was ruim negen op de tien daarvan een robot (dag en nacht even druk, elke
 * pagina even vaak, geen herkomst). 'confirmed_at' wordt gezet door een seintje
 * uit de browser na een teken van leven, 'event' markeert een mijlpaal (demo
 * gestart, geregistreerd) in plaats van een paginabezoek.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('visitor_hash');
            $table->string('event', 40)->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropColumn(['confirmed_at', 'event']);
        });
    }
};

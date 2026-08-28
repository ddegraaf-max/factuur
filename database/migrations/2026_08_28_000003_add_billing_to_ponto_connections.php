<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Toeslag per gekoppelde rekening: het Stripe-subscription-item en de afgerekende hoeveelheid. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ponto_connections', function (Blueprint $table) {
            $table->string('stripe_item_id', 64)->nullable()->after('last_error');
            $table->unsignedSmallInteger('billed_quantity')->default(0)->after('stripe_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('ponto_connections', function (Blueprint $table) {
            $table->dropColumn(['stripe_item_id', 'billed_quantity']);
        });
    }
};

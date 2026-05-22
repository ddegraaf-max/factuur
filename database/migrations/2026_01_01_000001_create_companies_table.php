<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trading_name')->nullable();
            $table->string('kvk_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address_line')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('NL');
            $table->string('currency', 3)->default('EUR');
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 7)->default('#E8231F');
            $table->integer('default_payment_terms')->default(30);
            $table->text('invoice_footer')->nullable();
            $table->string('invoice_number_format')->default('{year}-{sequence:4}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

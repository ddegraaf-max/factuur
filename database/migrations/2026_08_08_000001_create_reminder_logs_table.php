<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('invoice_id')->index();
            $table->string('type');                        // bv. "Eerste herinnering", "Tweede aanmaning"
            $table->string('kind')->default('reminder');   // reminder | warning
            $table->string('channel')->default('email');
            $table->string('sent_to')->nullable();
            $table->decimal('amount_open', 12, 2)->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};

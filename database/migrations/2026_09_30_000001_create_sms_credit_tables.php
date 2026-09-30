<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sms-tegoed (1.70.0): een administratie koopt een bundel sms'en en verbruikt
 * die per verstuurd bericht. De aankopen staan in sms_purchases; het tegoed is
 * de som van de boekingen in sms_credit_entries (bij, af), zodat altijd is na
 * te gaan waar het tegoed vandaan komt en waar het naartoe ging.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('credits');
            $table->decimal('price_excl', 10, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('price_incl', 10, 2);
            // pending | paid
            $table->string('status', 20)->default('pending');
            $table->string('stripe_session_id', 120)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('sms_credit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Bij (aankoop, cadeau) is positief, af (verstuurde sms) is negatief.
            $table->integer('amount');
            // purchase | use | gift
            $table->string('kind', 20);
            $table->foreignId('sms_purchase_id')->nullable()->constrained('sms_purchases')->nullOnDelete();
            $table->foreignId('sms_message_id')->nullable()->constrained('sms_messages')->nullOnDelete();
            $table->string('note', 160)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_entries');
        Schema::dropIfExists('sms_purchases');
    }
};

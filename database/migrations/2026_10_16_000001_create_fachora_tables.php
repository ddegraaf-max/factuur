<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fachora (1.82.0): de marktplaats voor gecontroleerde vakmannen in Polen.
 *
 * Platformbrede tabellen, bewust zonder company-scope: een bedrijf (provider)
 * staat er één keer, ongeacht hoeveel administraties er zijn. De eigen
 * administratie van het bedrijf (Lopra-motor) hangt eraan via company_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 10)->nullable()->unique();
            $table->string('regon', 14)->nullable();
            $table->string('krs', 10)->nullable();
            $table->string('legal_name', 200);
            $table->string('display_name', 160);
            $table->string('slug', 180)->unique();
            $table->string('legal_form', 20)->nullable();        // jdg | sp_zoo | … (config/markets.php pl.company_types)
            $table->string('address_line', 200)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('voivodeship', 60)->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('website', 180)->nullable();
            $table->string('source', 20)->default('manual');     // ceidg | krs | import | manual | claimed
            $table->timestamp('source_fetched_at')->nullable();
            $table->string('registry_status', 20)->nullable();   // active | suspended | ceased
            $table->string('vat_status', 20)->nullable();        // active | exempt | removed | unknown
            $table->timestamp('vat_checked_at')->nullable();

            // unclaimed → claimed → pending → verified → suspended | blocked
            $table->string('status', 20)->default('unclaimed');
            $table->text('status_reason')->nullable();
            $table->json('languages')->nullable();               // {"pl":"native","en":"b2"}
            $table->unsignedSmallInteger('radius_km')->default(25);
            $table->json('rates')->nullable();                   // {"callout":80,"first_hour":150,"extra_hour":100}
            $table->unsignedTinyInteger('warranty_months')->default(12);
            $table->boolean('invoice_promise')->default(true);
            $table->boolean('protect_opt_in')->default(true);
            $table->text('description')->nullable();
            $table->text('photo_data')->nullable();              // profielfoto als data-URL (klein)

            // Eigen administratie en inlog na het claimen.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Claimen: per brief (code) of vanaf het profiel (e-mail bevestigen).
            $table->string('claim_code', 12)->nullable()->unique();
            $table->string('claim_token', 64)->nullable()->unique();
            $table->string('claim_email', 180)->nullable();
            $table->string('claim_phone', 40)->nullable();
            $table->string('claim_name', 120)->nullable();
            $table->timestamp('claim_requested_at')->nullable();
            $table->timestamp('claimed_at')->nullable();

            // Mag Fachora dit bedrijf e-mailen? Alleen na claimen of met vastgelegde opt-in.
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_source', 80)->nullable();
            $table->timestamp('email_blocked_at')->nullable();
            $table->string('optout_token', 64)->nullable()->unique();

            // Verificatie.
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->timestamp('phone_code_sent_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version', 20)->nullable();
            $table->string('stripe_account_id', 60)->nullable();
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->json('stripe_requirements')->nullable();
            $table->timestamp('stripe_checked_at')->nullable();
            $table->date('insurance_until')->nullable();
            $table->string('insurance_note', 200)->nullable();
            $table->timestamp('verification_requested_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->date('suspended_until')->nullable();
            $table->timestamp('blocked_at')->nullable();

            // Reputatie.
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('jobs_done')->default(0);
            $table->unsignedInteger('response_minutes')->nullable();
            $table->text('notes')->nullable();                   // aantekeningen van de eigenaar
            $table->timestamps();

            $table->index('status');
            $table->index('city');
            $table->index(['lat', 'lng']);
        });

        Schema::create('provider_category', function (Blueprint $table) {
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40);
            $table->primary(['provider_id', 'category']);
            $table->index('category');
        });

        Schema::create('provider_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);                          // registry | vat | email | phone | terms | bank | insurance
            $table->string('status', 12)->default('pending');    // pending | passed | failed | manual
            $table->json('evidence')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['provider_id', 'kind']);
        });

        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();           // FA-10001
            $table->string('token', 64)->unique();               // geheime link van de klant
            $table->string('status', 20)->default('pending');
            $table->string('category', 40);
            $table->string('title', 160);
            $table->text('description');
            $table->text('description_pl')->nullable();         // vertaling voor het bedrijf (AI)
            $table->json('ai')->nullable();                      // samenvatting, prijsklasse, vragen
            $table->string('urgency', 12)->default('week');      // now | week | month | date
            $table->date('wanted_date')->nullable();
            $table->string('time_window', 40)->nullable();
            $table->string('language', 5)->default('pl');        // taal van de klant
            $table->string('customer_name', 120);
            $table->string('customer_email', 180);
            $table->string('customer_phone', 40)->nullable();
            $table->string('customer_locale', 5)->default('pl'); // taal van de mails/pagina: pl | en
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 120);
            $table->string('address_line', 200)->nullable();     // pas na de keuze voor een bedrijf
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->string('creator_ip', 45)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedTinyInteger('confirm_mails')->default(0);
            $table->decimal('estimate_low', 10, 2)->nullable();
            $table->decimal('estimate_high', 10, 2)->nullable();

            $table->timestamp('offered_at')->nullable();
            $table->timestamp('offers_deadline_at')->nullable();
            $table->unsignedTinyInteger('waves')->default(0);    // hoe vaak de opdracht is aangeboden
            $table->unsignedBigInteger('chosen_offer_id')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('agreed_amount', 10, 2)->nullable();  // bruto, zł
            $table->string('agreed_type', 12)->nullable();        // fixed | hourly | estimate
            $table->text('agreed_scope')->nullable();
            $table->timestamp('agreed_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('scheduled_window', 40)->nullable();
            $table->unsignedTinyInteger('warranty_months')->nullable();

            // Fachora Protect (Stripe Connect): betaald aan het platform, na akkoord overgeboekt.
            $table->boolean('protect')->default(false);
            $table->string('stripe_session_id', 80)->nullable();
            $table->string('stripe_payment_intent', 80)->nullable();
            $table->string('stripe_charge_id', 80)->nullable();
            $table->string('stripe_transfer_id', 80)->nullable();
            $table->string('stripe_refund_id', 80)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('payout_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('fee_percent', 5, 2)->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->decimal('payout_amount', 10, 2)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->text('done_note')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('accepted_by', 10)->nullable();        // customer | auto | owner
            $table->timestamp('dispute_opened_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->text('dispute_reply')->nullable();
            $table->timestamp('dispute_replied_at')->nullable();
            $table->string('dispute_resolution', 20)->nullable(); // payout | refund | split
            $table->text('dispute_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by', 10)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('owner_notified_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['provider_id', 'status']);
            $table->index('customer_email');
        });

        Schema::create('service_job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 12)->default('sent');       // sent | responded | declined | chosen | rejected | expired
            $table->unsignedTinyInteger('wave')->default(1);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->decimal('price', 10, 2)->nullable();          // bruto, zł
            $table->string('price_type', 12)->nullable();         // fixed | hourly | estimate
            $table->decimal('callout', 8, 2)->nullable();
            $table->decimal('hourly', 8, 2)->nullable();
            $table->timestamp('available_at')->nullable();
            $table->string('available_note', 120)->nullable();
            $table->text('remarks')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();
            $table->unique(['service_job_id', 'provider_id']);
            $table->index(['provider_id', 'status']);
        });

        Schema::create('service_job_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('actor', 12)->default('system');       // customer | provider | owner | system
            $table->text('description');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('service_job_id');
        });

        Schema::create('service_job_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->string('description', 200);
            $table->decimal('amount', 10, 2);
            $table->string('status', 12)->default('proposed');    // proposed | approved | declined
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('text')->nullable();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();
            $table->index('provider_id');
        });

        // Bestanden van bedrijven (polis, foto's) en opdrachten (foto's van de klus,
        // van het resultaat, van een geschil). In de database, net als alle bijlagen.
        Schema::create('fachora_files', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 12);                     // provider | job
            $table->unsignedBigInteger('owner_id');
            $table->string('kind', 20)->default('photo');         // photo | done | dispute | insurance | offer
            $table->string('filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedInteger('size_bytes')->default(0);
            $table->longText('file_data');
            $table->timestamps();
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fachora_files');
        Schema::dropIfExists('service_reviews');
        Schema::dropIfExists('service_job_extras');
        Schema::dropIfExists('service_job_events');
        Schema::dropIfExists('service_job_offers');
        Schema::dropIfExists('service_jobs');
        Schema::dropIfExists('provider_checks');
        Schema::dropIfExists('provider_category');
        Schema::dropIfExists('providers');
    }
};

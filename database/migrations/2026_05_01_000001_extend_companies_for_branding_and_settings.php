<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Branding
            $table->string('brand_color', 7)->default('#E8231F')->after('country');
            $table->string('accent_color', 7)->default('#1C1917')->after('brand_color');
            $table->text('logo_path')->nullable()->after('accent_color');
            $table->string('invoice_template', 32)->default('modern')->after('logo_path');
            $table->string('invoice_font', 32)->default('sans')->after('invoice_template');

            // Numbering
            $table->json('numbering_settings')->nullable()->after('invoice_font');

            // Algemene voorkeuren
            $table->string('price_mode', 10)->default('excl')->after('numbering_settings');
            $table->unsignedTinyInteger('fiscal_year_start')->default(1)->after('price_mode');
            $table->string('default_send_method', 20)->default('email')->after('fiscal_year_start');
            $table->unsignedSmallInteger('results_per_page')->default(25)->after('default_send_method');
            $table->string('copy_email')->nullable()->after('results_per_page');
            $table->boolean('daily_notification_enabled')->default(true)->after('copy_email');
            $table->string('daily_notification_email')->nullable()->after('daily_notification_enabled');

            // Reminders
            $table->json('reminder_settings')->nullable()->after('daily_notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'brand_color', 'accent_color', 'logo_path', 'invoice_template', 'invoice_font',
                'numbering_settings',
                'price_mode', 'fiscal_year_start', 'default_send_method', 'results_per_page',
                'copy_email', 'daily_notification_enabled', 'daily_notification_email',
                'reminder_settings',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'accent_color')) {
                $table->string('accent_color', 7)->default('#1C1917');
            }
            if (! Schema::hasColumn('companies', 'invoice_template')) {
                $table->string('invoice_template', 32)->default('modern');
            }
            if (! Schema::hasColumn('companies', 'invoice_font')) {
                $table->string('invoice_font', 32)->default('sans');
            }
            if (! Schema::hasColumn('companies', 'numbering_settings')) {
                $table->json('numbering_settings')->nullable();
            }
            if (! Schema::hasColumn('companies', 'price_mode')) {
                $table->string('price_mode', 10)->default('excl');
            }
            if (! Schema::hasColumn('companies', 'fiscal_year_start')) {
                $table->unsignedTinyInteger('fiscal_year_start')->default(1);
            }
            if (! Schema::hasColumn('companies', 'default_send_method')) {
                $table->string('default_send_method', 20)->default('email');
            }
            if (! Schema::hasColumn('companies', 'results_per_page')) {
                $table->unsignedSmallInteger('results_per_page')->default(25);
            }
            if (! Schema::hasColumn('companies', 'copy_email')) {
                $table->string('copy_email')->nullable();
            }
            if (! Schema::hasColumn('companies', 'daily_notification_enabled')) {
                $table->boolean('daily_notification_enabled')->default(true);
            }
            if (! Schema::hasColumn('companies', 'daily_notification_email')) {
                $table->string('daily_notification_email')->nullable();
            }
            if (! Schema::hasColumn('companies', 'reminder_settings')) {
                $table->json('reminder_settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'accent_color', 'invoice_template', 'invoice_font',
                'numbering_settings',
                'price_mode', 'fiscal_year_start', 'default_send_method', 'results_per_page',
                'copy_email', 'daily_notification_enabled', 'daily_notification_email',
                'reminder_settings',
            ] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

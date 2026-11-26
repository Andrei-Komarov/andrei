<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('notify_log_import')->default(true)->after('user_welcome_email');
            $table->boolean('notify_log_errors')->default(true)->after('notify_log_import');
            $table->boolean('notify_log_price_changes')->default(true)->after('notify_log_errors');
            $table->boolean('notify_log_new_items')->default(true)->after('notify_log_price_changes');
            $table->boolean('notify_email_reports')->default(false)->after('notify_log_new_items');
            $table->string('notify_email_schedule', 20)->default('daily')->after('notify_email_reports');
            $table->boolean('notify_telegram_digest')->default(false)->after('notify_email_schedule');
            $table->boolean('notify_error_alerts')->default(true)->after('notify_telegram_digest');
            $table->boolean('notify_price_drop_alerts')->default(true)->after('notify_error_alerts');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['notify_log_import', 'notify_log_errors', 'notify_log_price_changes', 'notify_log_new_items', 'notify_email_reports', 'notify_email_schedule', 'notify_telegram_digest', 'notify_error_alerts', 'notify_price_drop_alerts']);
        });
    }
};

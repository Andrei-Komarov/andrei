<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->integer('import_max_results')->default(10000)->after('ebay_filter_check_delay_seconds');
            $table->integer('import_page_size')->default(200)->after('import_max_results');
            $table->boolean('default_notify_new_listings')->default(true)->after('import_page_size');
            $table->boolean('default_notify_price_drops')->default(true)->after('default_notify_new_listings');
            $table->boolean('default_notify_search_errors')->default(true)->after('default_notify_price_drops');
            $table->boolean('default_notify_profile_matches')->default(true)->after('default_notify_search_errors');
            $table->boolean('default_notify_shared_price_changes')->default(true)->after('default_notify_profile_matches');
            $table->string('default_notification_frequency')->default('immediate')->after('default_notify_shared_price_changes');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'import_max_results',
                'import_page_size',
                'default_notify_new_listings',
                'default_notify_price_drops',
                'default_notify_search_errors',
                'default_notify_profile_matches',
                'default_notify_shared_price_changes',
                'default_notification_frequency',
            ]);
        });
    }
};

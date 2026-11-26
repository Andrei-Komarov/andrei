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
            $table->date('ebay_api_daily_count_date')->nullable()->after('ebay_api_limit_threshold_percent');
            $table->unsignedInteger('ebay_api_daily_count')->default(0)->after('ebay_api_daily_count_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['ebay_api_daily_count_date', 'ebay_api_daily_count']);
        });
    }
};

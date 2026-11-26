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
            $table->integer('ebay_max_retries')->default(8)->after('ebay_sandbox_mode');
            $table->integer('ebay_base_delay_seconds')->default(30)->after('ebay_max_retries');
            $table->integer('ebay_token_cache_minutes')->default(110)->after('ebay_base_delay_seconds');
            $table->boolean('ebay_enable_retry')->default(true)->after('ebay_token_cache_minutes');
            $table->integer('ebay_request_timeout')->default(30)->after('ebay_enable_retry');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['ebay_max_retries', 'ebay_base_delay_seconds', 'ebay_token_cache_minutes', 'ebay_enable_retry', 'ebay_request_timeout']);
        });
    }
};

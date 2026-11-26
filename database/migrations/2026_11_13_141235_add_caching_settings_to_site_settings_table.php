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
            $table->boolean('cache_pages_enabled')->default(false)->after('security_log_admin_logins');
            $table->integer('cache_pages_ttl')->default(60)->after('cache_pages_enabled');
            $table->boolean('cache_ebay_api_enabled')->default(false)->after('cache_pages_ttl');
            $table->integer('cache_ebay_api_ttl')->default(5)->after('cache_ebay_api_enabled');
            $table->boolean('performance_gzip')->default(true)->after('cache_ebay_api_ttl');
            $table->boolean('performance_lazy_loading')->default(true)->after('performance_gzip');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['cache_pages_enabled', 'cache_pages_ttl', 'cache_ebay_api_enabled', 'cache_ebay_api_ttl', 'performance_gzip', 'performance_lazy_loading']);
        });
    }
};

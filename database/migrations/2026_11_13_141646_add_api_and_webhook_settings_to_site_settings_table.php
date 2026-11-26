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
            $table->string('api_key', 64)->nullable()->after('currency_show_multi');
            $table->boolean('api_enabled')->default(false)->after('api_key');
            $table->string('webhook_url', 500)->nullable()->after('api_enabled');
            $table->boolean('webhook_new_items')->default(false)->after('webhook_url');
            $table->boolean('webhook_price_drops')->default(false)->after('webhook_new_items');
            $table->boolean('webhook_errors')->default(false)->after('webhook_price_drops');
            $table->string('webhook_secret', 128)->nullable()->after('webhook_errors');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'api_enabled', 'webhook_url', 'webhook_new_items', 'webhook_price_drops', 'webhook_errors', 'webhook_secret']);
        });
    }
};

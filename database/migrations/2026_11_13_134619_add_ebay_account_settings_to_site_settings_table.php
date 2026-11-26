<?php

use App\Models\SiteSetting;
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
            $table->string('ebay_client_id')->nullable()->after('appearance_default_theme');
            $table->string('ebay_client_secret')->nullable()->after('ebay_client_id');
            $table->string('ebay_marketplace_id')->default('EBAY_US')->after('ebay_client_secret');
            $table->string('ebay_api_base_url')->default('https://api.ebay.com')->after('ebay_marketplace_id');
            $table->string('ebay_identity_base_url')->default('https://api.ebay.com')->after('ebay_api_base_url');
            $table->boolean('ebay_sandbox_mode')->default(false)->after('ebay_identity_base_url');
        });

        // Copy values from .env if not already set
        $settings = SiteSetting::query()->first();
        if ($settings) {
            if (empty($settings->ebay_client_id) && env('EBAY_CLIENT_ID')) {
                $settings->ebay_client_id = env('EBAY_CLIENT_ID');
            }
            if (empty($settings->ebay_client_secret) && env('EBAY_CLIENT_SECRET')) {
                $settings->ebay_client_secret = env('EBAY_CLIENT_SECRET');
            }
            if (empty($settings->ebay_marketplace_id) || $settings->ebay_marketplace_id === 'EBAY_US') {
                $settings->ebay_marketplace_id = env('EBAY_MARKETPLACE_ID', 'EBAY_US');
            }
            if (empty($settings->ebay_api_base_url)) {
                $settings->ebay_api_base_url = env('EBAY_API_BASE_URL', 'https://api.ebay.com');
            }
            if (empty($settings->ebay_identity_base_url)) {
                $settings->ebay_identity_base_url = env('EBAY_IDENTITY_BASE_URL', 'https://api.ebay.com');
            }
            $settings->save();
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['ebay_client_id', 'ebay_client_secret', 'ebay_marketplace_id', 'ebay_api_base_url', 'ebay_identity_base_url', 'ebay_sandbox_mode']);
        });
    }
};

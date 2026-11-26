<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('seo_site_name')->default('Finder')->after('default_notification_frequency');
            $table->string('seo_site_description')->default('Поиск товаров на eBay')->after('seo_site_name');
            $table->string('seo_keywords')->default('eBay, товары, поиск, ноутбуки')->after('seo_site_description');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['seo_site_name', 'seo_site_description', 'seo_keywords']);
        });
    }
};

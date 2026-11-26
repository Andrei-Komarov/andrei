<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('seo_canonical_url')->nullable()->after('seo_keywords');
            $table->string('seo_og_title')->nullable()->after('seo_canonical_url');
            $table->text('seo_og_description')->nullable()->after('seo_og_title');
            $table->string('seo_og_image')->nullable()->after('seo_og_description');
            $table->string('seo_og_url')->nullable()->after('seo_og_image');
            $table->string('seo_twitter_title')->nullable()->after('seo_og_url');
            $table->text('seo_twitter_description')->nullable()->after('seo_twitter_title');
            $table->string('seo_twitter_image')->nullable()->after('seo_twitter_description');
        });

        // Set default values from existing SEO settings
        $settings = SiteSetting::query()->first();
        if ($settings) {
            if (empty($settings->seo_og_title)) {
                $settings->seo_og_title = $settings->seo_site_name ?? 'Finder';
            }
            if (empty($settings->seo_og_description)) {
                $settings->seo_og_description = $settings->seo_site_description ?? 'Поиск товаров на eBay';
            }
            if (empty($settings->seo_twitter_title)) {
                $settings->seo_twitter_title = $settings->seo_site_name ?? 'Finder';
            }
            if (empty($settings->seo_twitter_description)) {
                $settings->seo_twitter_description = $settings->seo_site_description ?? 'Поиск товаров на eBay';
            }
            $settings->save();
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['seo_canonical_url', 'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_url', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image']);
        });
    }
};

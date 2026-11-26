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
            $table->string('appearance_logo_path')->nullable()->after('seo_keywords');
            $table->string('appearance_favicon_path')->nullable()->after('appearance_logo_path');
            $table->string('appearance_site_subtitle')->default('Поиск товаров на eBay')->after('appearance_favicon_path');
            $table->integer('appearance_default_per_page')->default(25)->after('appearance_site_subtitle');
            $table->string('appearance_default_theme')->default('light')->after('appearance_default_per_page');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['appearance_logo_path', 'appearance_favicon_path', 'appearance_site_subtitle', 'appearance_default_per_page', 'appearance_default_theme']);
        });
    }
};

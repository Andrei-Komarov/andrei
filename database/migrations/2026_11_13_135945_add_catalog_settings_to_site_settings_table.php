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
            $table->string('catalog_currency', 10)->default('USD')->after('search_default_condition');
            $table->boolean('catalog_show_shipping')->default(true)->after('catalog_currency');
            $table->string('catalog_price_format', 10)->default('before')->after('catalog_show_shipping');
            $table->boolean('catalog_show_image')->default(true)->after('catalog_price_format');
            $table->boolean('catalog_show_seller')->default(true)->after('catalog_show_image');
            $table->boolean('catalog_show_condition')->default(true)->after('catalog_show_seller');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['catalog_currency', 'catalog_show_shipping', 'catalog_price_format', 'catalog_show_image', 'catalog_show_seller', 'catalog_show_condition']);
        });
    }
};

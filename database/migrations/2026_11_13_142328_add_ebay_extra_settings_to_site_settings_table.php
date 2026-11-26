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
            $table->string('ebay_category_id', 20)->default('175672')->after('ebay_auction_only');
            $table->string('ebay_sort_by', 50)->default('price')->after('ebay_category_id');
            $table->text('ebay_ignored_sellers')->nullable()->after('ebay_sort_by');
            $table->string('ebay_seller_country', 10)->nullable()->after('ebay_ignored_sellers');
            $table->boolean('ebay_free_shipping_only')->default(false)->after('ebay_seller_country');
            $table->boolean('ebay_buy_it_now_only')->default(false)->after('ebay_free_shipping_only');
            $table->boolean('ebay_auction_only')->default(false)->after('ebay_buy_it_now_only');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ebay_category_id',
                'ebay_sort_by',
                'ebay_ignored_sellers',
                'ebay_seller_country',
                'ebay_free_shipping_only',
                'ebay_buy_it_now_only',
                'ebay_auction_only',
            ]);
        });
    }
};

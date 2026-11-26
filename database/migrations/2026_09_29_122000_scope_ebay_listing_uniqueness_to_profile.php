<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laptop_listings', function (Blueprint $table): void {
            $table->dropUnique('laptop_listings_ebay_item_id_unique');
            $table->unique(['search_profile_id', 'ebay_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('laptop_listings', function (Blueprint $table): void {
            $table->dropUnique(['search_profile_id', 'ebay_item_id']);
            $table->unique('ebay_item_id');
        });
    }
};

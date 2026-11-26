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
        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->boolean('notify_profile_matches')->default(true);
            $table->boolean('notify_shared_price_changes')->default(true);
        });

        Schema::table('user_listing_alerts', function (Blueprint $table): void {
            $table->decimal('last_observed_price', 12, 2)->nullable();
            $table->string('last_observed_currency', 3)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_listing_alerts', function (Blueprint $table): void {
            $table->dropColumn(['last_observed_price', 'last_observed_currency']);
        });

        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->dropColumn(['notify_profile_matches', 'notify_shared_price_changes']);
        });
    }
};

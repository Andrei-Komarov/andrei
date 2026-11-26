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
            $table->decimal('price_alert_threshold', 10, 2)->nullable();
        });

        Schema::table('laptop_listings', function (Blueprint $table): void {
            $table->timestamp('price_threshold_notified_at')->nullable();
        });

        Schema::create('laptop_listing_price_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('laptop_listing_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->timestamp('observed_at');
            $table->timestamps();
            $table->index(['laptop_listing_id', 'observed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laptop_listing_price_histories');

        Schema::table('laptop_listings', function (Blueprint $table): void {
            $table->dropColumn('price_threshold_notified_at');
        });

        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->dropColumn('price_alert_threshold');
        });
    }
};

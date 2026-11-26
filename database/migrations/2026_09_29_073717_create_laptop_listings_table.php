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
        Schema::create('laptop_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_profile_id')->constrained()->cascadeOnDelete();
            $table->string('ebay_item_id')->unique();
            $table->string('title');
            $table->string('item_url', 2048);
            $table->string('image_url', 2048)->nullable();
            $table->string('condition')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('shipping_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('seller')->nullable();
            $table->decimal('seller_feedback', 5, 2)->nullable();
            $table->string('cpu')->nullable();
            $table->unsignedSmallInteger('ram_gb')->nullable();
            $table->string('storage')->nullable();
            $table->string('screen')->nullable();
            $table->string('gpu')->nullable();
            $table->json('specifications')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status')->default('new');
            $table->timestamp('seen_at');
            $table->timestamp('sold_at')->nullable();
            $table->index(['search_profile_id', 'status']);
            $table->index('total_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laptop_listings');
    }
};

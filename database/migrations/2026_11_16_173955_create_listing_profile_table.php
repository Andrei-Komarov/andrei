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
        Schema::create('listing_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laptop_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('search_profile_id')->constrained()->cascadeOnDelete();
            $table->unique(['laptop_listing_id', 'search_profile_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_profile');
    }
};

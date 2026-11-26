<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_listing_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ebay_item_id');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'ebay_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_listing_alerts');
    }
};

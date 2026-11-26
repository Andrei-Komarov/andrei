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
        Schema::table('search_profiles', function (Blueprint $table) {
            $table->json('brands')->nullable();
            $table->json('ram_sizes')->nullable();
            $table->json('storage_capacities')->nullable();
            $table->json('screen_sizes')->nullable();
            $table->json('gpus')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'brands',
                'ram_sizes',
                'storage_capacities',
                'screen_sizes',
                'gpus',
            ]);
        });
    }
};

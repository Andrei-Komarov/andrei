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
            $table->unsignedInteger('min_price')->nullable();
            $table->string('brand')->nullable();
            $table->string('processor')->nullable();
            $table->string('ram_size')->nullable();
            $table->string('storage_capacity')->nullable();
            $table->string('screen_size')->nullable();
            $table->string('gpu')->nullable();
            $table->string('operating_system')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'min_price',
                'brand',
                'processor',
                'ram_size',
                'storage_capacity',
                'screen_size',
                'gpu',
                'operating_system',
            ]);
        });
    }
};

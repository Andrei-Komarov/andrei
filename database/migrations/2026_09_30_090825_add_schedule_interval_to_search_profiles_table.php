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
            $table->unsignedTinyInteger('schedule_interval_hours')->default(24);
            $table->timestamp('last_scheduled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->dropColumn(['schedule_interval_hours', 'last_scheduled_at']);
        });
    }
};

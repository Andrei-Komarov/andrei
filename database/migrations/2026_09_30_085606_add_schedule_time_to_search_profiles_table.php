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
            $table->time('schedule_time')->default('08:00:00');
            $table->date('last_scheduled_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->dropColumn(['schedule_time', 'last_scheduled_date']);
        });
    }
};

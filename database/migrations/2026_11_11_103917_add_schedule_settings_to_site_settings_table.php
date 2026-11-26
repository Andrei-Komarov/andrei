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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('schedule_frequency', 50)->default('everyMinute')->after('kgs_per_usd');
            $table->string('schedule_timezone', 50)->default('Asia/Bishkek')->after('schedule_frequency');
            $table->boolean('schedule_without_overlapping')->default(true)->after('schedule_timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['schedule_frequency', 'schedule_timezone', 'schedule_without_overlapping']);
        });
    }
};

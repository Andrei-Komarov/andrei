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
            $table->string('locale_language', 10)->default('ru')->after('api_rate_limit_window');
            $table->string('locale_date_format', 20)->default('d.m.Y')->after('locale_language');
            $table->string('locale_time_format', 10)->default('24h')->after('locale_date_format');
            $table->string('locale_number_format', 10)->default('space_dot')->after('locale_time_format');
            $table->string('locale_first_day', 10)->default('monday')->after('locale_number_format');
            $table->string('locale_direction', 3)->default('ltr')->after('locale_first_day');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'locale_language',
                'locale_date_format',
                'locale_time_format',
                'locale_number_format',
                'locale_first_day',
                'locale_direction',
            ]);
        });
    }
};

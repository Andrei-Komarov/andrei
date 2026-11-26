<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->integer('ebay_profile_delay_seconds')->default(5)->after('schedule_type');
            $table->integer('ebay_filter_check_delay_seconds')->default(3)->after('ebay_profile_delay_seconds');
        });

        $settings = DB::table('site_settings')->first();
        if ($settings) {
            DB::table('site_settings')
                ->where('id', $settings->id)
                ->update([
                    'ebay_profile_delay_seconds' => 5,
                    'ebay_filter_check_delay_seconds' => 2,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['ebay_profile_delay_seconds', 'ebay_filter_check_delay_seconds']);
        });
    }
};

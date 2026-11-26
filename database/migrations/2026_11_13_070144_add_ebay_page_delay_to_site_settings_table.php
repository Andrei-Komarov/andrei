<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->integer('ebay_page_delay_seconds')->default(3)->after('ebay_profile_delay_seconds');
        });

        $settings = DB::table('site_settings')->first();
        if ($settings) {
            DB::table('site_settings')
                ->where('id', $settings->id)
                ->update([
                    'ebay_page_delay_seconds' => 3,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('ebay_page_delay_seconds');
        });
    }
};

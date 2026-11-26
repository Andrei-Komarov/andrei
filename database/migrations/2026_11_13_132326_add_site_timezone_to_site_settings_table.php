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
            $table->string('site_timezone')->default('UTC')->after('schedule_timezone');
        });

        $settings = DB::table('site_settings')->first();
        if ($settings) {
            DB::table('site_settings')
                ->where('id', $settings->id)
                ->update([
                    'site_timezone' => env('APP_TIMEZONE', 'UTC'),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('site_timezone');
        });
    }
};

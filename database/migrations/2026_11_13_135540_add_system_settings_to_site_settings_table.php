<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('system_log_level', 20)->default('warning')->after('backup_notify_telegram');
            $table->boolean('system_debug_mode')->default(false)->after('system_log_level');
            $table->integer('system_max_execution_time')->default(300)->after('system_debug_mode');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['system_log_level', 'system_debug_mode', 'system_max_execution_time']);
        });
    }
};

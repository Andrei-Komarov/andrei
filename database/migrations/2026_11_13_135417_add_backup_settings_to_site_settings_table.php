<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('backup_schedule', 20)->default('daily')->after('seo_twitter_image');
            $table->integer('backup_retain_count')->default(7)->after('backup_schedule');
            $table->boolean('backup_database')->default(true)->after('backup_retain_count');
            $table->boolean('backup_files')->default(true)->after('backup_database');
            $table->string('backup_path')->nullable()->after('backup_files');
            $table->boolean('backup_notify_telegram')->default(true)->after('backup_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['backup_schedule', 'backup_retain_count', 'backup_database', 'backup_files', 'backup_path', 'backup_notify_telegram']);
        });
    }
};

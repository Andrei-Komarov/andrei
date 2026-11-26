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
            $table->text('security_ip_blacklist')->nullable()->after('locale_direction');
            $table->text('security_ip_whitelist')->nullable()->after('security_ip_blacklist');
            $table->boolean('security_ip_whitelist_enabled')->default(false)->after('security_ip_whitelist');
            $table->text('security_blocked_user_agents')->nullable()->after('security_ip_whitelist_enabled');
            $table->boolean('security_honeypot_enabled')->default(true)->after('security_blocked_user_agents');
            $table->integer('security_max_upload_size')->default(10)->after('security_honeypot_enabled');
            $table->string('security_allowed_file_types')->default('jpg,jpeg,png,gif,pdf,doc,docx')->after('security_max_upload_size');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'security_ip_blacklist',
                'security_ip_whitelist',
                'security_ip_whitelist_enabled',
                'security_blocked_user_agents',
                'security_honeypot_enabled',
                'security_max_upload_size',
                'security_allowed_file_types',
            ]);
        });
    }
};

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
            $table->boolean('security_2fa_enabled')->default(false)->after('ebay_request_timeout');
            $table->text('security_admin_ip_whitelist')->nullable()->after('security_2fa_enabled');
            $table->integer('security_admin_session_lifetime')->default(1440)->after('security_admin_ip_whitelist');
            $table->integer('security_admin_max_attempts')->default(5)->after('security_admin_session_lifetime');
            $table->boolean('security_log_admin_logins')->default(true)->after('security_admin_max_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['security_2fa_enabled', 'security_admin_ip_whitelist', 'security_admin_session_lifetime', 'security_admin_max_attempts', 'security_log_admin_logins']);
        });
    }
};

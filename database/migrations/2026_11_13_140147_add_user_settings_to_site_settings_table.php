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
            $table->boolean('user_registration_enabled')->default(true)->after('catalog_show_condition');
            $table->boolean('user_email_verification')->default(true)->after('user_registration_enabled');
            $table->integer('user_session_lifetime')->default(43200)->after('user_email_verification');
            $table->integer('user_login_max_attempts')->default(5)->after('user_session_lifetime');
            $table->integer('user_password_min_length')->default(8)->after('user_login_max_attempts');
            $table->boolean('user_welcome_email')->default(true)->after('user_password_min_length');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['user_registration_enabled', 'user_email_verification', 'user_session_lifetime', 'user_login_max_attempts', 'user_password_min_length', 'user_welcome_email']);
        });
    }
};

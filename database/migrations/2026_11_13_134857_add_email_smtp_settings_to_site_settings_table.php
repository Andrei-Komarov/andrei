<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('email_smtp_host')->nullable()->after('ebay_sandbox_mode');
            $table->integer('email_smtp_port')->nullable()->after('email_smtp_host');
            $table->string('email_smtp_encryption')->nullable()->after('email_smtp_port');
            $table->string('email_smtp_username')->nullable()->after('email_smtp_encryption');
            $table->string('email_smtp_password')->nullable()->after('email_smtp_username');
            $table->string('email_from_address')->nullable()->after('email_smtp_password');
            $table->string('email_from_name')->nullable()->after('email_from_address');
        });

        // Copy values from .env if not already set
        $settings = SiteSetting::query()->first();
        if ($settings) {
            if (empty($settings->email_smtp_host) && env('MAIL_HOST')) {
                $settings->email_smtp_host = env('MAIL_HOST');
            }
            if (empty($settings->email_smtp_port) && env('MAIL_PORT')) {
                $settings->email_smtp_port = (int) env('MAIL_PORT');
            }
            if (empty($settings->email_smtp_encryption)) {
                $settings->email_smtp_encryption = env('MAIL_SCHEME', 'tls');
            }
            if (empty($settings->email_smtp_username) && env('MAIL_USERNAME')) {
                $settings->email_smtp_username = env('MAIL_USERNAME');
            }
            if (empty($settings->email_smtp_password) && env('MAIL_PASSWORD')) {
                $settings->email_smtp_password = env('MAIL_PASSWORD');
            }
            if (empty($settings->email_from_address) && env('MAIL_FROM_ADDRESS')) {
                $settings->email_from_address = env('MAIL_FROM_ADDRESS');
            }
            if (empty($settings->email_from_name) && env('MAIL_FROM_NAME')) {
                $settings->email_from_name = env('MAIL_FROM_NAME');
            }
            $settings->save();
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['email_smtp_host', 'email_smtp_port', 'email_smtp_encryption', 'email_smtp_username', 'email_smtp_password', 'email_from_address', 'email_from_name']);
        });
    }
};

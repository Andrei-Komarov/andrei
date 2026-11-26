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
            $table->text('cors_allowed_origins')->default('*')->after('webhook_secret');
            $table->string('cors_allowed_methods')->default('GET,POST,PUT,PATCH,DELETE')->after('cors_allowed_origins');
            $table->string('cors_allowed_headers')->default('Content-Type,Authorization')->after('cors_allowed_methods');
            $table->boolean('cors_allow_credentials')->default(false)->after('cors_allowed_headers');
            $table->integer('api_rate_limit')->default(60)->after('cors_allow_credentials');
            $table->integer('api_rate_limit_window')->default(60)->after('api_rate_limit');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cors_allowed_origins',
                'cors_allowed_methods',
                'cors_allowed_headers',
                'cors_allow_credentials',
                'api_rate_limit',
                'api_rate_limit_window',
            ]);
        });
    }
};

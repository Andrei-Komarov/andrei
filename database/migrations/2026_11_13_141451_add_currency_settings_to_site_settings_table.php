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
            $table->decimal('eur_per_usd', 10, 4)->default(1.0)->after('email_subject_template');
            $table->decimal('gbp_per_usd', 10, 4)->default(1.0)->after('eur_per_usd');
            $table->boolean('currency_auto_update')->default(false)->after('gbp_per_usd');
            $table->string('currency_update_schedule', 20)->default('daily')->after('currency_auto_update');
            $table->boolean('currency_show_multi')->default(false)->after('currency_update_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['eur_per_usd', 'gbp_per_usd', 'currency_auto_update', 'currency_update_schedule', 'currency_show_multi']);
        });
    }
};

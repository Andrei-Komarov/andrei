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
            $table->boolean('email_notify_new_items')->default(false)->after('performance_lazy_loading');
            $table->boolean('email_notify_price_drops')->default(false)->after('email_notify_new_items');
            $table->boolean('email_notify_errors')->default(false)->after('email_notify_price_drops');
            $table->boolean('email_notify_matches')->default(false)->after('email_notify_errors');
            $table->text('email_recipients')->nullable()->after('email_notify_matches');
            $table->string('email_subject_template', 255)->nullable()->after('email_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['email_notify_new_items', 'email_notify_price_drops', 'email_notify_errors', 'email_notify_matches', 'email_recipients', 'email_subject_template']);
        });
    }
};

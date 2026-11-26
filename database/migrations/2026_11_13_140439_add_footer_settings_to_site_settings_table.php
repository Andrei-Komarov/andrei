<?php

use App\Models\SiteSetting;
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
            $table->string('footer_copyright', 255)->nullable()->after('notify_price_drop_alerts');
            $table->text('footer_privacy_policy')->nullable()->after('footer_copyright');
            $table->text('footer_terms_of_service')->nullable()->after('footer_privacy_policy');
            $table->string('footer_contact_email', 255)->nullable()->after('footer_terms_of_service');
            $table->string('footer_social_telegram', 255)->nullable()->after('footer_contact_email');
            $table->string('footer_social_facebook', 255)->nullable()->after('footer_social_telegram');
        });

        // Set default copyright
        $settings = SiteSetting::query()->first();
        if ($settings) {
            $settings->footer_copyright = '© '.now()->year.' Finder. Все права защищены.';
            $settings->save();
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['footer_copyright', 'footer_privacy_policy', 'footer_terms_of_service', 'footer_contact_email', 'footer_social_telegram', 'footer_social_facebook']);
        });
    }
};

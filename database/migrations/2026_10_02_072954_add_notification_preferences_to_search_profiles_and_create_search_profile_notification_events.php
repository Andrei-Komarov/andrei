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
        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->boolean('notify_new_listings')->default(true);
            $table->boolean('notify_price_drops')->default(true);
            $table->boolean('notify_search_errors')->default(true);
            $table->string('notification_frequency')->default('immediate');
        });

        Schema::create('search_profile_notification_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('search_profile_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['search_profile_id', 'sent_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_profile_notification_events');

        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_new_listings',
                'notify_price_drops',
                'notify_search_errors',
                'notification_frequency',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $fallbackUserId = DB::table('users')
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');

        DB::table('search_profiles')
            ->select('id')
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (object $profile) use ($fallbackUserId): void {
                $activityUserId = DB::table('search_profile_activities')
                    ->where('search_profile_id', $profile->id)
                    ->whereNotNull('user_id')
                    ->orderBy('id')
                    ->value('user_id');

                $userId = $activityUserId ?? $fallbackUserId;

                DB::table('search_profiles')
                    ->where('id', $profile->id)
                    ->update(['user_id' => $userId]);

                if ($userId !== null) {
                    DB::table('search_profile_activities')
                        ->where('search_profile_id', $profile->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('search_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};

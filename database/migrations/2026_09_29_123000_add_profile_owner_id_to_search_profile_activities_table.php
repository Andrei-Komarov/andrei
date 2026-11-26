<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_profile_activities', function (Blueprint $table): void {
            $table->foreignId('profile_owner_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        DB::table('search_profile_activities')
            ->whereNotNull('search_profile_id')
            ->update([
                'profile_owner_id' => DB::raw(
                    '(select user_id from search_profiles where search_profiles.id = search_profile_activities.search_profile_id)',
                ),
            ]);

        DB::table('search_profile_activities')
            ->whereNull('profile_owner_id')
            ->whereNotNull('user_id')
            ->update(['profile_owner_id' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('search_profile_activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('profile_owner_id');
        });
    }
};

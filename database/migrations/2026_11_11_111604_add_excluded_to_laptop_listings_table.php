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
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->boolean('excluded')->default(false)->after('status');
            $table->text('excluded_reason')->nullable()->after('excluded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->dropColumn(['excluded', 'excluded_reason']);
        });
    }
};

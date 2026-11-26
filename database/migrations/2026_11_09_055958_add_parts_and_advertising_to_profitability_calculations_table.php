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
        Schema::table('profitability_calculations', function (Blueprint $table): void {
            $table->decimal('advertising_costs', 10, 2)->default(0);
            $table->json('parts_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profitability_calculations', function (Blueprint $table): void {
            $table->dropColumn(['advertising_costs', 'parts_snapshot']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('profitability_currency', 3)->default('KGS');
            $table->decimal('kgs_per_usd', 12, 4)->default(89);
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'id' => 1,
            'profitability_currency' => 'KGS',
            'kgs_per_usd' => '89.0000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};

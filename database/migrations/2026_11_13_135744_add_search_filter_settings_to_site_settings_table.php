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
            $table->string('search_default_sort', 30)->default('price_asc')->after('system_max_execution_time');
            $table->string('search_default_field', 30)->default('title')->after('search_default_sort');
            $table->string('search_default_status', 20)->nullable()->after('search_default_field');
            $table->integer('search_price_min')->default(0)->after('search_default_status');
            $table->integer('search_price_max')->default(0)->after('search_price_min');
            $table->string('search_default_condition')->nullable()->after('search_price_max');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['search_default_sort', 'search_default_field', 'search_default_status', 'search_price_min', 'search_price_max', 'search_default_condition']);
        });
    }
};

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
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->string('shipping_type', 20)->nullable()->after('shipping_price');
        });

        // Mark existing items with CALCULATED shipping in raw_payload
        DB::statement("UPDATE laptop_listings SET shipping_type = 'CALCULATED' WHERE shipping_price = 0 AND shipping_type IS NULL AND raw_payload::text LIKE '%CALCULATED%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->dropColumn('shipping_type');
        });
    }
};

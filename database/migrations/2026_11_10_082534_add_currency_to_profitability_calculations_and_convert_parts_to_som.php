<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SOM_PER_USD = 89;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profitability_calculations', function (Blueprint $table): void {
            $table->string('currency_code', 3)->default('USD');
        });

        DB::table('profitability_parts')->update([
            'price' => DB::raw('ROUND(price * '.self::SOM_PER_USD.', 2)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('profitability_calculations')
            ->where('currency_code', 'KGS')
            ->orderBy('id')
            ->chunkById(100, function (Collection $calculations): void {
                foreach ($calculations as $calculation) {
                    $partsSnapshot = is_string($calculation->parts_snapshot)
                        ? (json_decode($calculation->parts_snapshot, true, flags: JSON_THROW_ON_ERROR) ?? [])
                        : ($calculation->parts_snapshot ?? []);

                    foreach ($partsSnapshot as &$part) {
                        if (isset($part['price'])) {
                            $part['price'] = number_format((float) $part['price'] / self::SOM_PER_USD, 2, '.', '');
                        }
                    }
                    unset($part);

                    DB::table('profitability_calculations')
                        ->where('id', $calculation->id)
                        ->update([
                            'purchase_price' => DB::raw('ROUND(purchase_price / '.self::SOM_PER_USD.', 2)'),
                            'additional_costs' => DB::raw('ROUND(additional_costs / '.self::SOM_PER_USD.', 2)'),
                            'advertising_costs' => DB::raw('ROUND(advertising_costs / '.self::SOM_PER_USD.', 2)'),
                            'customs_cost' => DB::raw('ROUND(customs_cost / '.self::SOM_PER_USD.', 2)'),
                            'sale_price' => DB::raw('ROUND(sale_price / '.self::SOM_PER_USD.', 2)'),
                            'total_cost' => DB::raw('ROUND(total_cost / '.self::SOM_PER_USD.', 2)'),
                            'commission_amount' => DB::raw('ROUND(commission_amount / '.self::SOM_PER_USD.', 2)'),
                            'net_profit' => DB::raw('ROUND(net_profit / '.self::SOM_PER_USD.', 2)'),
                            'parts_snapshot' => json_encode($partsSnapshot, JSON_THROW_ON_ERROR),
                            'currency_code' => 'USD',
                        ]);
                }
            });

        DB::table('profitability_parts')->update([
            'price' => DB::raw('ROUND(price / '.self::SOM_PER_USD.', 2)'),
        ]);

        Schema::table('profitability_calculations', function (Blueprint $table): void {
            $table->dropColumn('currency_code');
        });
    }
};

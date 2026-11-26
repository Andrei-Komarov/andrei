<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitabilityCalculation extends Model
{
    protected $attributes = [
        'currency_code' => 'USD',
    ];

    protected $fillable = [
        'listing_id',
        'listing_title',
        'currency_code',
        'purchase_price',
        'additional_costs',
        'advertising_costs',
        'customs_cost',
        'parts_snapshot',
        'sale_price',
        'commission_percent',
        'total_cost',
        'commission_amount',
        'net_profit',
        'roi_percentage',
        'profit_margin_percentage',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'additional_costs' => 'decimal:2',
            'advertising_costs' => 'decimal:2',
            'customs_cost' => 'decimal:2',
            'parts_snapshot' => 'array',
            'sale_price' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'roi_percentage' => 'decimal:2',
            'profit_margin_percentage' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(LaptopListing::class);
    }
}

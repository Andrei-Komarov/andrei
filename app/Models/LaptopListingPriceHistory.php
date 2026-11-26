<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaptopListingPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = ['laptop_listing_id', 'price', 'currency', 'observed_at'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'observed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(LaptopListing::class, 'laptop_listing_id');
    }
}

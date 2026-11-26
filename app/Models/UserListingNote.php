<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserListingNote extends Model
{
    protected $fillable = ['user_id', 'ebay_item_id', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

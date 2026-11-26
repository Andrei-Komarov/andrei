<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcludedWord extends Model
{
    use HasFactory;

    protected $fillable = ['word', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}

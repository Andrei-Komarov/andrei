<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'telegram_chat_id', 'avatar_path', 'email_notifications', 'telegram_notifications', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_enabled_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'email_notifications' => 'boolean',
            'telegram_notifications' => 'boolean',
            'two_factor_enabled_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function searchProfiles(): HasMany
    {
        return $this->hasMany(SearchProfile::class);
    }

    public function listingNotes(): HasMany
    {
        return $this->hasMany(UserListingNote::class);
    }

    public function profitabilityCalculations(): HasMany
    {
        return $this->hasMany(ProfitabilityCalculation::class);
    }
}

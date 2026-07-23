<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'whatsapp', 'whatsapp_hash', 'password', 'role', 'is_active', 'activated_at'])]
#[Hidden(['password', 'remember_token', 'whatsapp', 'whatsapp_hash'])]
class User extends Authenticatable
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
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'whatsapp' => 'encrypted',
            'activated_at' => 'immutable_datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->getAttribute('role') === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /** @return HasMany<UserActivationToken, $this> */
    public function activationTokens(): HasMany
    {
        return $this->hasMany(UserActivationToken::class);
    }

    /** @return HasMany<UserNotificationDelivery, $this> */
    public function userNotificationDeliveries(): HasMany
    {
        return $this->hasMany(UserNotificationDelivery::class);
    }
}

<?php

namespace App\Models;

use App\Modules\Storefront\Models\ShopItem;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'handle',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    protected static function booted(): void
    {
        // Handle (slug sklepu) generowany automatycznie z nazwy, jeśli nie podano.
        static::creating(function (User $user) {
            if (empty($user->handle)) {
                $user->handle = static::uniqueHandle($user->name);
            }
        });
    }

    /** Unikalny handle konta (slug sklepu) z nazwy. */
    public static function uniqueHandle(?string $name): string
    {
        $base = Str::slug((string) $name) ?: 'sklep';
        $handle = $base;
        $i = 2;
        while (static::where('handle', $handle)->exists()) {
            $handle = $base.'-'.$i++;
        }

        return $handle;
    }

    /** Produkty (sklep) tego konta. */
    public function shopItems(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_name',
        'currency',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($wallet) {
            $wallet->uuid = \Str::uuid();
        });
    }

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Methods
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function getBalanceAttribute($value): string
    {
        return number_format($value, 2, '.', '');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByOwner($query, string $ownerName)
    {
        return $query->where('owner_name', 'like', "%{$ownerName}%");
    }

    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', strtoupper($currency));
    }
}

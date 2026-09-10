<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_FARMER = 'farmer';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isFarmer(): bool
    {
        return ! $this->isAdmin();
    }

    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'مدير النظام' : 'مزارع';
    }

    public function scopeFarmers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_FARMER);
    }

    public function crops()
    {
        return $this->hasMany(Crop::class);
    }

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function buyerPayments()
    {
        return $this->hasMany(BuyerPayment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    /**
     * هل الموسم مغلق (مؤرشف)؟ المواسم المغلقة لا يُسمح بتعديل مبيعاتها أو مصاريفها.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * سنة الموسم المعتمدة في الأرشيف والمقارنات (سنة تاريخ البدء).
     */
    public function year(): ?int
    {
        return $this->start_date ? (int) date('Y', strtotime($this->start_date)) : null;
    }

    /**
     * إجمالي الإنتاج المُباع بالطن.
     */
    public function totalQuantityTons(): float
    {
        return (float) $this->sales->sum('quantity');
    }

    public function totalExpensesUSD()
    {
        return $this->expenses->reduce(function ($carry, $e) {
            $rate = ($e->exchange_rate && $e->exchange_rate > 0) ? $e->exchange_rate : 1;

            return $carry + (($e->currency === 'USD') ? $e->amount : ($e->amount / $rate));
        }, 0);
    }

    public function totalSalesUSD()
    {
        return $this->sales->reduce(function ($carry, $s) {
            $rate = ($s->exchange_rate && $s->exchange_rate > 0) ? $s->exchange_rate : 1;

            return $carry + (($s->currency === 'USD') ? $s->total_price : ($s->total_price / $rate));
        }, 0);
    }

    public function netProfitUSD()
    {
        return $this->totalSalesUSD() - $this->totalExpensesUSD();
    }

    /**
     * متوسط الإنتاجية بالطن لكل دونم (يعتمد على مساحة الأرض).
     */
    public function yieldPerDunum(): ?float
    {
        $area = $this->field->area_dunums ?? null;

        if (! $area || $area <= 0) {
            return null;
        }

        return $this->totalQuantityTons() / (float) $area;
    }
}

<?php

namespace App\Models;

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
}
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasReceiptAttachment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerPayment extends Model
{
    use BelongsToUser, HasFactory, HasReceiptAttachment;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * قيمة الدفعة محولة إلى الدولار الأمريكي.
     */
    public function amountUSD(): float
    {
        $rate = ($this->exchange_rate && $this->exchange_rate > 0) ? (float) $this->exchange_rate : 1;

        return ($this->currency === 'USD')
            ? (float) $this->amount
            : ((float) $this->amount / $rate);
    }

    /**
     * إجمالي دفعات تاجر معين بالدولار.
     */
    public static function totalForBuyerUSD(string $buyerName): float
    {
        return static::where('buyer_name', $buyerName)->get()
            ->reduce(fn ($carry, $p) => $carry + $p->amountUSD(), 0.0);
    }
}

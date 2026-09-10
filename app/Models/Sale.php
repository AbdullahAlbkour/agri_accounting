<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use BelongsToUser, HasFactory;

    protected $guarded = [];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * الدفعات اللاحقة (سندات القبض) المرتبطة بهذه الفاتورة.
     */
    public function payments()
    {
        return $this->hasMany(BuyerPayment::class);
    }

    protected function toUSD($amount): float
    {
        $rate = ($this->exchange_rate && $this->exchange_rate > 0) ? (float) $this->exchange_rate : 1;

        return ($this->currency === 'USD') ? (float) $amount : ((float) $amount / $rate);
    }

    public function totalPriceUSD(): float
    {
        return $this->toUSD($this->total_price);
    }

    public function paidAmountUSD(): float
    {
        return $this->toUSD($this->paid_amount);
    }

    /**
     * المتبقي على الفاتورة قبل احتساب الدفعات اللاحقة (بعملة الفاتورة).
     */
    public function remainingAmountUSD(): float
    {
        return $this->toUSD($this->remaining_amount);
    }

    /**
     * إجمالي الدفعات اللاحقة المرتبطة بهذه الفاتورة بالدولار.
     */
    public function paymentsUSD(): float
    {
        return $this->payments->reduce(fn ($carry, $p) => $carry + $p->amountUSD(), 0.0);
    }

    /**
     * المتبقي الفعلي بعد خصم الدفعات اللاحقة المرتبطة بالفاتورة (بالدولار).
     */
    public function netRemainingUSD(): float
    {
        return max(0, $this->remainingAmountUSD() - $this->paymentsUSD());
    }
}

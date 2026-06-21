<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtherDebt extends Model
{
    protected $fillable = ['user_id', 'person', 'name', 'total_amount', 'months', 'start_month'];

    protected $casts = [
        'start_month'  => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(OtherDebtPayment::class);
    }

    public function getPaymentPerMonthAttribute(): float
    {
        return $this->months > 0 ? round($this->total_amount / $this->months, 2) : 0;
    }

    public function getPaidMonthsCountAttribute(): int
    {
        return $this->payments()->where('is_paid', true)->count();
    }

    public function getMonthsRemainingAttribute(): int
    {
        return $this->months - $this->paid_months_count;
    }

    public function getAmountPaidAttribute(): float
    {
        return round($this->paid_months_count * $this->payment_per_month, 2);
    }

    public function getAmountRemainingAttribute(): float
    {
        return round($this->total_amount - $this->amount_paid, 2);
    }
}

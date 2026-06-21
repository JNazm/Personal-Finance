<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Commitment extends Model
{
    protected $fillable = ['user_id', 'name', 'amount_per_month', 'date_started'];

    protected $casts = [
        'date_started'     => 'date',
        'amount_per_month' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(CommitmentPayment::class);
    }

    /** Ensure payment rows exist for every month from start up to current month. */
    public function syncPayments(): void
    {
        $cursor = $this->date_started->copy()->startOfMonth();
        // Generate up to end of next year
        $end = \Carbon\Carbon::now()->addYear()->endOfYear()->startOfMonth();

        while ($cursor->lte($end)) {
            $dateStr = $cursor->format('Y-m-d');
            $this->payments()->firstOrCreate(
                ['month_date' => $dateStr],
                ['is_paid' => false]
            );
            $cursor->addMonth();
        }
    }

    public function getTotalMonthsAttribute(): int
    {
        return $this->payments()->count();
    }

    public function getPaidMonthsCountAttribute(): int
    {
        return $this->payments()->where('is_paid', true)->count();
    }

    public function getMonthsRemainingAttribute(): int
    {
        return $this->total_months - $this->paid_months_count;
    }

    public function getAmountPaidAttribute(): float
    {
        return round($this->paid_months_count * $this->amount_per_month, 2);
    }

    public function getAmountRemainingAttribute(): float
    {
        return round($this->months_remaining * $this->amount_per_month, 2);
    }
}

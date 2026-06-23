<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Commitment extends Model
{
    protected $fillable = ['user_id', 'name', 'amount_per_month', 'date_started', 'type', 'end_date'];

    protected $casts = [
        'date_started'     => 'date',
        'end_date'         => 'date',
        'amount_per_month' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(CommitmentPayment::class);
    }

    /** Ensure payment rows exist for every month from start up to end date. */
    public function syncPayments(): void
    {
        $startDay = $this->date_started->day;
        $cursor   = $this->date_started->copy()->day($startDay);

        if (in_array($this->type, ['normal', 'normal-month']) && $this->end_date) {
            $end = $this->end_date->copy()->day($startDay);
        } else {
            $end = \Carbon\Carbon::create(2040, 12, $startDay);
        }

        while ($cursor->lte($end)) {
            $dateStr = $cursor->format('Y-m-d');
            $this->payments()->firstOrCreate(
                ['month_date' => $dateStr],
                ['is_paid' => false]
            );
            $cursor->addMonthNoOverflow();
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $fillable = ['debt_id', 'month_index', 'is_paid'];

    protected $casts = [
        'is_paid' => 'boolean',
    ];

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }
}

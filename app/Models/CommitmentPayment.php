<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitmentPayment extends Model
{
    protected $fillable = ['commitment_id', 'month_date', 'is_paid'];

    protected $casts = [
        'is_paid' => 'boolean',
    ];

    public function commitment()
    {
        return $this->belongsTo(Commitment::class);
    }
}

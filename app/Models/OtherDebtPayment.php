<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherDebtPayment extends Model
{
    protected $fillable = ['other_debt_id', 'month_index', 'is_paid'];

    protected $casts = ['is_paid' => 'boolean'];

    public function otherDebt()
    {
        return $this->belongsTo(OtherDebt::class);
    }
}

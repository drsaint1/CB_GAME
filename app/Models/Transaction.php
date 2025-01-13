<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // protected $fillable = ['user_id', 'transaction_type', 'amount', 'balance_before', 'balance_after', 'reference_id'];
    protected $fillable = ['user_id', 'transaction_id', 'action', 'amount'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

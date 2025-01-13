<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{

    protected $fillable = ['user_id', 'total_points', 'withdrawn_points'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

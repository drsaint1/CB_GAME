<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Referral extends Model
{

    protected $fillable = ['referrer_wallet', 'referred_wallet', 'bonus'];
    // protected $fillable = ['referrer_id', 'referred_id', 'bonus'];
    // protected $fillable = ['referrer_wallet', 'referred_wallet'];
    // public function referrer()
    // {
    //     return $this->belongsTo(User::class, 'referrer_wallet');
    // }

    public function user()
{
    return $this->belongsTo(User::class);
}
}

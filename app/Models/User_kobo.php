<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User_kobo extends Authenticatable
{
    use HasApiTokens, HasUuid, HasFactory, Notifiable;

    protected $table = "users_kobo";


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'user_type',
        'user_table_id',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'user_table_id');
    }

    public function logistic()
    {
        return $this->belongsTo(Logistic::class, 'user_table_id');
    }
}

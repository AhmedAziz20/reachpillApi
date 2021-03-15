<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens,Notifiable;

    public function pharmacy(){
        return $this->hasOne(Pharmacy::class);
    }

    public function order(){
        return $this->hasMany(Order::class);
    }

    public function promo(){
        return $this->belongsToMany(Promo::class);
    }

    public function alarm(){
        return $this->hasMany(Alarm::class);
    }

    public function complaint(){
        return $this->hasMany(Complaint::class);
    }

    public function cosmetic(){
        return $this->hasMany(Cosmetic::class);
    }

    public function tip(){
        return $this->hasMany(Tip::class);
    }

    public function package(){
        return $this->hasMany(Package::class);
    }
    public function ads (){
        return $this->hasMany(Ad::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }
    
    public function notification(){
        return $this->hasMany(Notification::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','phone','address','photo','gender','disease','dob','active','lat', 'lng','role'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','email_verified_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}

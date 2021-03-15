<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function pharmacy(){
        return $this->belongsTo(Pharmacy::class);
    }

    public function alarm(){
        return $this->hasOne(Alarm::class);
    }

    public function rate(){
        return $this->hasOne(Rate::class);
    }

    public function cosmetics(){
        return $this->belongsToMany(Cosmetic::class);
    }

    public function packages(){
        return $this->belongsToMany(Package::class);
    }

    protected $fillable = [
        'order_type', 'address', 'image','name','pharmacy_id','phone','price','created_at'
    ];
}

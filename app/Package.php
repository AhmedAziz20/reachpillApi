<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);    
    }

    public function cosmetics(){
        return $this->belongsToMany(Cosmetic::class);
    }

    public function pharmacy(){
        return $this->belongsToMany(Pharmacy::class);
    }

    public function Order(){
        return $this->belongsToMany(Order::class);
    }


    protected $fillable = [
        'name' , 'price' , 'image'
    ];
}

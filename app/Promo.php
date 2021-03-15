<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    public function user(){
        return $this->belongsToMany(User::class);
    }

    protected $fillable = [
      'code','status','expireDate' , 'discount'
    ];
}

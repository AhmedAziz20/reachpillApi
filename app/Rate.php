<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    public function order(){
        return $this->belongsTo(Order::class);
    }

    protected $fillable = [
        'rate'
    ];

    protected $hidden = [
        'updated_at', 'created_at','id','order_id'
    ];
}

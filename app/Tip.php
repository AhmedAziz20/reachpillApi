<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    public function user(){
        return $this->belongsTo(User::class);
    }

    protected $fillable =[
        'title','body'
    ];
}

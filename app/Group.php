<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function cosmetics(){
        return $this->hasMany(Cosmetic::class);
    }

    protected $fillable = [
        'name'
    ];
}

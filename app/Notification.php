<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    protected $fillable = [
        'title', 'body'
    ];
}

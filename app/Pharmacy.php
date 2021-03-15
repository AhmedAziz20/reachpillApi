<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pharmacy extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->hasMany(Order::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function packages(){
        return $this->belongsToMany(Package::class);
    }

    // to Calculate Distance between user and pharmacy
    public static function getByDistance($lat, $lng, $distance)
    {
        $results = DB::select(DB::raw('SELECT id, ( 6371 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians(lat) ) ) ) AS distance FROM pharmacies HAVING distance < ' . $distance . ' ORDER BY distance'));

        return $results;
    }


    protected $fillable = [
        'name', 'address', 'lat', 'lng','phone','availability'
    ];
}

<?php
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');



Route::get('/time', function(){
        $date = new DateTime();
        $created_at = $date->format('U = H:i:s Y-m-d');
        $current_date_time = time() + (3600*2); // 30 * 60 
        return $current_date_time;
});
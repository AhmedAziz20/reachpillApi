<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
header('Access-Control-Allow-Origin: *');
//Access-Control-Allow-Origin: *
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', 'Api\AuthController@register');
Route::post('login', 'Api\AuthController@login');
Route::post('logindashboard', 'Api\AuthController@loginDashBoard');

Route::group(['middleware' => ['web']], function () {
    Route::get('/auth/facebook', 'Api\AuthController@redirectToProvider');
});




Route::post('payment' ,'Api\PaymentController@Pay');

Route::post('/addpharmacy' , 'Api\PharmacyController@addPharmacy');
Route::post('/checkemail','Api\PharmacyController@checkEmail');
Route::post('/checkphone','Api\PharmacyController@checkPhone');
// Route::get('post/{id}', function($id){
//    $order= App\Order::find($id);
//    $order->status = 'rejected';
//    $order->save();
//    return response()->json($order);
// });
// All Routes

Route::get('testitem',function(){
    $currentMonth = date('m');
$data = DB::table("orders")
        ->whereMonth('created_at', Carbon::now()->month-1)
        ->whereDay('created_at', Carbon::now()->day)
        ->count();
return ([Carbon::now()->month => $data]);
});
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('logout', 'Api\UserController@logout');
    Route::post('updatedata' , 'Api\UserController@updateData');
    Route::post('changepassword' , 'Api\UserController@changePassword');
    Route::post('addtoken' , 'Api\UserController@addToken');

    Route::get('/user', function (Request $request) {
        $user = auth()->user();
        if(auth()->user()->role == 'vendor')
        {
            $user->pharmacy;
            return response()->json($user,200);

        }

        return auth()->user();

    });
});


//User and Admin Routes
Route::group(['middleware' =>['auth:api', 'scope:user,moderator,admin']] ,function(){
    Route::get('/getcosmetic/{id}', 'Api\CosmeticController@getCosmetic');
    Route::get('/acceptedads' , 'Api\AdsController@getAcceptedAds');
    Route::get('/getpackages' , 'Api\PackageController@getPackages');
    Route::get('/getpackagedata/{id}' , 'Api\PackageController@getPackageData');
    Route::get('/getgroup/{id}', 'Api\GroupController@getGroup');
    Route::get('/getcategory', 'Api\CategoryController@getCategory');
    Route::post('/upload', 'Api\OrderController@upload');
    Route::get('/tips' , 'Api\TipsController@getTips');

    
});

// User Routes
Route::group(['middleware' => ['auth:api', 'scope:user']], function () {
    Route::post('/addorder', 'Api\OrderController@addOrder');
    Route::get('/getorderuser', 'Api\OrderController@getOrderForUser');
    Route::post('/addcomplaint', 'Api\ComplaintController@addComplaint');
    Route::post('/addrate', 'Api\RateController@addRate');
    Route::post('/nearest' , 'Api\PharmacyController@findNearestPharmacy');
    Route::post('/addproduct' , 'Api\ProductController@addProduct');
    Route::get('/getproduct' , 'Api\ProductController@getProduct');
    Route::post('/updateproduct' , 'Api\ProductController@updateProduct');
    Route::post('/deleteproduct' , 'Api\ProductController@deleteProduct');
    Route::get('/getalarm' , 'Api\AlarmController@getAlarms');
    Route::post('/addpromotouser' , 'Api\PromoController@addPromoToUser');
    
    Route::get('getNotification', 'Api\NotificationController@getNotification');
});


// Vendor Routes
Route::group(['middleware' => ['auth:api', 'scope:vendor']], function () {
    // $user = auth()->user();
    // if(!$user->active) {
    //     return response()->json(['failed'=>'your pharmacy not active right now '],404);
    // }
    Route::get('/getordervendor', 'Api\OrderController@getOrderForVendor');
    Route::get('/getacceptedordervendor', 'Api\OrderController@getAcceptedOrder');
    Route::get('/getrejectedordervendor', 'Api\OrderController@getRejectedOrder');
    Route::get('/getpendingorder', 'Api\OrderController@getPendingOrder');
    Route::get('/getsuspendingorder', 'Api\OrderController@getSuspendingOrder');
    Route::post('/acceptorder', 'Api\OrderController@onAcceptOrder');
    Route::post('/rejectorder', 'Api\OrderController@onRejectOrder');
    Route::post('/pharmacynotavailible', 'Api\PharmacyController@pharmacyNotAvailible');
    Route::get('/pharmacyavailibility', 'Api\PharmacyController@pharmacyAvailibility');
    Route::post('/addalarm' , 'Api\AlarmController@addAlarm');
    Route::get('/getorderwithratevendor', 'Api\RateController@getOrdersWithRateForVendor');
    Route::post('updatepharmacy' , 'Api\UserController@updatePharmacyData');
    Route::get('getvendorstatistics','Api\StatisticsController@getStatisticsForVendor');
    Route::post('/noresponseorder', 'Api\OrderController@onNotResponseOrder');

});

// Moderator Routes
Route::group(['middleware' => ['auth:api', 'scope:moderator,admin']], function () {
    Route::get('/getorderadmin', 'Api\OrderController@getAllOrdersForAdmin');
    Route::get('/getpharmacy' , 'Api\PharmacyController@getPharmacy');
    Route::get('/deletepharmacy/{id}' , 'Api\PharmacyController@deletePharmacy');
    Route::post('/addtip' , 'Api\TipsController@addTip');
    Route::post('/addpromo' , 'Api\PromoController@addPromo');
    Route::get('/getpromo' , 'Api\PromoController@getPromo');
    Route::get('/deactivepromo/{id}' , 'Api\PromoController@DeactivePromo');
    Route::get('/deletepromo/{id}' , 'Api\PromoController@DeletePromo');
    Route::get('/deleteads/{id}' , 'Api\AdsController@deleteAds');
    Route::get('/getallcomplaints', 'Api\ComplaintController@getAllComplaints');
    Route::get('/getorderwithrate', 'Api\RateController@getOrdersWithRate');
    Route::post('/addcosmetic', 'Api\CosmeticController@addCosmetic');
    Route::post('/acceptads' , 'Api\AdsController@AcceptAds');
    Route::post('/rejectads' , 'Api\AdsController@RejectAds');
    Route::get('/pendingads' , 'Api\AdsController@getPendingAds');
    Route::post('/addpackage' , 'Api\PackageController@addPackage');
    Route::post('/updatepackage' , 'Api\PackageController@updatePackage');
    Route::post('/addads' , 'Api\AdsController@addAds');
    Route::get('/getads' , 'Api\AdsController@getAds');
    Route::post('/addcategory', 'Api\CategoryController@addCategory');
    Route::post('/addgroup', 'Api\GroupController@addGroup');
    Route::get('/getnormalusers', 'Api\UserController@getNormalUsers');
    Route::get('/getvendors', 'Api\UserController@getVendors');
    Route::get('/getpendingpharmacy', 'Api\PharmacyController@getPendingPharmacy');
    Route::get('/searchuserbyid/{id}', 'Api\UserController@searchUserById');
    Route::get('/searchvendorbyid/{id}', 'Api\UserController@searchVendorById');
    Route::get('/countnewpharmacy', 'Api\UserController@numberNewPharmacy');
    Route::get('/orderbyuserid/{id}', 'Api\OrderController@getOrderByUserId');
    Route::post('/acceptpharmacy', 'Api\PharmacyController@aceeptPharmacy');
    Route::post('/rejectpharmacy', 'Api\PharmacyController@rejectPharmacy');
    Route::post('/searchuser', 'Api\UserController@searchUser');
    Route::post('/searchvendor', 'Api\UserController@searchVendor');
    Route::post('/searchpending', 'Api\UserController@searchPending');
    Route::get('/getorderpharmacy/{id}', 'Api\OrderController@getOrderPharmacy');

    Route::get('/getorderpharmacybyuserid/{id}' , 'Api\OrderController@getOrderPharmacyByUserId');
    Route::get('getgroups' , 'Api\GroupController@getGroups');
    Route::get('/getgroupscosmetic' , 'Api\GroupController@getGroupsCosmetic');
    Route::get('/getcosmetics', 'Api\CosmeticController@getCosmetics');
    Route::get('/getcosmeticsgroup', 'Api\CosmeticController@getCosmeticsGroup');
    Route::get('/getpharmacies' , 'Api\PharmacyController@getPharmacies');
    Route::post('/deletecosmetic', 'Api\CosmeticController@deleteCosmetic');
    Route::post('/updatecosmetic', 'Api\CosmeticController@updateCosmetic');
    Route::post('deletegroup' , 'Api\GroupController@deleteGroup');
    Route::post('deletepackage' , 'Api\PackageController@deletePackage');
    Route::get('/getpackagesweb' , 'Api\PackageController@getPackagesWeb');
    Route::get('groupbyid/{id}' , 'Api\GroupController@getGroupById');
    Route::get('/getpackagepharmacybyid/{id}' , 'Api\PackageController@getPackagePharmacyById');
    Route::get('/getpackagecosmeticbyid/{id}' , 'Api\PackageController@getPackageCosmeticById');
    Route::get('getadmintatistics','Api\StatisticsController@getStatisticsForAdmin');

    Route::get('/getacceptedorderadmin', 'Api\OrderController@getAcceptedOrderAdmin');
    Route::get('/getrejectedorderadmin', 'Api\OrderController@getRejectedOrderAdmin');
    Route::get('/getsuspendingorderadmin', 'Api\OrderController@getSuspendingOrderAdmin');
    Route::get('/getadminads' , 'Api\AdsController@getAds');


});


// Admin Routes
Route::group(['middleware' => ['auth:api', 'scope:admin']], function () {
    Route::post('/addadmin', 'Api\UserController@addAdmin');
    Route::post('/deleteadmin', 'Api\UserController@deleteAdmin');
    Route::post('/updaterole', 'Api\UserController@updateRole');
    Route::get('/getadmin', 'Api\UserController@getAdmin');
    Route::get('/getmoderator', 'Api\UserController@getModerator');

});

// Route::middleware(['auth:api',['scope:user']])->get('/user', function (Request $request) {
//     return $request->user();
// });

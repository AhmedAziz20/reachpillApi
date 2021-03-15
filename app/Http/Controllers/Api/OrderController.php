<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use Illuminate\Support\Carbon;
use App\Order;
use App\Pharmacy;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Promo;
class OrderController extends Controller
{
    // upload images
    public function upload(Request $request)
    {
        $request->validate([
            'image'=>'required|max:4096',
            'image.*' => 'mimes:jpeg,png,jpg,gif,svg'
        ]);
        $insert = array();
        if ($image = $request->file('image')) {
            foreach ($image as $files) {

            $destinationPath = 'images/'; // upload path
            // $profileImage =  md5_file($files->getRealPath())->getClientOriginalExtension();

            $ImageName =  auth()->user()->email[0] . md5_file($files->getRealPath()) . "." . $files->getClientOriginalExtension();
            $files->move($destinationPath, $ImageName);
             $insert[] = $ImageName;
            }
        }
        // $check = Image::insert($insert);
        return response()->json(['images'=> $insert],200);
    }
    // to add new order
    public function addOrder(Request $request){
       $orderData = $request->validate([
            'address'=>'required',
            'pharmacy_id'=>'required',
            'phone' => 'required',
        ]);

        // $orderData['image'] = json_decode($orderData['image']);
        // $order = new Order;
        $orderData['order_type'] = 'medication';
        $current_date_time = Carbon::now();
        $current_date_time->modify('+2 hour');
        $user = auth()->user();
        // return $user->product()->get();
        $data = $user->product()->where('type','med')->get(['data']);
        $image = $user->product()->where('type','med')->get(['image']);
        $names = [];
        $images = [];

        if(isset($data)){
            for($i = 0 ;$i <count($data) ; $i++){
                if(!$data[$i]->data)
                    break;
            $nm = json_decode($data[$i]->data);
            for($j = 0 ;$j < count($nm) ; $j++)
                array_push($names,$nm[$j]);
        }
    }
        if(isset($image)){
            for($i = 0 ;$i < count($image) ; $i++){
                if(!$image[$i]->image)
                    break;
            $im = json_decode($image[$i]->image);
            for($j = 0 ;$j < count($im) ; $j++)
                array_push($images,$im[$j]);
        }
    }
          
        $orderData['name'] = json_encode($names) ;
        $orderData['image'] = json_encode($images);
        $orderData['cosmetic'] = json_encode($user->product()->where('type','cosmetic')
        ->get(['product_id']));
        $orderData['package'] = json_encode($user->product()->where('type','package')
        ->get(['product_id']));

        $orderData['price'] = $user->product->sum('price');
        $orderData['created_at'] = $current_date_time->toDateTimeString();
        $order =  $user->order()->create($orderData);

       if($orderData['cosmetic'] != null)
       {
        $cosmetic = json_decode($orderData['cosmetic']);
        // return $cosmetic;
        $cosmetic_ids = array();
        foreach($cosmetic as $c){
            $cosmetic_ids [] = $c->product_id;
        }
        //    $ = json_decode($orderData['cosmetic']);

           $order->cosmetics()->attach($cosmetic_ids);
       }

       if($orderData['package'] != null)
       {
        $package = json_decode($orderData['package']);

        $package_ids = array();
        foreach($package as $p){
            $package_ids [] = $p->product_id;
        }

           $order->packages()->attach($package_ids);
       }
       $fullData = null;
        if($order->cosmetics != null){
            if($order->packages !=null){
             $fullData = Order::with('cosmetics')->with('packages')->with('user')->find($order->id);
             for($i=0;$i < count($fullData['packages']) ; $i++){
                 $cosmetics = $fullData['packages'][$i]->cosmetics;
                 $fullData['packages'][$i]['cosmetics'] = $cosmetics;
             }
            }
        }
        else if($order->packages != null)
        {
            $fullData = Order::with('packages')->with('user')->find($order->id);
            for($i=0;$i < count($fullData['packages']) ; $i++){
                 $cosmetics = $fullData['packages'][$i]->cosmetics;
                 $fullData['packages'][$i]['cosmetics'] = $cosmetics;
             }
        }
        else {
            $fullData = $order;
            $fullData->user;
        }
       
        $promo = $user->promo()->latest()->first();
        $hasPromo = false;
        if($promo)
            $hasPromo = DB::table("promo_user")->where('promo_id',$promo->id)->where('user_id',$user->id)->where('used',0)->count();
         
        // sendNotification($orderData);
        $pharmacy =Pharmacy::find($orderData['pharmacy_id']);
        $token = $pharmacy->user->token;
     //   return $token;
        $fullData->name = json_decode($fullData->name);
        $fullData->image = json_decode($fullData->image);
        // return ($fullData);
        if($hasPromo){

            // DB::update('update promo_user set used = 1 where user_id = ? AND promo_id = ? ', [$user->id,$promo->id]);
            $fullData['has_promo'] = true;
            $fullData['discount'] = $promo->discount;
           //     $res = array(
         //       'data' => $fullData,
       //         'has_promo' => true ,
       //         'discount' => $promo->discount
         //       );
    //    pushOrderNotification($res,$token);
    //    return response()->json($res,201);

        }else
        $fullData['has_promo'] = false;
            //    $res = array(
          //      'data' => $fullData,
     //         'has_promo' => false ,
     //           );
        // return response()->json([$order->packages,$order->cosmetics],200);
            pushOrderNotification($fullData,$token);
            // pushToMobile('test','test',$fullData,$token);
        return response()->json($fullData,201);
    }

    public function getOrderForVendor(){
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $pharmacy = auth()->user()->pharmacy;
        if(!$pharmacy)
        return response()->json(['error'=>'this user not related to pharmacy']);
        $orders = $pharmacy->order()
        ->with('cosmetics')
        ->with('packages')
        ->with('user')
        ->paginate(20);
        for($i = 0 ; $i<count($orders);$i++){
          $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }

          // foreach ($orders as $order) {
          //       for($j = 0 ; $j < count($order->name) ; $j++){
          //           $order->name[$j]->data = json_decode($order->name[$j]->data);
          //           $order->image[$j]->image = json_decode($order->image[$j]->image);
          //     }
          // }


        return response()->json($orders);
    }



    public function getOrderForUser(){
        $orders = auth()->user()->order()->orderBy('id','DESC')->get();
        for($i = 0 ; $i<count($orders);$i++){
            $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }
        return response()->json($orders);
    }

    public function getAllOrdersForAdmin(){
        $orders = Order::with('pharmacy')
        ->with('packages')
        ->with('cosmetics')
        ->with('user')
        ->orderBy('id','DESC')
        ->paginate(20);
        // $orders->name = json_decode($orders->name);
        for($i = 0 ; $i<count($orders);$i++){
            $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }
        return response()->json($orders,200);
    }

    // when vendor open website , get the pending orders that related to him!
    public function getPendingOrder(){
        if(!auth()->user()->active) {
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }

        $pharmacy_id = auth()->user()->pharmacy->id;
        $current_date_time = Carbon::now();
        $current_date_time->modify('+2 hour');
        $orders = Order::where('pharmacy_id',$pharmacy_id)
        ->with('cosmetics')
        ->with('packages')
        ->with('user')
        ->orderBy('id','DESC')
        ->where('status','3')
        ->where('created_at','>',$current_date_time->modify('-5 minute'))
        ->get();
        $res = array();
        for($i = 0 ; $i<count($orders);$i++){
           
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          $promo = $orders[$i]->user->promo()->latest()->first();
          $user = $orders[$i]->user;
          $hasPromo = false;
          if($promo)
         $hasPromo = DB::table("promo_user")->where('promo_id',$promo->id)->where('user_id',$user->id)->where('used',0)->count();
         if($hasPromo){
            // DB::update('update promo_user set used = 1 where user_id = ? AND promo_id = ? ', [$user->id,$promo->id]);
            $orders[$i]['has_promo'] = true;
            $orders[$i]['discount'] = $promo->discount;
             }
             else {
             $orders[$i]['has_promo'] = false ;
             }
              if(strtotime($orders[$i]->created_at) > strtotime("-4 minutes"))
            {
                array_push($res ,$orders[$i] );
            }
            
            for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
             
        }
           return response()->json($res,200);
    }

    public function getSuspendingOrder(){
        if(!auth()->user()->active) {
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $pharmacy_id = auth()->user()->pharmacy->id;
        $orders = Order::where('pharmacy_id',$pharmacy_id)
        ->with('cosmetics')
        ->with('packages')
        ->with('user')
        ->where('status','4')
        ->orderBy('id','DESC')
        ->paginate(20);
        for($i = 0 ; $i<count($orders);$i++){
            $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }
        return response()->json($orders,200);
    }

        // when vendor open website , get the accepted orders that related to him!
        public function getAcceptedOrder(){
            if(!auth()->user()->active) {
                return response()->json(['failed'=>'your pharmacy not active right now '],404);
            }
            $pharmacy_id = auth()->user()->pharmacy->id;
            $orders = Order::where('pharmacy_id',$pharmacy_id)
            ->with('cosmetics')
            ->with('packages')
            ->with('user')
            ->where('status','1')
            ->orderBy('id','DESC')
            ->paginate(20);
            for($i = 0 ; $i<count($orders);$i++){
                $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
              $orders[$i]->name = json_decode($orders[$i]->name);
              $orders[$i]->image = json_decode($orders[$i]->image);
              
              for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
            }
            return response()->json($orders,200);
        }

            // when vendor open website , get the rejected orders that related to him!
    public function getRejectedOrder(){
        if(!auth()->user()->active) {
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $pharmacy_id = auth()->user()->pharmacy->id;
        $orders = Order::where('pharmacy_id',$pharmacy_id)
        ->with('cosmetics')
        ->with('packages')
        ->with('user')
        ->where('status','2')
        ->orderBy('id','DESC')
        ->paginate(20);
        for($i = 0 ; $i<count($orders);$i++){
            $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }
        return response()->json($orders,200);
    }

    //on accept order
    public function onAcceptOrder(Request $request){
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $validatedData = $request->validate([
            'order_id'=>'required|integer',
            'price' => 'required',
            'alarm' => 'nullable',
            'time' => 'required',
            'dates' => 'nullable'
        ]);

        $order = Order::find($validatedData['order_id']);
        $order->status = '1';
        $order->price = $validatedData['price'];
        $order->save();
        $pharmacy = $order->pharmacy;
        $user = $order->user;
        $promo = DB::select('select promo_id from promo_user where user_id = ? AND used = ? ' ,[$user->id,0]);
        // return $promo[0]->promo_id;
        if($promo){
            DB::update('update promo_user set used = 1 where user_id = ? AND promo_id = ? ', [$user->id,$promo[0]->promo_id]);
            DB::update('update promo_user set order_id = ? where user_id = ? AND promo_id = ? ', [$order->id,$user->id,$promo[0]->promo_id]);
        }

        $validatedData['user_id'] = $order->user_id;
        $validatedData['details'] = $request['alarm'] ? $request['alarm'] : "No Details" ; 
        // $validatedData['dates'] = ($validatedData['dates']); 
        $alarm = $order->alarm()->create($validatedData);
        $details = array(
          'pharmacyName' => $pharmacy->name,
          'price' => $order->price,
          'tips' =>isset($request['alarm']) ? $request['alarm'] : [],
          'dates' => isset($request['dates']) ? json_decode($request['dates']) : [],
          'time' => $validatedData['time']
        );
        $token = $order->user->token;
        $msg ="order in his way, and it will arrive in ".$validatedData['time']." minutes";
        // return $token;
        pushToMobile($pharmacy->name,$msg,$details,$token,$order->user_id);
        DB::delete('delete from products where user_id = ?' , [$order->user_id]);
        return response()->json(['success'=>'order accepted successfully'],200);
    }
    // on reject order
    public function onRejectOrder(Request $request){
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $validatedData = $request->validate([
            'order_id' => 'required|integer'
        ]);
        $order = Order::find($validatedData['order_id']);
        if(!$request->is_pending){
        $order->status = '2';

        $order->save();
        }


    
        $user = User::find($order->user_id);
        $pharmacy = Pharmacy::find($order->pharmacy_id);
        $lat = $pharmacy['lat'] ;
        $lng = $pharmacy['lng'];
        $distance = 10;
        $current_date_time = Carbon::now();
        $current_date_time->modify('+2 hour');
        $data = Pharmacy::getByDistance($lat,$lng,$distance);


        if(empty($data)) {
            return response()->json(['error','there are no pharmacies nearest of you'],200);
          }

          $ids = [];

          //Extract the id's
          foreach($data as $q)
          {
              if(Pharmacy::find($q->id)['availability'])
                 array_push($ids, $q->id);
          }

        //   $test = Pharmacy::select('name')->get()->toArray;
        $pharmacy_id = null;
        for($i = 0 ; $i< count($ids) ; $i++){
            if($ids[$i] == $pharmacy->id){
                if(isset($ids[$i+1]))
                    $pharmacy_id = $ids[$i+1];
                else
                {
                    $msg = "يؤسفنا ان طلبك غير متاح في منطقتك الأن";
                    $token = $user->token;
                    pushToMobile("NA",$msg,[],$token,$user->id);
                    return ;
                }
            }
        }
        $newOrder = array();
        $newOrder['user_id'] = $order->user_id;
        $newOrder['order_type'] = $order->order_type;
        $newOrder['pharmacy_id'] = $pharmacy_id;
        $newOrder['address'] = $order->address;
        $newOrder['phone'] = $order->phone;
        $newOrder['price'] = $order->price;
        $newOrder['name'] = $order->name;
        $newOrder['image'] = $order->image;
        $newOrder['created_at'] = $current_date_time->toDateTimeString();
        $newOrder['cosmetic'] = json_encode($user->product()->where('type','cosmetic')
        ->get(['product_id']));
        $newOrder['package'] = json_encode($user->product()->where('type','package')
        ->get(['product_id']));
        $newOrder['price'] = $user->product->sum('price');
        // return $orderData;
        $order =  $user->order()->create($newOrder);
       if($newOrder['cosmetic'] != null)
       {
        $cosmetic = json_decode($newOrder['cosmetic']);
        // return $cosmetic;
        $cosmetic_ids = array();
        foreach($cosmetic as $c){
            $cosmetic_ids [] = $c->product_id;
        }
        //    $ = json_decode($orderData['cosmetic']);

          $order->cosmetics()->attach($cosmetic_ids);
       }

       if($newOrder['package'] != null)
       {
        $package = json_decode($newOrder['package']);

        $package_ids = array();
        foreach($package as $p){
            $package_ids [] = $p->product_id;
        }

           $order->packages()->attach($package_ids);
       }
       $fullData = null;
        if($order->cosmetics != null){
            if($order->packages !=null){
             $fullData = Order::with('cosmetics')->with('packages')->with('user')->find($order->id);
             
             for($i=0;$i < count($fullData['packages']) ; $i++){
                 $cosmetics = $fullData['packages'][$i]->cosmetics;
                 $fullData['packages'][$i]['cosmetics'] = $cosmetics;
             }
            }
        }
        else if($order->packages != null)
        {
            $fullData = Order::with('packages')->with('user')->find($order->id);
            
            for($i=0;$i < count($fullData['packages']) ; $i++){
                 $cosmetics = $fullData['packages'][$i]->cosmetics;
                 $fullData['packages'][$i]['cosmetics'] = $cosmetics;
             }
        }
        else {
            $fullData = $order;
            $fullData->user;
        }
        
        $promo = $user->promo()->first();
        $hasPromo = false;
        if($promo)
         $hasPromo = DB::table("promo_user")->where('promo_id',$promo->id)->where('user_id',$user->id)->where('used',0)->count();

        // sendNotification($orderData);
        $pharmacy =Pharmacy::find($newOrder['pharmacy_id']);
        $token = $pharmacy->user->token;
        $fullData->name = json_decode($fullData->name);
        $fullData->image = json_decode($fullData->image);
        if($hasPromo){
            // DB::update('update promo_user set used = 1 where user_id = ? AND promo_id = ? ', [$user->id,$promo->id]);
                // $res = array(
                // 'data' => $fullData,
                // 'has_promo' => true ,
                // 'discount' => $promo->discount
                // );
                $fullData['has_promo'] = true;
                $fullData['discount'] = $promo->discount;
        }
            else
                $fullData['has_promo'] = false;
        // return response()->json([$order->packages,$order->cosmetics],200);
            pushOrderNotification($fullData,$token);
        return response()->json($fullData,201);
        // search for new pharmacy
        // return response()->json(['success'=>'order rejected successfully'],200);
    }


    public function getOrderByUserId($id){
        $orders = Order::where('user_id',$id)
        ->with('packages')
        ->with('cosmetics')
        ->with('user')
        ->orderBy('id','DESC')
        ->paginate(20);
          for($i = 0 ; $i<count($orders);$i++){
              $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }
        return response()->json($orders);
    }

    //by Pharmacy ID
    public function getOrderPharmacy($id){
        $user = User::find($id);
        $pharmacy = Pharmacy::where('user_id',$user->id)->first();
        $orders = Order::where('pharmacy_id',$pharmacy->id)
        ->with('packages')
        ->with('cosmetics')
        ->with('user')
        ->orderBy('id','DESC')
        ->paginate(20);
        for($i = 0 ; $i<count($orders);$i++){
            $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
          $orders[$i]->name = json_decode($orders[$i]->name);
          $orders[$i]->image = json_decode($orders[$i]->image);
          
          for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
        }
        return response()->json($orders);
    }

     //by User ID
        public function getOrderPharmacyByUserId($id){
                $pharmacy = Pharmacy::where('user_id',$id)->get();
                // return $pharmacy[0]->id;
                $orders = Order::where('pharmacy_id', $pharmacy[0]->id)
                 ->with('packages')
                ->with('cosmetics')
                ->with('user')
                ->orderBy('id','DESC')
                ->paginate(20);
                for($i = 0 ; $i<count($orders);$i++){
                    $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
                  $orders[$i]->name = json_decode($orders[$i]->name);
                  $orders[$i]->image = json_decode($orders[$i]->image);
                  
                  for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
                }
                return response()->json($orders);
        }



        /* ADMIN SECTION */

        public function getSuspendingOrderAdmin(){
            $orders = Order::with('cosmetics')
            ->with('packages')
            ->with('user')
            ->where('status','4')
            ->orderBy('id','DESC')
            ->paginate(20);
            for($i = 0 ; $i<count($orders);$i++){
                $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
              $orders[$i]->name = json_decode($orders[$i]->name);
              $orders[$i]->image = json_decode($orders[$i]->image);
              
              for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
            }
            return response()->json($orders,200);
        }

            // when vendor open website , get the accepted orders that related to him!
            public function getAcceptedOrderAdmin(){
                $orders = Order::with('cosmetics')
                ->with('packages')
                ->with('user')
                ->where('status','1')
                ->orderBy('id','DESC')
                ->paginate(20);
                for($i = 0 ; $i<count($orders);$i++){
                    $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
                  $orders[$i]->name = json_decode($orders[$i]->name);
                  $orders[$i]->image = json_decode($orders[$i]->image);
                  
                  for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
                }
                return response()->json($orders,200);
            }

                // when vendor open website , get the rejected orders that related to him!
        public function getRejectedOrderAdmin(){
            $orders = Order::with('cosmetics')
            ->with('packages')
            ->with('user')
            ->where('status','2')
            ->orderBy('id','DESC')
            ->paginate(20);
            for($i = 0 ; $i<count($orders);$i++){
                $promos = DB::table("promo_user")->where('order_id',$orders[$i]->id)->get();   
          if($promos){
              foreach($promos as $promo){
                  $code = Promo::find($promo->promo_id);
                  $orders[$i]->discount = $code->discount . "%";
              }
          }
              $orders[$i]->name = json_decode($orders[$i]->name);
              $orders[$i]->image = json_decode($orders[$i]->image);
              
              for($j=0;$j < count($orders[$i]['packages']) ; $j++){
                 $cosmetics = $orders[$i]['packages'][$j]->cosmetics;
                 $orders[$i]['packages'][$j]['cosmetics'] = $cosmetics;
             }
            }
            return response()->json($orders,200);
        }

        public function onNotResponseOrder(Request $request){
          $validatedData = $request->validate([
            'order_id' => 'required',
          ]);
            $request['is_pending'] = true;
          $order = Order::find($validatedData['order_id']);
          $order->status = 4;
          $order->save();
          $this->onRejectOrder($request);
          if($order)
            return response()->json(['status' => true],200);
          else
            return response()->json(['status' => false],200);
        }
}
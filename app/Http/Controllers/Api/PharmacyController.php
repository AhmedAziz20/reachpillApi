<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Pharmacy;
use App\Order;

class PharmacyController extends Controller
{

    public function checkEmail(Request $request){
        $user = User::where('email',$request->email)->first();
        if($user)
             return response()->json(['status'=>false],403);
        return response()->json(['status'=>true],200);
    }

    public function checkPhone(Request $request){
        $user = User::where('phone',$request->phone)->first();
        if($user)
             return response()->json(['status'=>false],403);
        return response()->json(['status'=>true],200);
    }

    public function addPharmacy(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'required',
            'phone' => 'required|numeric|unique:users',
            'user_name' => 'required',
            'user_address' => 'required',
            'user_phone' => 'required',
            'user_password' => 'required',
            'user_email' => 'required',
        ]);
        $Data = User::where('email',$validatedData['user_email'])->first();
        if($Data)
            return response()->json(['status'=>false,'message'=>'account already use'],200);
        $user = new User;
        $user->name = $validatedData['user_name'];
        $user->email = $validatedData['user_email'];
        $user->address = $validatedData['user_address'];
        $user->phone = $validatedData['user_phone'];
        $user->password = bcrypt($validatedData['user_password']);
        $user->role = 'vendor';
        $user->save();
        $token = $user->createToken('My Token', ['vendor'])->accessToken;
        $pharmacy =  $user->pharmacy()->create($validatedData);
            return response()->json(['status'=>true,'token'=>$token], 201);

        // if($user == null)
        //     return response()->json(['error'=>'user not found'],404);
        // if (isVendor($user)) {
        //     if ($user->pharmacy == null) {

        //     }
        //     return response()->json(['error' => 'this user already related to pharmacy'], 403);
        // }
        // return response()->json(['error' => 'user is not a vendor'], 403);
    }

    public function getPharmacy(){
        $pharmacy = Pharmacy::whereHas("user", function($q){
            $q->where("active","=",1);
         })->with('user')->withCount('order')->orderBy('id','DESC')->paginate(20);
        return response()->json($pharmacy,200);
    }

    public function getPharmacies(){
        $pharmacy = Pharmacy::get(['id','name']);
        return response()->json($pharmacy,200);

    }

    public function getPendingPharmacy(){
        $pharmacy = Pharmacy::whereHas("user", function($q){
            $q->where("active",0)->
            where('role','vendor');
         })->with('user')->orderBy('id','DESC')->paginate(20);
        return response()->json($pharmacy,200);
    }

    public function findNearestPharmacy(Request $request){
        $validatedData = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $lat = $validatedData['lat'] ;
        $lng = $validatedData['lng'];
        $distance = 10;

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
        return response()->json($ids[0],200);
    }

    public function deletePharmacy($id){

        $pharmacy = Pharmacy::find($id);
        $pharmacy->delete();

        return response()->json(['Done'],200);
    }

     // on Order TimeOut or pharmacy not available
     public function pharmacyNotAvailible(Request $request){
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $validatedData = $request->validate([
            'order_id' => 'required|integer'
        ]);

        $order = Order::find($validatedData['order_id']);
        if($order->status != 'pending')
            return;
        $pharmacy = $order->pharmacy;

        $pharmacy->availability = 0;

        $pharmacy->save();

        //search for new pharmacy

        return response()->json(['success','pharmacy is not availible right now '],200);
    }

    public function pharmacyAvailibility(){
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $pharmacy = auth()->user()->pharmacy;
        
        $pharmacy->availability = !$pharmacy->availability;

        $pharmacy->save();
        if($pharmacy->availability)
            return response()->json(['status'=>true],200);
        else
        return response()->json(['status'=>false],200);
    }
    

    public function aceeptPharmacy(Request $request){
        $validatedData = $request->validate([
            'user_id' => 'required|numeric'
        ]);

        $user = User::find($validatedData['user_id']);
        if(!isVendor($user))
        return response()->json(['status'=>false,'msg'=>'this user not vendor'],200);

        $user->active = 1;

        $user->save();

        return response()->json(['status'=>true,'msg'=>'pharmacy is active now'],200);

    }

    public function rejectPharmacy(Request $request){
        $validatedData = $request->validate([
            'user_id' => 'required|numeric'
        ]);

        $user = User::find($validatedData['user_id']);
        if(!isVendor($user))
        return response()->json(['status'=>false,'msg'=>'this user not vendor'],200);
        $pharmacy = $user->pharmacy;
        $pharmacy->delete();
        $user->delete();

        return response()->json(['status'=>true,'msg'=>'pharmacy is deleted now'],200);

    }
}

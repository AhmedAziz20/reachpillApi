<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Promo;
use Illuminate\Support\Facades\DB;

class PromoController extends Controller
{
    public function addPromo(Request $request){
        $now = date('Y-m-d');
        $validatedData = $request->validate([
            'expireDate' =>'date_format:"Y-m-d"|required|after:'.$now,
            'code' => 'required|unique:promos',
            'discount' => 'required'
        ]);

        // $validatedData['code'] = crypto(10);

        $user = auth()->user();
        $promo = new Promo;
        $promo->expireDate = $validatedData['expireDate'];
        $promo->code = $validatedData['code'];
        $promo->discount = $validatedData['discount'];
        $promo->status = 'active';
        $promo->user_id = $user->id;
        $promo->save();
        return response()->json($promo,201);
    }

    public function DeactivePromo($id){
        $promo = Promo::find($id);
        
        $promo->status = 'deactivated';

        $promo->save();

        return response()->json(['Card Deactivated Successfully'],200);
    }
    
        public function DeletePromo($id){
        $promo = Promo::find($id);
        
        $promo->delete();

        return response()->json(['status'=>true],200);
    }
    
    public function getPromo(){
     $promo = Promo::paginate(20);
     return response()->json($promo,200);
    }

       public function addPromoToUser(Request $request){
            $validatedData = $request->validate([
            'code' => 'required',
        ]);
            $now = date('Y-m-d');
            $user = auth()->user();
            $promo = Promo::where('code',$validatedData['code'])->where('expireDate', '>' , $now)->first();
            if(!$promo)
                return response()->json(['msg'=>'this promo is expired'],200);
            $check = DB::table("promo_user")->where('promo_id',$promo->id)->where('user_id',$user->id)->count();
            if($check)
                 return response()->json(['msg'=>'this promo code already used !'],200);


            $user->promo()->attach([$promo->id]);

            return response()->json(['msg'=>'promo code has been activated'],200);


    }
}

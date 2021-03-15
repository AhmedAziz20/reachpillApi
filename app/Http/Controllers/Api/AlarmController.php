<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Order;

class AlarmController extends Controller
{
    public function addAlarm(Request $request){
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $validatedAlarm = $request->validate([
            'times' =>'required|numeric',
            'every' => 'numeric|required',
            'details' => 'required',
            'order_id' => 'required',
        ]);
            $order = Order::find($validatedAlarm['order_id']);
            $validatedAlarm['user_id'] = $order->user_id;
            $alarm = $order->alarm()->create($validatedAlarm);

            //send notification

            return response()->json($alarm,201);
    }
    
    public function getAlarms(){
        $user = auth()->user();
        $alarms = $user->alarm()->with('order')->get();
        for($i = 0 ; $i < count($alarms) ; $i++){
            if(!is_array($alarms[$i]->order->name))
          $alarms[$i]->order->name = json_decode($alarms[$i]->order->name);
            if(!is_array($alarms[$i]->order->image))
          $alarms[$i]->order->image = json_decode($alarms[$i]->order->image);
          $alarms[$i]->dates = json_decode($alarms[$i]->dates);
        }
        
        return response()->json($alarms,200);
    }
}

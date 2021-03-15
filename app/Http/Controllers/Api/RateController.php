<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Order;
use App\Rate;

class RateController extends Controller
{
    public function addRate(Request $request)
    {
        $validatedRate = $request->validate([
            'order_id' => 'required|numeric',
            'rate' => 'required|integer|between:1,5'
        ]);

        $order = Order::find($validatedRate['order_id']);
        if ($order->rate == null) {
            if (auth()->user()->id == $order->user_id) {
                $rate = $order->rate()->create($validatedRate);
                return response()->json($rate, 201);
            }
            return response()->json(['error' => 'you can\'t rate this order because it\'s not related to you '], 401);
        }
        return response()->json(['error' => 'you already rated this order'], 200);
    }

    public function getOrdersWithRate()
    {
        $order = Order::with('rate')->get();
        return response()->json($order, 200);
    }

    public function getOrdersWithRateForVendor()
    {
        if(!isActive()){
            return response()->json(['failed'=>'your pharmacy not active right now '],404);
        }
        $pharmacyId = auth()->user()->pharmacy->id;
        $orders = Order::with('rate')->get()->where('pharmacy_id', $pharmacyId);

        return response()->json($orders, 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function getNotification(){
        $notifications = auth()->user()->notification()->orderBy('created_at', 'desc')->get();
        
        return response()->json(['status'=>true,'data'=>$notifications],200);
    }
}

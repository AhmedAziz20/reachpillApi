<?php

use App\User;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
/*use FCM;*/
function getCreat($query)
{

    \Illuminate\Support\Carbon::setLocale(\App::getLocale());
    return $query->created_at->diffForHumans();
}

function isActive()
{
    if (!auth()->user()->active) {
        return false;
    } else {
        return true;
    }
}
function isVendor($user)
{
    if ($user->role == 'vendor')
        return true;

    return false;
}

function isUser($user)
{
    if ($user->role == 'user')
        return true;

    return false;
}
function isAdmin($user)
{
    if ($user->role == 'admin')
        return true;

    return false;
}
function isModerator($user)
{
    if ($user->role == 'moderator')
        return true;

    return false;
}

// search

 function searchByName($name , $role){
     if($role == "vendor")
        $user = User::where('name','LIKE','%'.$name.'%')->where('role',$role)->where('active',1)->with('pharmacy')->paginate(20);
    elseif($role == 'user')
        $user = User::where('name','LIKE','%'.$name.'%')->where('role',$role)->withCount('order')->paginate(20);

    return response()->json($user);
}

 function searchByDate($start,$end , $role){
    if($role == "vendor")

    $user = User::whereBetween('created_at',array($start, $end))->where('role',$role)->where('active',1)->with('pharmacy')->paginate(20);
    elseif($role == 'user')
    $user = User::whereBetween('created_at',array($start, $end))->where('role',$role)->withCount('order')->paginate(20);

    return response()->json($user);
}

//  function searchUserByName($name){
//     $user = User::where('name','LIKE','%'.$name.'%')->where('role','user')->get();

//     return response()->json($user);
// }

//  function searchUserByDate($start,$end){
//     $user = User::whereBetween('created_at',array($start, $end))->where('role','user')->get();

//     return response()->json($user);
// }




function pushOrderNotification($order,$token)
{
    /*
    $url = "https://fcm.googleapis.com/fcm/send";
    $serverKey = 'AAAAEEt0owI:APA91bEsRA8e38KKWmKYo8kA7iEijgP_igRReSxRVo-Q-xNzpUxVadvDxxvKw1sqI841telT2JV1-ljzQsuQj4fyDoArgvq8vK3RZxc0CIaSrF7fWsIO3GuLcl3nGCBL0v9za0O4QePm';
    $link = env('APP_FRONT_END');
    $notification = array(
        'title' => 'you got new order', 'body' => 'please click on alert to get the order before reject after 3 minutes', "click_action" => $link, 'sound' => 'default',    'order' => '$order'
    );
    $arrayToSend = array('to' => $token, 'notification' => $notification, 'priority' => 'high');
    $json = json_encode($arrayToSend);
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key=' . $serverKey;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //prevent return
   $res = curl_exec($ch);
    curl_close($ch);
	*/
}

function pushToMobile($title,$body,$details,$token,$target_id = 0)
{
    $optionBuilder = new OptionsBuilder();
    $optionBuilder->setTimeToLive(60*20);
    
    $notificationBuilder = new PayloadNotificationBuilder($title);
    $notificationBuilder->setBody($body)
    				    ->setSound('default');
    
    $dataBuilder = new PayloadDataBuilder();
    $dataBuilder->addData($details);
    
    $option = $optionBuilder->build();
    $notification = $notificationBuilder->build();
    $data = $dataBuilder->build();
    
    // $token = "a_registration_from_your_database";
    
    $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
    $notificationData = new \App\Notification;
    $notificationData->title = $title;
    $notificationData->body = $body;
    $notificationData->user_id = $target_id;
    $notificationData->save();
    $downstreamResponse->numberSuccess();
    $downstreamResponse->numberFailure();
    $downstreamResponse->numberModification();
    
    // return Array - you must remove all this tokens in your database
    $downstreamResponse->tokensToDelete();
    
    // return Array (key : oldToken, value : new token - you must change the token in your database)
    $downstreamResponse->tokensToModify();
    
    // return Array - you should try to resend the message to the tokens in the array
    $downstreamResponse->tokensToRetry();
    
    // return Array (key:token, value:error) - in production you should remove from your database the tokens
    $downstreamResponse->tokensWithError();
}


function crypto($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

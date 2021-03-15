<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Pharmacy;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getNormalUsers(){
        $users = User::where('role','user')->withCount('order')->orderBy('id','DESC')->paginate(20);

        return response()->json($users);
    }


    public function searchUser(Request $request){
        $validatedData = $request->validate([
            'name' => 'nullable',
            'start' => 'date_format:"Y-m-d"|nullable',
            'end' => 'date_format:"Y-m-d"|nullable',
        ]);

        if( ($request['start'] == null || $request['end'] == null) && $request['name']){
            return searchByName($validatedData['name'],'user');
        }else if ($request['name'] == null && ($request['start'] && $request['end']) )
        {
            return searchByDate($validatedData['start'],$validatedData['end'],'user');
        }else if($request['name'] == null && $request['start'] == null && $request['end'] == null)
            return response()->json(['status'=>false]);
        $user = User::whereBetween('created_at',array($validatedData['start'], $validatedData['end']))->
        where('name','LIKE','%'.$validatedData['name'].'%')->
        where('role','user')->
        withCount('order')->
        paginate(20);

        return response()->json($user);
    }


    public function getVendors(){
        $vendors = User::where('role','vendor')->where('active',1)->with('pharmacy')->orderBy('id','DESC')->paginate(20);

        return response()->json($vendors);
    }



    public function searchUserById($id){

        $user = User::where('role','user')->find($id);
        $orders = $user->order()->paginate(20);
        return response()->json(['user'=>$user,'orders'=>$orders]);

    }

    public function searchVendor(Request $request){
        $validatedData = $request->validate([
            'name' => 'nullable',
            'start' => 'date_format:"Y-m-d"|nullable',
            'end' => 'date_format:"Y-m-d"|nullable',
        ]);

        if( ($request['start'] == null || $request['end'] == null) && $request['name']){
            return searchByName($validatedData['name'],'vendor');
        }else if ($request['name'] == null && ($request['start'] && $request['end']) )
        {
            return searchByDate($validatedData['start'],$validatedData['end'],'vendor');
        }else if($request['name'] == null && $request['start'] == null && $request['end'] == null)
            return response()->json(['status'=>false]);
        $user = User::whereBetween('created_at',array($validatedData['start'], $validatedData['end']))->
        where('name','LIKE','%'.$validatedData['name'].'%')->
        where('role','vendor')->
        where('active',1)->
        with('pharmacy')->
        paginate(20);

        return response()->json($user);
    }

    public function searchPending(Request $request){
        $validatedData = $request->validate([
            'name' => 'nullable',
            'start' => 'date_format:"Y-m-d"|nullable',
            'end' => 'date_format:"Y-m-d"|nullable',
        ]);

        if( ($request['start'] == null || $request['end'] == null) && $request['name']){
            return Pharmacy::whereHas('user' , function($q) use ($request){
                $q->where('name','LIKE','%'.$request['name'].'%')->where('role','vendor')->where('active',0);
            })->with('user')->paginate(20);
        }else if ($request['name'] == null && ($request['start'] && $request['end']) )
        {
            return Pharmacy::whereHas('user' , function($q) use ($request){
                $q->whereBetween('created_at',array($request['start'], $request['end']))->where('role','vendor')->where('active',0);
            })->with('user')->paginate(20);        }else if($request['name'] == null && $request['start'] == null && $request['end'] == null)
            return response()->json(['status'=>false]);

            return Pharmacy::whereHas('user' , function($q) use ($request){
                $q->whereBetween('created_at',array($request['start'], $request['end']))->
                 where('name','LIKE','%'.$request['name'].'%')->
                 where('role','vendor')->
                 where('active',0);
            })->with('user')->paginate(20);
    }
    public function searchVendorById($id){
        $user = User::where('role','vendor')->find($id);
        $pharmacy = Pharmacy::where('user_id',$user->id)->withCount('order')->get();
        return response()->json(['user'=>$user,'pharmacy'=>$pharmacy]);

    }

    public function updateData(Request $request){
        $validatedData = $request->validate([
            'email' => 'nullable|unique:users,email,'.auth()->user()->id.'|max:100|email',
            'name' => 'nullable',
            'address' => 'nullable',
            'phone' => 'nullable|min:11',
            'disease' => 'nullable',
            'dob' => 'date_format:"Y-m-d"|nullable',
            'gender' => 'nullable',
            'photo' => 'nullable'
        ]);
        $user = auth()->user();
        if($request['email'])
        $user->email = $request['email'] ;
        if($request['name'])
        $user->name = $request['name'] ;
        if($request['address'])
        $user->address = $request['address'] ;
        if($request['phone'])
        $user->phone = $request['phone'] ;
        if($request['disease'])
        $user->disease = $request['disease'] ;
        if($request['dob'])
        $user->dob = $request['dob'] ;
        if($request['gender'])
        $user->gender = $request['gender'] ;
        if($request['photo'])
        $user->photo = $request['photo'] ;
        $user->save();

        return response()->json(['status'=>true,'data'=>$user]);

    }

    public function updatePharmacyData(Request $request){
        $validatedData = $request->validate([
            'name' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'required',
            'phone' => 'required|numeric',
        ]);

        $pharmacy = auth()->user()->pharmacy;
        $pharmacy->name = $validatedData['name'] ;
        $pharmacy->lat = $validatedData['lat'] ;
        $pharmacy->lng = $validatedData['lng'] ;
        $pharmacy->address = $validatedData['address'] ;
        $pharmacy->phone = $validatedData['phone'] ;
        $pharmacy->save();
        return response()->json(['status'=>true,'data'=>$pharmacy]);

    }

    public function changePassword(Request $request){
        $validatedData = $request->validate([
            'oldpassword' =>'required',
            'newpassword' => 'required',
        ]);
        $current_password = auth()->user()->password;
        if(Hash::check($validatedData['oldpassword'], $current_password))
        {

                $user_id = Auth::user()->id;
                $obj_user = User::find($user_id);
                $obj_user->password = bcrypt($validatedData['newpassword']);
                $obj_user->save();
              return response()->json(['status'=>true]);
             }
            return response()->json(['status'=>false,'error'=>'old password is incorrect']);
    }

    public function numberNewPharmacy(){
        $users = User::where('active',0)->get();
        $count = $users->count();

        return response()->json(['count'=>$count]);
    }

    public function addAdmin(Request $request){
        $validatedData = $request->validate([
            'email' => 'required|unique:users|max:100|email',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone' => 'numeric|min:11|required|unique:users',
            // 'dob' => 'date_format:"Y-m-d"|required',
            'gender' => 'nullable',
            'photo' => 'nullable',
            'role' =>'required|in:admin,moderator'
        ]);
        $validatedData['dob'] = "1997-10-01";
        $validatedData['password'] = bcrypt($validatedData['password']);
        $user = new User;
        $validatedData['active'] = '1';
        $validatedData['role'] = $validatedData['role'];
        $user->create($validatedData);

        return response()->json(['status'=>true],201);
    }

    public function updateRole(Request $request){
        $validatedData = $request->validate([
            'role' =>'required|in:admin,moderator',
            'user_id' =>'required|numeric'
        ]);
            $user = User::find($validatedData['user_id']);
            $user->role = $validatedData['role'];
            $user->save();
            return response()->json(['status'=>true],200);
    }

    public function deleteAdmin(Request $request){
        $validatedData = $request->validate([
            'user_id' => 'required|numeric'
        ]);

        $admin = User::find($validatedData['user_id']);

        $admin->delete();

        return response()->json(['status'=>true,'msg'=>'admin Deleted'],200);
    }

    public function getModerator()
    {
        $moderators = User::where('role','moderator')->paginate(20);

        return response()->json($moderators,200);
    }

    public function getAdmin()
    {
        $admins = User::where('role','admin')->paginate(20);

        return response()->json($admins,200);
    }


    public function addToken (Request $request){
        $validatedToken = $request->validate([
                'token' => "required"
            ]);

            $user = auth()->user();
            $user->token = $validatedToken['token'];
            $user->save();

            return response()->json($user,200);
    }

    public function logout() {
        $accessToken = Auth::user()->token();
        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);

        $accessToken->revoke();
        return response()->json(true, 204);
    }
}

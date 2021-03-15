<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Socialite;

class AuthController extends Controller
{

    public function register(Request $request)
    {

        $validatedData = $request->validate([
            'email' => 'required|unique:users|max:100|email',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
            'name' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'required',
            'phone' => 'numeric|min:11|required|unique:users',
            'disease' => 'nullable',
            'dob' => 'date_format:"Y-m-d"|required',
            'gender' => 'required',
            'photo' => 'nullable'
        ]);
        $validatedData['password'] = bcrypt($validatedData['password']);
        $user = new User;
        $validatedData['active'] = '1';
        $user->create($validatedData);
        $token = $user->createToken('My Token', ['user'])->accessToken;

        return response()->json(['status' => true, 'token' => $token], 201);
    }


    public function redirectToProvider(){
        return Socialite::driver('facebook')->redirect();
    }

    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'token' => 'nullable'
        ]);

        if (Auth::attempt(['email' => $validatedData['email'], 'password' => $validatedData['password']])) {
            $role = Auth::user()->role;
            if ($request['token']) {
                $user = Auth::user();
                $user->token = $validatedData['token'];
                $user->save();
            }
            $token = Auth::user()->createToken('My Token', [$role])->accessToken;
            return response()->json(['status' => true, 'token' => $token], 200);
        } else {
            return response()->json(['status' => false], 401);
        }
    }


    public function loginDashBoard(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $validatedData['email'], 'password' => $validatedData['password']])) {
            if (Auth::user()->role == 'user')
                return response()->json(['status' => false, 'msg' => 'user not allowed to login here!'], 401);
            $role = Auth::user()->role;
            $token = Auth::user()->createToken('My Token', [$role])->accessToken;
            return response()->json(['status' => true, 'token' => $token], 200);
        } else {
            return response()->json(['status' => false], 401);
        }
    }
}

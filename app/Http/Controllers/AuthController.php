<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index()
    {
        return view('signin');
    }
    public function signInHandler(Request $req)
    {
        if (!Auth::attempt(['email' => $req->email, 'password' => $req->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed, please check your email and password!'
            ]);
        } else {
            $user = Auth::user();
            return response()->json([
                'success' => true,
                'message' => 'Login successfully',
                'user' => $user,
                'redirect' => route('dashboard')
            ]);
        }
    }
    public function signUp()
    {
        return view('signup');
    }
    public function signUpHandler(Request $req)
    {
        $checkEmail = User::where('email', $req->email)->first();
        if ($checkEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email already exists'
            ]);
        }

        $checkPhone = User::where('phone', $req->phone)->first();
        if ($checkPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number already exists'
            ]);
        }
        $user = User::create([
            'name'             => $req->name,
            'email'            => $req->email,
            'phone'            => $req->phone,
            'password'         => Hash::make($req->password),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        if ($user) {
            session()->flash('message_registration', 'Your account has been created, please login!');
            return response()->json([
                'success' => true,
                'message' => 'Registration successfully!',
                'user' => $user
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Registration error!',
            ]);
        }
    }


    public function logout()
    {
        auth()->logout();
        session()->flash('message', 'You have been logged out!');
        return redirect('');
    }
}

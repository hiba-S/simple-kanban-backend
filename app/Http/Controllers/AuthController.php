<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only('logout');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required','string','email'],
            'password' => ['required','string', 'min:6'],
        ]);

        if(!Auth::attempt($request->only('email', 'password'))){
            return response([
                'error' => 'The Provided credentials does not match'
            ], 422);
        }

        $user = User::where('email',$request->email)->first();

        return response([
            'user' => new UserResource($user),
            'token' => $user->createToken('API token of '.$user->name)->plainTextToken
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response([
            'user' => new UserResource($user),
            'token' => $user->createToken('API token of '.$user->name)->plainTextToken
        ]);
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return response([
            'success' => true,
            'message'=>'goodbye...'
        ]);
    }
}

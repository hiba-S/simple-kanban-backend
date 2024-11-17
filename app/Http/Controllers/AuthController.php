<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description=""
 * )
 */
class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only('logout');
    }

    /**
     * @OA\Post(
     *   path="/login",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"email","password"},
     *         @OA\Property(property="email", type="string", example=""),
     *         @OA\Property(property="password", type="password", example=""),
     *       )
     *     )
     *   ),
     *   tags={"Auth"},
     *   description="Login by email and password",
     *   operationId="login",
     *   @OA\Response(
     *     response=200,
     *     description="successful operation",
     *   ),
     * )
    */
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

    /**
     * @OA\Post(
     *   path="/register",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"name","email","password","password_confirmation"},
     *         @OA\Property(property="name", type="string", example=""),
     *         @OA\Property(property="email", type="string", example=""),
     *         @OA\Property(property="password", type="password", example=""),
     *         @OA\Property(property="password_confirmation", type="password", example=""),
     *       )
     *     )
     *   ),
     *   tags={"Auth"},
     *   description="Register",
     *   operationId="register",
     *   @OA\Response(
     *     response=200,
     *     description="successful operation",
     *   ),
     * )
    */
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

    /**
     * @OA\Post(
     *   path="/logout",
     *   security={{"bearer_token":{}}},
     *   tags={"Auth"},
     *   description="Logout authorized user",
     *   operationId="logout",
     *   @OA\Response(
     *     response=200,
     *     description="successful operation"
     *   ),
     * )
    */
    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return response([
            'success' => true,
            'message'=>'goodbye...'
        ]);
    }
}

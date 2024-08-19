<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use NextApps\VerificationCode\VerificationCode;

class UserAuthenticationController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $email = strtolower($request->input('email'));
        $password = $request->input('password');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password)
        ]);
        VerificationCode::send($email);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User Account Created Successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $email = strtolower($request->input('email'));
        $password = $request->input('password');

        $credentials = [
            'email' => $email,
            'password' => $password
        ];
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ],200);
    }

    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Succesfully Logged out'
        ], 200);
    }

    public function resendVerificationCode(): JsonResponse
    {
        VerificationCode::send(auth()->user()->email);

        return response()->json([
            'message'=>'Verification code sent successfully'
        ],200);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $code = $request->input('code');
        $verify=VerificationCode::verify($code,auth()->user()->email);

        if($verify){
            auth()->user()->markEmailAsVerified();
            return response()->json([
                'message'=>'Email verified successfully'
            ]);
        }else{
            return response()->json([
                'message'=>'Incorrect verification code'
            ]);
        }

    }

    public function testRoute(): JsonResponse
    {
        return response()->json([
            'message'=>'Just a test route'
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use NextApps\VerificationCode\VerificationCode;

class UserAuthenticationController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $name = $validated->input('name');
        $email = $validated->input('email');
        $password = $validated->input('password');

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

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $email = $validated->input('email');
        $password = $validated->input('password');

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

    public function redirectToGoogleAuth(): JsonResponse
    {
        return response()->json([
            'url' => Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    public function handleGoogleAuthCallback(): JsonResponse
    {
        try {
            $socialiteUser = Socialite::driver('google')->stateless()->user();
        } catch (ClientException $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        $user = User::query()
            ->firstOrCreate(
                [
                    'email' => $socialiteUser->email,
                ],
                [
                    'email_verified_at' => now(),
                    'name' => $socialiteUser->name,
                    'google_id' => $socialiteUser->id,
                ]
            );

        $token =$user->createToken('google-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);

    }

    public function testRoute(): JsonResponse
    {
        return response()->json([
            'message'=>'Just a test route'
        ]);
    }
}

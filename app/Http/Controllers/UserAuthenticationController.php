<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyPasswordCodeRequest;
use App\Mail\SendCodeResetPassword;
use App\Models\ResetCodePassword;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use NextApps\VerificationCode\VerificationCode;
use Request;

class UserAuthenticationController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $name = $request->input('name');
        $email = $request->input('email');
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

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
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

    public function loginAdmin(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
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

        if (!$user->isAdmin){
            return response()->json([
                'message' => 'Not allowed to access resources'
            ], 401);
        }

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

    public function verifyCode($code): JsonResponse
    {
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

    public function handleGoogleAuthCallback(Request $request): JsonResponse
    {
        try {
            $socialiteUser = Socialite::driver('google')->stateless()->user();
        } catch (ClientException $e) {
            return response()->json([
                'error' => 'Invalid credentials provided.',
                'ERROR'=>$e
            ], 422);
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

    public  function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Delete all old code that user send before.
        ResetCodePassword::where('email', $request->email)->delete();

        // Create a new code
        $codeData = ResetCodePassword::create([
            'code'=>mt_rand(100000, 999999),
            'email'=>$request->email,
        ]);

        // Send email to user
        Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));

        return response()->json([
            'message'=>'Password reset code sent to the user mail successfully'
        ]);
    }


    public function verifyPasswordCode(VerifyPasswordCodeRequest $request): JsonResponse
    {
        // find the code
        $passwordReset = ResetCodePassword::firstWhere('code', $request->code);

        // check if it does not expire: the time is one hour
        if ($passwordReset->created_at > now()->addHour()) {
            $passwordReset->delete();
            return response()->json([
                'message'=>'Password code already expired'
            ],422);
        }

        // find user's email
        $user = User::firstWhere('email', $passwordReset->email);

        // update user password
        $user->update($request->only('password'));

        // delete current code
        $passwordReset->delete();

        return response()->json([
            'message'=>'Password successfully reset',
        ]);
    }

    public function testRoute(): JsonResponse
    {
        return response()->json([
            'message'=>'Just a test route'
        ]);
    }
}

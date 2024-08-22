<?php

use App\Http\Controllers\UserAuthenticationController;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [UserAuthenticationController::class, 'login']);
    Route::post('register', [UserAuthenticationController::class, 'register']);
    Route::get('send-test-mail', [MailController::class, 'index']);
    Route::get('/google-auth',[UserAuthenticationController::class,'redirectToGoogleAuth']);
    Route::get('/google-auth-callback',[UserAuthenticationController::class, 'handleGoogleAuthCallback']);
});

//These routes are protected using middleware
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('resend-verification-code',[UserAuthenticationController::class,'resendVerificationCode']);
    Route::post('verify-code',[UserAuthenticationController::class,'verifyCode']);
    Route::post('logout', [UserAuthenticationController::class, 'logout']);
});

//Only for verified users
Route::prefix('v1')->middleware(['auth:sanctum','verified'])->group(function () {
    Route::get('test-route',[UserAuthenticationController::class,'testRoute']);
});

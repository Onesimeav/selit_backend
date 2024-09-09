<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserAuthenticationController;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [UserAuthenticationController::class, 'login']);
    Route::post('register', [UserAuthenticationController::class, 'register']);
    Route::get('send-test-mail', [MailController::class, 'index']);
    Route::get('/google-auth',[UserAuthenticationController::class,'redirectToGoogleAuth']);
    Route::get('/google-auth-callback',[UserAuthenticationController::class, 'handleGoogleAuthCallback']);
    Route::post('/forgot-password',[UserAuthenticationController::class,'forgotPassword']);
    Route::post('/reset-password',[UserAuthenticationController::class,'verifyPasswordCode']);
});

//only logged-in users
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('resend-verification-code',[UserAuthenticationController::class,'resendVerificationCode']);
    Route::post('verify-code',[UserAuthenticationController::class,'verifyCode']);
    Route::post('logout', [UserAuthenticationController::class, 'logout']);
});

//Only for verified users
Route::prefix('v1')->middleware(['auth:sanctum','verified'])->group(function () {

    Route::get('test-route',[UserAuthenticationController::class,'testRoute']);
    //template
    Route::post('create-template',[TemplateController::class,'createTemplate']);
    Route::post('search-template',[TemplateController::class,'searchTemplate']);
    Route::post('update-template',[TemplateController::class,'updateTemplate']);
    Route::post('delete-template',[TemplateController::class,'deleteTemplate']);
    //shop
    Route::post('create-shop',[ShopController::class,'createShop']);
    Route::post('choose-shop-template',[ShopController::class,'chooseTemplate']);
    Route::post('add-product-to-shop',[ProductController::class,'addToShop']);
    //product
    Route::post('create-product',[ProductController::class,'createProduct']);
    Route::post('search-product',[ProductController::class,'searchProduct']);
    Route::post('update-product',[ProductController::class,'updateProduct']);
    Route::post('delete-product',[ProductController::class,'deleteProduct']);
});

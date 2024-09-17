<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
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
    Route::get('verify-code/{id}',[UserAuthenticationController::class,'verifyCode']);
    Route::post('logout', [UserAuthenticationController::class, 'logout']);
});

//Only for verified users
Route::prefix('v1')->middleware(['auth:sanctum','verified'])->group(function () {

    Route::get('test-route',[UserAuthenticationController::class,'testRoute']);
    //template
    Route::post('/templates',[TemplateController::class,'createTemplate']);
    Route::get('/templates',[TemplateController::class,'searchTemplate']);
    Route::put('/templates/{id}',[TemplateController::class,'updateTemplate']);
    Route::delete('/templates/{id}',[TemplateController::class,'deleteTemplate']);
    //shop
    Route::post('/shops',[ShopController::class,'createShop']);
    Route::post('/shops/choose-shop-template',[ShopController::class,'chooseTemplate']);
    Route::post('/shops/add-product-to-shop',[ProductController::class,'addToShop']);
    Route::get('/shops/publish-shop/{id}',[ShopController::class, 'publishShop']);
    //product
    Route::post('/products',[ProductController::class,'createProduct']);
    Route::get('/products',[ProductController::class,'searchProduct']);
    Route::put('/products/{id}',[ProductController::class,'updateProduct']);
    Route::delete('/products/{id}',[ProductController::class,'deleteProduct']);
    //category
    Route::post('/category',[CategoryController::class,'createCategory']);
    Route::get('/category',[CategoryController::class,'searchCategory']);
    Route::put('/category/{id}',[CategoryController::class,'updateCategory']);
    Route::delete('/category/{id}',[CategoryController::class,'deleteCategory']);
    Route::get('/category/get-products/{id}',[CategoryController::class,'getCategoryProducts']);
    Route::post('/category/add-products',[CategoryController::class,'addProductsToCategory']);
    Route::post('/category/remove-products',[CategoryController::class,'removeProductsFromCategory']);
    //promotion
    Route::post('/promotions',[PromotionController::class,'createPromotion']);
    Route::get('/promotions',[PromotionController::class,'searchPromotion']);
    Route::put('/promotions/{id}',[PromotionController::class,'updatePromotion']);
    Route::delete('/promotions/{id}',[PromotionController::class,'deletePromotion']);
    Route::post('/promotions/add-products',[PromotionController::class,'addProductsToPromotion']);
    Route::post('/promotions/remove-products',[PromotionController::class,'removeProductFromPromotion']);
    Route::get('/promotions/verify-code/{code}',[PromotionController::class,'verifyPromoCode']);
});

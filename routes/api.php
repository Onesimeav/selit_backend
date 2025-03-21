<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SubdomainController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserAuthenticationController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [UserAuthenticationController::class, 'login']);
Route::get('/admin-login', [UserAuthenticationController::class, 'loginAdmin']);
Route::post('register', [UserAuthenticationController::class, 'register']);
Route::get('send-test-mail', [MailController::class, 'index']);
Route::get('/google-auth', [UserAuthenticationController::class, 'redirectToGoogleAuth']);
Route::post('/google-auth-callback', [UserAuthenticationController::class, 'handleGoogleAuthCallback']);
Route::post('/forgot-password', [UserAuthenticationController::class, 'forgotPassword']);
Route::post('/reset-password', [UserAuthenticationController::class, 'verifyPasswordCode']);
//orders
Route::post('/orders', [OrderController::class, 'createOrder']);
Route::get('orders/get-orders',[OrderController::class, 'getOrder']);
Route::get('/orders/get-delivery-info',[OrderController::class,'getDeliveryDetails']);
Route::get('/orders/get-deliveryman-info',[OrderController::class,'getDeliverymanInfo']);
Route::put('/orders/finish-order', [OrderController::class, 'setOrderStateAsFinished']);
Route::put('/orders/cancel-order', [OrderController::class, 'cancelOrder']);
Route::put('orders/delivered-order/{orderReference}', [OrderController::class, 'setOrderStateAsDelivered']);
//promotion
Route::get('/promotions/verify-code',[PromotionController::class,'verifyPromoCode']);
Route::get('/promotions/{id}',[PromotionController::class,'getPromotion']);
//Subdomain
Route::get('/subdomain/{domain}', [SubdomainController::class, 'getShop']);
Route::get('/subdomain/{domain}/get-products', [SubdomainController::class, 'getShopProducts']);
Route::get('/subdomain/{domain}/get-categories', [SubdomainController::class, 'getShopCategories']);
Route::get('/subdomain/{domain}/category/{id}/products', [SubdomainController::class, 'getShopCategoryProducts']);
//products
Route::get('/products/get-product/{id}',[ProductController::class,'getProduct']);
Route::get('/products/search-product',[ProductController::class,'searchProductFromShop']);

//only logged-in users
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',[UserController::class,'getUser']);
    Route::get('resend-verification-code',[UserAuthenticationController::class,'resendVerificationCode']);
    Route::get('verify-code/{code}',[UserAuthenticationController::class,'verifyCode']);
    Route::get('logout', [UserAuthenticationController::class, 'logout']);
});

//Only for verified users
Route::middleware(['auth:sanctum','verified'])->group(function () {
    //users
    Route::get('/users/get-balance',[UserController::class,'getBalance']);
    Route::post('/users/withdraw-request',[UserController::class,'makeWithdrawal']);
    //shop
    Route::post('/shops/get-shops',[ShopController::class,'getUserShops']);
    Route::post('/shops',[ShopController::class,'createShop']);
    Route::post('/shops/choose-shop-template',[ShopController::class,'chooseTemplate']);
    Route::post('/shops/add-product-to-shop',[ProductController::class,'addToShop']);
    Route::get('/shops/publish-shop/{id}',[ShopController::class, 'publishShop']);
    Route::put('/shops/change-main-color',[ShopController::class,'changeShopMainColor']);
    //product
    Route::post('/products',[ProductController::class,'createProduct']);
    Route::get('/products',[ProductController::class,'searchProduct']);
    Route::put('/products/{id}',[ProductController::class,'updateProduct']);
    Route::delete('/products',[ProductController::class,'deleteProduct']);
    Route::post('/products/add-media/',[ProductController::class,'addMediaToProduct']);
    Route::get('/products/delete-media/',[ProductController::class,'deleteMediaFromProduct']);
    Route::get('/products/update-specification/',[ProductController::class,'updateProductSpecifications']);
    Route::post('/products/add-specification/',[ProductController::class,'addSpecificationsToProduct']);
    Route::get('/products/delete-specification/',[ProductController::class,'deleteProductSpecifications']);
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
    Route::get('/promotions/get-products/{id}',[PromotionController::class,'getPromotionProducts']);
    Route::post('/promotions/add-products',[PromotionController::class,'addProductsToPromotion']);
    Route::post('/promotions/remove-products',[PromotionController::class,'removeProductFromPromotion']);
    //order
    Route::get('/orders',[OrderController::class,'getOrders']);
    Route::put('/orders/approve-order/{orderId}',[OrderController::class,'setOrderStateAsApproved']);
    Route::put('/orders/delivery-order',[OrderController::class,'setOrderStateAsDelivery']);
    Route::get('/orders/get-invoice/{orderReference}',[OrderController::class,'getOrderInvoice']);
    //stats
    Route::get('/stats/total-orders',[StatsController::class,'getTotalOrders']);
    Route::get('/stats/annual-revenue-rate',[StatsController::class,'getTotalOrderPrices']);
    Route::get('/stats/total-order-evolution',[StatsController::class,'getOrderEvolution']);
    Route::get('/stats/total-revenue-evolution',[StatsController::class,'getOrderPriceEvolution']);
});
//only admins
Route::middleware(['auth:sanctum','admin'])->group(function () {
    //template
    Route::post('/templates',[TemplateController::class,'createTemplate']);
    Route::get('/templates',[TemplateController::class,'searchTemplate']);
    Route::put('/templates/{id}',[TemplateController::class,'updateTemplate']);
    Route::delete('/templates/{id}',[TemplateController::class,'deleteTemplate']);
    Route::get('/users/get-withdraw-request',[UserController::class,'getWithdrawalRequests']);
    Route::put('/users/validate-withdraw/{id}',[UserController::class,'validateWithdrawal']);
});



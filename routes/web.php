<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\SubdomainController;
use Illuminate\Support\Facades\Route;

//shops redirection
Route::group(['domain' => '{subdomain}.selit.store'], function () {
    Route::get('/', [SubdomainController::class,'index']);
});

Route::get('/delivery/{orderReference}',[OrderController::class,'getDeliveryDetails']);


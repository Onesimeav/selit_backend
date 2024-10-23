<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;


Route::get('order/delivery/{orderReference}',[OrderController::class,'getDeliveryDetails']);


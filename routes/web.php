<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

//shops redirection
Route::group(['domain' => '{subdomain}.example.com'], function () {
    Route::get('/', 'SubdomainController@index');
});


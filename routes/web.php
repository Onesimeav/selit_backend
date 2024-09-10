<?php

use Illuminate\Support\Facades\Route;

//shops redirection
Route::group(['domain' => '{subdomain}.selit.store'], function () {
    Route::get('/', 'SubdomainController@index');
});


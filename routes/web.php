<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment-success', function () {
    return redirect()->to('myapp://payment-success');
});

Route::get('/payment-failed', function () {
    return redirect()->to('myapp://payment-failed');
});

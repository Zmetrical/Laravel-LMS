<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View;


Route::get('/', function () {
    return view('dashboard');
});

Route::get('/user_management', function () {
    return view('user_management/user_management');
});


Route::get('/welcome', function () {
    return view('welcome');
});








// sample ui
Route::get('/calendar', function () {
    return view('calendar');
});

Route::get('/feedback', function () {
    return view('feedback');
});

Route::get('/container', function () {
    return view('container');
});

Route::get('/form', function () {
    return view('form');
});
Route::get('/table', function () {
    return view('table');
});
Route::get('/test', function () {
    return view('test');
});


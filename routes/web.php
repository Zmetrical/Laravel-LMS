<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View;


Route::get('/welcome', function () {
    return view('welcome');
});


Route::get('/', function () {
    return view('main');
});


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


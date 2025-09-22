<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View;
use App\Http\Controllers\User_Management\User_Management;


Route::get('/', [User_Management::class, 'index']);




Route::get('/login', function () {
    return view('auth/login');
});

Route::get('/register', function () {
    return view('user_management/register');
});

Route::post('/user_create', [User_Management::class, 'store'])->name('userlist.create');




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


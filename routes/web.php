<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View;
use App\Http\Controllers\User_Management\User_Management;


Route::get('/', [User_Management::class, 'index']);




Route::get('/login', function () {
    return view('auth/login');
});



// ---------------------------------------------------------------------------
//  User Management
// ---------------------------------------------------------------------------
Route::prefix('user_management')->group(function () {
    Route::get('/register', function () {
        return view(view: 'user_management.register');
    })->name('user.register');

    Route::post('/create_student', [User_Management::class, 'store'])->name('user.create_student');

    Route::get('/list', function () {
        return view('user_management.list');
    })->name('user.list');

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


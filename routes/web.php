<?php

use App\Http\Controllers\User_Management\Profile_Management;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View;
use App\Http\Controllers\User_Management\User_Management;
use App\Http\Controllers\Admin;


Route::get('/', [User_Management::class, 'index']);

Route::get('/login', function () {
    return view('auth/login');
});



// ---------------------------------------------------------------------------
//  User Management
// ---------------------------------------------------------------------------
// Route::prefix('user_management')->group(function () {
//     Route::get('/register', function () {
//         return view(view: 'user_management.register');
//     })->name('user.register');

//     Route::post('/create_student', [User_Management::class, 'store'])->name('user.create_student');

//     Route::get('/list', function () {
//         return view('user_management.list');
//     })->name('user.list');
// });


// ---------------------------------------------------------------------------
//  Admin Page
// ---------------------------------------------------------------------------

Route::get('/', [Admin::class, 'index'])
    ->name('admin.home');


// ---------------------------------------------------------------------------
//  User Management - Admin 
// ---------------------------------------------------------------------------

// === Student ===
Route::get('/user_management/create_student', action: [User_Management::class, 'create_student'])
    ->name('admin.create_student');


Route::get('/procedure/get_sections', [User_Management::class, 'get_Sections']);

Route::post('/procedure/insert_Student', [User_Management::class, 'insert_Student'])
    ->name('procedure.insert_Student');

Route::post('/procedure/insert_Students', [User_Management::class, 'insert_Students'])
    ->name('procedure.insert_Students');

Route::get('/user_management/list_student', [User_Management::class, 'list_Students'])
    ->name('admin.list_student');


// === Teacher ===
Route::get('/user_management/create_teacher', [User_Management::class, 'create_teacher'])
    ->name('admin.create_teacher');

Route::post('/user_management/insert_teacher', [User_Management::class, 'insert_teacher'])
    ->name('procedure.insert_teacher');

Route::get('/user_management/list_teacher', [User_Management::class, 'list_teacher'])
    ->name('admin.list_teacher');


// ---------------------------------------------------------------------------
//  Profile 
// ---------------------------------------------------------------------------

// === STUDENT ===
Route::get('/profile/student/{id}/edit', [Profile_Management::class, 'edit_student'])
    ->name('profile.student.edit');

Route::get('/profile/student/{id}', [Profile_Management::class, 'show_student'])
    ->name('profile.student.show');

// ===
Route::post('/profile/student/{id}/update', [Profile_Management::class, 'update_student']); 


// === TEACHER ===
Route::get('/profile/teacher/{id}/edit', [Profile_Management::class, 'edit_teacher'])
    ->name('profile.teacher.edit');

Route::get('/profile/teacher/{id}', [Profile_Management::class, 'show_teacher'])
    ->name('profile.teacher.show');

Route::post('/profile/teacher/{id}/update', [Profile_Management::class, 'update_teacher']); 




// ---------------------------------------------------------------------------
//  Sample UI
// ---------------------------------------------------------------------------
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


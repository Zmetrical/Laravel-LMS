<?php

use App\Http\Controllers\Enrollment_Management\Enroll_Management;
use App\Http\Controllers\User_Management\Profile_Management;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View;
use App\Http\Controllers\User_Management\User_Management;
use App\Http\Controllers\Class_Management\Class_Management;
use App\Http\Controllers\Class_Management\Class_List;
use App\Http\Controllers\StudentController;

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\Data_Controller;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\Auth\Login_Controller;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\DB;

Route::get('/', [DeveloperController::class, 'index']);


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

Route::prefix('admin')->group(function () {
    Route::get('/', [Admin::class, 'index'])
        ->name('admin.home');

    Route::get('/login', [Admin::class, 'login'])
        ->name('admin.login');
});

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
//  Enrollment Management 
// ---------------------------------------------------------------------------

Route::get('/enrollment_management/enroll_class', [Enroll_Management::class, 'enroll_class'])
    ->name('admin.enroll_class');

// === Section ===


Route::prefix('enrollment_management')->group(function () {

    Route::get('/enroll_section', [Enroll_Management::class, 'enroll_section'])
        ->name('admin.enroll_section');

    Route::get('/sections/data', [Enroll_Management::class, 'getSectionsData'])
        ->name('admin.sections.data');

    Route::get('/sections/{id}/details', [Enroll_Management::class, 'getDetails'])
        ->name('admin.sections.details');


    Route::get('/section-class-enrollment', [Enroll_Management::class, 'sectionClassEnrollment'])
        ->name('admin.section_class_enrollment');

    Route::get('/sections/{id}/classes', [Enroll_Management::class, 'getSectionClasses'])
        ->name('admin.sections.classes');

    Route::post('/sections/{id}/enroll-class', [Enroll_Management::class, 'enrollClass'])
        ->name('admin.sections.enrollClass');

    Route::delete('/sections/{sectionId}/remove-class/{classId}', [Enroll_Management::class, 'removeClass'])
        ->name('admin.sections.removeClass');

    Route::get('/available-classes/{sectionId}', [Enroll_Management::class, 'getAvailableClasses'])
        ->name('admin.sections.availableClasses');



    Route::get('/enroll_student', [Enroll_Management::class, 'enroll_student'])
        ->name('admin.enroll_student');


       // Irregular Students Management
    Route::get('/irregular-students', [Enroll_Management::class, 'enroll_student'])
        ->name('admin.irregular.students');
    
    Route::get('/irregular-students/data', [Enroll_Management::class, 'getStudentsData'])
        ->name('admin.irregular.students.data');
    
    Route::get('/irregular-students/{id}/classes', [Enroll_Management::class, 'getStudentClasses'])
        ->name('admin.irregular.student.classes');
    
    Route::post('/irregular-students/enroll', [Enroll_Management::class, 'enrollStudentClass'])
        ->name('admin.irregular.enroll.class');
    
    Route::post('/irregular-students/unenroll', [Enroll_Management::class, 'removeStudentClass'])
        ->name('admin.irregular.unenroll.class');

    // Helper routes for filters (if not already defined)
    Route::get('/levels/data', function() {
        return response()->json([
            'success' => true,
            'data' => DB::table('levels')->get()
        ]);
    })->name('admin.levels.data');

    Route::get('/strands/data', function() {
        return response()->json([
            'success' => true,
            'data' => DB::table('strands')->where('status', 1)->get()
        ]);
    })->name('admin.strands.data');
});



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

// === ADMIN ===
Route::get('admin/data/{id}', [Data_Controller::class, 'student_data'])
    ->name('data.student');

// ---------------------------------------------------------------------------
//  Class Management - Admin
// ---------------------------------------------------------------------------

Route::post('/class_management/insert_class', [Class_Management::class, 'insert_class'])
    ->name('admin.insert_class');

Route::get('/class_management/list_class', [Class_Management::class, 'list_class'])
    ->name('admin.list_class');

Route::get('/class_management/list_strand', [Class_Management::class, 'list_strand'])
    ->name('admin.list_strand');

Route::get('/class_management/list_section', [Class_Management::class, 'list_section'])
    ->name('admin.list_section');

Route::get('/class_management/list_schoolyear', [Class_Management::class, 'list_schoolyear'])
    ->name('admin.list_schoolyear');


// ---------------------------------------------------------------------------
//  Student 
// ---------------------------------------------------------------------------

// Student Routes
Route::prefix('student')->group(function () {
    // Show login page (GET)
    Route::get('/login', [StudentController::class, 'login'])
        ->name('student.login');
    // Guest routes (not authenticated)
    Route::middleware('guest:student')->group(function () {


        // Handle authentication (POST only)
        Route::post('/auth', [Login_Controller::class, 'auth_student'])
            ->name('student.auth');
    });

    // Protected routes (authenticated)
    Route::middleware('auth:student')->group(function () {
        // Student dashboard
        Route::get('/', [StudentController::class, 'index'])
            ->name('student.home');

        // Logout
        Route::post('/logout', [Login_Controller::class, 'logout_student'])
            ->name('student.logout');

        Route::get('/class', [Class_List::class, 'student_class_list'])
            ->name('student.list_class');
    });
});





// ---------------------------------------------------------------------------
//  Teacher 
// ---------------------------------------------------------------------------

Route::get('/teacher', [TeacherController::class, 'index'])
    ->name('teacher.home');


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

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
use App\Http\Controllers\Class_Management\Page_Lesson;
use App\Http\Controllers\Class_Management\Page_Quiz;
use App\Http\Controllers\Class_Management\Page_Grade;
use App\Http\Controllers\Class_Management\Page_Participant;
use App\Http\Controllers\Class_Management\Page_Lecture;
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


    // ---------------------------------------------------------------------------
    //  Section
    // ---------------------------------------------------------------------------

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



    // ---------------------------------------------------------------------------
    //  Student
    // ---------------------------------------------------------------------------


    // Students List
    Route::get('/enroll_student', [Enroll_Management::class, 'enroll_student'])
        ->name('admin.enroll_student');

    Route::get('/enroll_student/data', [Enroll_Management::class, 'getStudentsData'])
        ->name('admin.students.data');

    // Student Enrollment Page
    Route::get('/students/{id}/enrollment', [Enroll_Management::class, 'studentClassEnrollment'])
        ->name('admin.student_class_enrollment');

    Route::get('/students/{id}/info', [Enroll_Management::class, 'getStudentInfo'])
        ->name('admin.student.info');

    Route::get('/students/{id}/classes', [Enroll_Management::class, 'getStudentClasses'])
        ->name('admin.student.classes');

    Route::post('/students/enroll', [Enroll_Management::class, 'enrollStudentClass'])
        ->name('admin.enroll.class');

    Route::post('/students/unenroll', [Enroll_Management::class, 'removeStudentClass'])
        ->name('admin.unenroll.class');

    // Helper routes for filters (if not already defined)
    Route::get('/levels/data', function () {
        return response()->json([
            'success' => true,
            'data' => DB::table('levels')->get()
        ]);
    })->name('levels.data');

    Route::get('/strands/data', function () {
        return response()->json([
            'success' => true,
            'data' => DB::table('strands')->where('status', 1)->get()
        ]);
    })->name('strands.data');


    // ---------------------------------------------------------------------------
    //  Teacher
    // ---------------------------------------------------------------------------
    // Class Students View
    Route::get('/class-students', [Enroll_Management::class, 'classes_enrollment'])
        ->name('admin.classes.students.index');

    Route::get('/classes/list', [Enroll_Management::class, 'getClassesList'])
        ->name('admin.classes.list');

    Route::get('/classes/{id}/details', [Enroll_Management::class, 'getClassDetails'])
        ->name('admin.classes.details');

    Route::get('/classes/{id}/students', [Enroll_Management::class, 'getClassStudents'])
        ->name('admin.classes.students');

    // Teacher Management
    Route::get('/teachers/list', [Enroll_Management::class, 'getTeachersList'])
        ->name('admin.teachers.list');

    Route::post('/classes/assign-teacher', [Enroll_Management::class, 'assignTeacher'])
        ->name('admin.classes.assign-teacher');

    Route::post('/classes/remove-teacher', [Enroll_Management::class, 'removeTeacher'])
        ->name('admin.classes.remove-teacher');
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
//  Grade Management
// ---------------------------------------------------------------------------

// Grade Management Routes
Route::prefix('class/{classId}')->group(function () {
    // Get all grades for a class (Teacher)
    Route::get('/grades', [Page_Grade::class, 'getGrades']);
    
    // Get students in a class
    Route::get('/students', [Page_Grade::class, 'getStudents']);
    
    // Get quizzes in a class
    Route::get('/quizzes', [Page_Grade::class, 'getQuizzes']);
    
    // Get specific student grades (Student)
    Route::get('/student/{studentNumber}/grades', [Page_Grade::class, 'getStudentGrades']);
});


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

        // Class Management
        Route::get('/class', [Class_List::class, 'student_class_list'])
            ->name('student.list_class');

        // API Routes for Classes
        Route::get('/classes/list', [Class_List::class, 'getStudentClasses'])
            ->name('student.classes.list');

        Route::get('/classes/{id}/details', [Class_List::class, 'getClassDetails'])
            ->name('student.classes.details');


        // Class Lessons Routes
        Route::prefix('class/{classId}')->group(function () {
            // View Pages
            Route::get('/lessons', [Page_Lesson::class, 'studentIndex'])->name('student.class.lessons');


            Route::prefix('lesson/{lessonId}')->group(function () {
                // View lecture page
                Route::get('lecture/{lectureId}', [Page_Lecture::class, 'view'])
                    ->name('student.class.lectures.view');

                // Get lecture data (AJAX)
                Route::get('lecture/{lectureId}/data', [Page_Lecture::class, 'getData'])
                    ->name('student.class.lectures.view.data');

                // Download file
                Route::get('/{lectureId}/download/{filename}', [Page_Lecture::class, 'download'])
                    ->name('download');

                // Stream file
                Route::get('/{lectureId}/stream/{filename}', [Page_Lecture::class, 'stream'])
                    ->name('stream');
            });

            Route::get('/quizzes', function ($classId) {
                $class = DB::table('classes')->where('id', $classId)->first();
                return view('modules.class.page_quiz', ['userType' => 'student', 'class' => $class]);
            })->name('student.class.quizzes');

            Route::get('/grades', [Page_Grade::class, 'studentIndex'])
                ->name('student.class.grades');

            // API Routes for Lessons
            Route::get('/lessons/list', [Page_Lesson::class, 'studentList'])->name('student.class.lessons.list');


            // View quiz details and attempts
            Route::get('/lesson/{lessonId}/quiz/{quizId}', [Page_Quiz::class, 'studentViewQuiz'])
                ->name('student.class.quiz.view');

            // Start quiz attempt
            Route::get('/lesson/{lessonId}/quiz/{quizId}/start', [Page_Quiz::class, 'studentStartQuiz'])
                ->name('student.class.quiz.start');

            // Submit quiz attempt
            Route::post('/lesson/{lessonId}/quiz/{quizId}/submit', [Page_Quiz::class, 'studentSubmitQuiz'])
                ->name('student.class.quiz.submit');

            // View attempt results
            Route::get('/lesson/{lessonId}/quiz/{quizId}/results/{attemptId}', [Page_Quiz::class, 'studentGetResults'])
                ->name('student.class.quiz.results');
        });
    });
});





// ---------------------------------------------------------------------------
//  Teacher 
// ---------------------------------------------------------------------------


// Teacher routes
Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/login', [TeacherController::class, 'login'])->name('login');
    Route::post('/auth', [Login_Controller::class, 'auth_teacher'])->name('auth');

    Route::middleware(['auth:teacher'])->group(function () {
        Route::get('/home', [TeacherController::class, 'index'])->name('home');
        Route::post('/logout', [Login_Controller::class, 'logout_teacher'])->name('logout');

        // Class Management
        Route::get('/list_class', [Class_List::class, 'teacher_class_list'])->name('list_class');

        // Class API endpoints
        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('/list', [Class_List::class, 'getTeacherClasses'])->name('list');
        });

        // Class Lessons Routes
        Route::prefix('class/{classId}')->group(function () {
            // View Pages
            Route::get('/lessons', [Page_Lesson::class, 'teacherIndex'])->name('class.lessons');
            Route::get('/quizzes', [Page_Quiz::class, 'teacherIndex'])->name('class.quizzes');

            Route::get('/grades', [Page_Grade::class, 'teacherIndex'])
                ->name('class.grades');
            Route::get('/participants', [Page_Participant::class, 'teacherIndex'])->name('class.participants');

            // API Routes for Lessons
            Route::get('/lessons/list', [Page_Lesson::class, 'teacherList'])->name('class.lessons.list');
            Route::post('/lessons', [Page_Lesson::class, 'store'])->name('class.lessons.store');
            Route::put('/lessons/{lessonId}', [Page_Lesson::class, 'update'])->name('class.lessons.update');
            Route::delete('/lessons/{lessonId}', [Page_Lesson::class, 'destroy'])->name('class.lessons.delete');
        

        });

        // Lecture Management Routes - CORRECTED
        Route::prefix('class/{classId}/lesson/{lessonId}/lecture')->name('class.lectures.')->group(function () {
            // Create
            Route::get('/create', [Page_Lecture::class, 'create'])->name('create');
            Route::post('/', [Page_Lecture::class, 'store'])->name('store');

            // Edit
            Route::get('/{lectureId}/edit', [Page_Lecture::class, 'edit'])->name('edit');
            Route::put('/{lectureId}', [Page_Lecture::class, 'update'])->name('update');

            // Delete (soft delete) - MUST use DELETE method
            Route::delete('/{lectureId}', [Page_Lecture::class, 'destroy'])->name('destroy');

            // Download/Stream files
            Route::get('/{lectureId}/download/{filename}', [Page_Lecture::class, 'download'])->name('download');
            Route::get('/{lectureId}/stream/{filename}', [Page_Lecture::class, 'stream'])->name('stream');
        });

        // Quiz routes for teachers
        Route::prefix('teacher/class/{classId}/lesson/{lessonId}/quiz')->name('class.quiz.')->group(function () {
            Route::get('/create', [Page_Quiz::class, 'teacherCreate'])->name('create');
            Route::post('/store', [Page_Quiz::class, 'store'])->name('store');
            Route::get('/{quizId}/edit', [Page_Quiz::class, 'teacherEdit'])->name('edit');
            Route::get('/{quizId}/data', [Page_Quiz::class, 'getQuizData'])->name('data');
            Route::put('/{quizId}/update', [Page_Quiz::class, 'update'])->name('update');
            Route::delete('/{quizId}/delete', [Page_Quiz::class, 'destroy'])->name('delete');
        });
    });
});



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

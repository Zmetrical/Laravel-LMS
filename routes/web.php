<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\Data_Controller;
use App\Http\Controllers\Auth\Login_Controller;
use App\Http\Controllers\Class_Management\Class_List;
use App\Http\Controllers\Class_Management\Class_Management;
use App\Http\Controllers\Class_Management\Page_Grade;
use App\Http\Controllers\Class_Management\Page_Lecture;
use App\Http\Controllers\Class_Management\Page_Lesson;
use App\Http\Controllers\Class_Management\Page_Participant;
use App\Http\Controllers\Class_Management\Page_Quiz;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\Enrollment_Management\Enroll_Management;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\User_Management\Profile_Management;
use App\Http\Controllers\User_Management\User_Management;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ===========================================================================
// PUBLIC ROUTES
// ===========================================================================

Route::get('/', [DeveloperController::class, 'index']);
Route::get('/login', fn() => view('auth.login'));

// Development/Testing Routes
Route::get('/form', fn() => view('form'));
Route::get('/table', fn() => view('table'));
Route::get('/test', fn() => view('test'));

// ===========================================================================
// ADMIN ROUTES
// ===========================================================================

Route::prefix('admin')->name('admin.')->group(function () {
    // Auth Routes
    Route::get('/login', [Admin::class, 'login'])->name('login');
    Route::get('/', [Admin::class, 'index'])->name('home');

    // ---------------------------------------------------------------------------
    // USER MANAGEMENT
    // ---------------------------------------------------------------------------
    
    // Student Management
    Route::prefix('user_management')->group(function () {
        // Students
        Route::get('/create_student', [User_Management::class, 'create_student'])->name('create_student');
        Route::get('/list_student', [User_Management::class, 'list_Students'])->name('list_student');
        Route::post('/insert_student', [User_Management::class, 'insert_Student'])->name('insert_Student');
        Route::post('/insert_students', [User_Management::class, 'insert_Students'])->name('insert_Students');
        
        // Teachers
        Route::get('/create_teacher', [User_Management::class, 'create_teacher'])->name('create_teacher');
        Route::get('/list_teacher', [User_Management::class, 'list_teacher'])->name('list_teacher');
        Route::post('/insert_teacher', [User_Management::class, 'insert_teacher'])->name('procedure.insert_teacher');
    });

    // Helper Routes
    Route::get('/procedure/get_sections', [User_Management::class, 'get_Sections']);

    // ---------------------------------------------------------------------------
    // ENROLLMENT MANAGEMENT
    // ---------------------------------------------------------------------------
    
    Route::prefix('enrollment_management')->group(function () {
        // Section Enrollment
        Route::get('/enroll_class', [Enroll_Management::class, 'enroll_class'])->name('enroll_class');
        Route::get('/section-class-enrollment', [Enroll_Management::class, 'sectionClassEnrollment'])->name('section_class_enrollment');
        
        // Section API Routes
        Route::prefix('sections')->group(function () {
            Route::get('/data', [Enroll_Management::class, 'getSectionsData'])->name('sections.data');
            Route::get('/{id}/details', [Enroll_Management::class, 'getDetails'])->name('sections.details');
            Route::get('/{id}/classes', [Enroll_Management::class, 'getSectionClasses'])->name('sections.classes');
            Route::post('/{id}/enroll-class', [Enroll_Management::class, 'enrollClass'])->name('sections.enrollClass');
            Route::delete('/{sectionId}/remove-class/{classId}', [Enroll_Management::class, 'removeClass'])->name('sections.removeClass');
        });
        
        Route::get('/available-classes/{sectionId}', [Enroll_Management::class, 'getAvailableClasses'])->name('sections.availableClasses');

        // Student Enrollment
        Route::get('/enroll_student', [Enroll_Management::class, 'enroll_student'])->name('enroll_student');
        Route::get('/enroll_student/data', [Enroll_Management::class, 'getStudentsData'])->name('students.data');
        
        // Student Class Enrollment
        Route::prefix('students')->group(function () {
            Route::get('/{id}/enrollment', [Enroll_Management::class, 'studentClassEnrollment'])->name('student_class_enrollment');
            Route::get('/{id}/info', [Enroll_Management::class, 'getStudentInfo'])->name('student.info');
            Route::get('/{id}/classes', [Enroll_Management::class, 'getStudentClasses'])->name('student.classes');
            Route::post('/enroll', [Enroll_Management::class, 'enrollStudentClass'])->name('enroll.class');
            Route::post('/unenroll', [Enroll_Management::class, 'removeStudentClass'])->name('unenroll.class');
        });

        // Class Students & Teacher Assignment
        Route::get('/class-students', [Enroll_Management::class, 'classes_enrollment'])->name('classes.students.index');
        
        Route::prefix('classes')->group(function () {
            Route::get('/list', [Enroll_Management::class, 'getClassesList'])->name('classes.list');
            Route::get('/{id}/details', [Enroll_Management::class, 'getClassDetails'])->name('classes.details');
            Route::get('/{id}/students', [Enroll_Management::class, 'getClassStudents'])->name('classes.students');
            Route::post('/assign-teacher', [Enroll_Management::class, 'assignTeacher'])->name('classes.assign-teacher');
            Route::post('/remove-teacher', [Enroll_Management::class, 'removeTeacher'])->name('classes.remove-teacher');
        });

        Route::get('/teachers/list', [Enroll_Management::class, 'getTeachersList'])->name('teachers.list');

        // Helper Routes
        Route::get('/levels/data', fn() => response()->json([
            'success' => true,
            'data' => DB::table('levels')->get()
        ]))->name('levels.data');

        Route::get('/strands/data', fn() => response()->json([
            'success' => true,
            'data' => DB::table('strands')->where('status', 1)->get()
        ]))->name('strands.data');
    });

    // ---------------------------------------------------------------------------
    // CLASS MANAGEMENT
    // ---------------------------------------------------------------------------
    
    Route::prefix('class_management')->group(function () {
        Route::post('/insert_class', [Class_Management::class, 'insert_class'])->name('insert_class');
        Route::get('/list_class', [Class_Management::class, 'list_class'])->name('list_class');
        Route::get('/list_strand', [Class_Management::class, 'list_strand'])->name('list_strand');
        Route::get('/list_section', [Class_Management::class, 'list_section'])->name('list_section');
        Route::get('/list_schoolyear', [Class_Management::class, 'list_schoolyear'])->name('list_schoolyear');
    });
});

// ===========================================================================
// PROFILE MANAGEMENT (SHARED)
// ===========================================================================

Route::prefix('profile')->name('profile.')->group(function () {
    // Student Profiles
    Route::prefix('student/{id}')->group(function () {
        Route::get('/', [Profile_Management::class, 'show_student'])->name('student.show');
        Route::get('/edit', [Profile_Management::class, 'edit_student'])->name('student.edit');
        Route::post('/update', [Profile_Management::class, 'update_student']);
    });

    // Teacher Profiles
    Route::prefix('teacher/{id}')->group(function () {
        Route::get('/', [Profile_Management::class, 'show_teacher'])->name('teacher.show');
        Route::get('/edit', [Profile_Management::class, 'edit_teacher'])->name('teacher.edit');
        Route::post('/update', [Profile_Management::class, 'update_teacher']);
    });
});

// Admin Data Routes
Route::get('admin/data/{id}', [Data_Controller::class, 'student_data'])->name('data.student');

// ===========================================================================
// GRADE MANAGEMENT (SHARED)
// ===========================================================================

Route::prefix('class/{classId}')->group(function () {
    Route::get('/get_grades', [Page_Grade::class, 'getGrades']);
    Route::get('/get_students', [Page_Grade::class, 'getStudents']);
    Route::get('/get_quizzes', [Page_Grade::class, 'getQuizzes']);
    Route::get('/student/{studentNumber}/grades', [Page_Grade::class, 'getStudentGrades']);
});




// ===========================================================================
// STUDENT ROUTES
// ===========================================================================

Route::prefix('student')->name('student.')->group(function () {
    // Guest Routes
    Route::middleware('guest:student')->group(function () {
        Route::get('/login', [StudentController::class, 'login'])->name('login');
        Route::post('/auth', [Login_Controller::class, 'auth_student'])->name('auth');
    });

    // Authenticated Routes
    Route::middleware('auth:student')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('home');
        Route::post('/logout', [Login_Controller::class, 'logout_student'])->name('logout');

        // Class List
        Route::get('/class', [Class_List::class, 'student_class_list'])->name('list_class');
        Route::get('/classes/list', [Class_List::class, 'getStudentClasses'])->name('classes.list');
        Route::get('/classes/{id}/details', [Class_List::class, 'getClassDetails'])->name('classes.details');

        // Class Content Routes
        Route::prefix('class/{classId}')->name('class.')->group(function () {
            // Lessons
            Route::get('/lessons', [Page_Lesson::class, 'studentIndex'])->name('lessons');
            Route::get('/lessons/list', [Page_Lesson::class, 'studentList'])->name('lessons.list');

            // Quizzes
            Route::get('/quizzes', function ($classId) {
                $class = DB::table('classes')->where('id', $classId)->first();
                return view('modules.class.page_quiz', ['userType' => 'student', 'class' => $class]);
            })->name('quizzes');

            // Grades
            Route::get('/grades', [Page_Grade::class, 'studentIndex'])->name('grades');

            // Lecture Routes
            Route::prefix('lesson/{lessonId}')->group(function () {
                Route::get('lecture/{lectureId}', [Page_Lecture::class, 'view'])->name('lectures.view');
                Route::get('lecture/{lectureId}/data', [Page_Lecture::class, 'getData'])->name('lectures.view.data');
                Route::get('/{lectureId}/download/{filename}', [Page_Lecture::class, 'download'])->name('download');
                Route::get('/{lectureId}/stream/{filename}', [Page_Lecture::class, 'stream'])->name('stream');

                // Quiz Routes
                Route::prefix('quiz/{quizId}')->group(function () {
                    Route::get('/', [Page_Quiz::class, 'studentViewQuiz'])->name('quiz.view');
                    Route::get('/start', [Page_Quiz::class, 'studentStartQuiz'])->name('quiz.start');
                    Route::post('/submit', [Page_Quiz::class, 'studentSubmitQuiz'])->name('quiz.submit');
                    Route::get('/results/{attemptId}', [Page_Quiz::class, 'studentGetResults'])->name('quiz.results');
                });
            });
        });
    });
});

// ===========================================================================
// TEACHER ROUTES
// ===========================================================================

Route::prefix('teacher')->name('teacher.')->group(function () {
    // Guest Routes
    Route::get('/login', [TeacherController::class, 'login'])->name('login');
    Route::post('/auth', [Login_Controller::class, 'auth_teacher'])->name('auth');

    // Authenticated Routes
    Route::middleware('auth:teacher')->group(function () {
        Route::get('/home', [TeacherController::class, 'index'])->name('home');
        Route::post('/logout', [Login_Controller::class, 'logout_teacher'])->name('logout');

        // Class List
        Route::get('/list_class', [Class_List::class, 'teacher_class_list'])->name('list_class');
        Route::get('/classes/list', [Class_List::class, 'getTeacherClasses'])->name('classes.list');

        // Class Management Routes
        Route::prefix('class/{classId}')->name('class.')->group(function () {
            // Main Pages
            Route::get('/lessons', [Page_Lesson::class, 'teacherIndex'])->name('lessons');
            Route::get('/quizzes', [Page_Quiz::class, 'teacherIndex'])->name('quizzes');
            Route::get('/grades', [Page_Grade::class, 'teacherIndex'])->name('grades');
            Route::get('/participants', [Page_Participant::class, 'teacherIndex'])->name('participants');
            Route::get('/participants/list', [Page_Participant::class, 'getParticipants'])->name('participants.list');

            // Lesson CRUD
            Route::get('/lessons/list', [Page_Lesson::class, 'teacherList'])->name('lessons.list');
            Route::post('/lessons', [Page_Lesson::class, 'store'])->name('lessons.store');
            Route::put('/lessons/{lessonId}', [Page_Lesson::class, 'update'])->name('lessons.update');
            Route::delete('/lessons/{lessonId}', [Page_Lesson::class, 'destroy'])->name('lessons.delete');

            // Lecture Management
            Route::prefix('lesson/{lessonId}/lecture')->name('lectures.')->group(function () {
                Route::get('/create', [Page_Lecture::class, 'create'])->name('create');
                Route::post('/', [Page_Lecture::class, 'store'])->name('store');
                Route::get('/{lectureId}/edit', [Page_Lecture::class, 'edit'])->name('edit');
                Route::put('/{lectureId}', [Page_Lecture::class, 'update'])->name('update');
                Route::delete('/{lectureId}', [Page_Lecture::class, 'destroy'])->name('destroy');
                Route::get('/{lectureId}/download/{filename}', [Page_Lecture::class, 'download'])->name('download');
                Route::get('/{lectureId}/stream/{filename}', [Page_Lecture::class, 'stream'])->name('stream');
            });

            // Quiz Management
            Route::prefix('lesson/{lessonId}/quiz')->name('quiz.')->group(function () {
                Route::get('/create', [Page_Quiz::class, 'teacherCreate'])->name('create');
                Route::post('/store', [Page_Quiz::class, 'store'])->name('store');
                Route::get('/{quizId}/edit', [Page_Quiz::class, 'teacherEdit'])->name('edit');
                Route::get('/{quizId}/data', [Page_Quiz::class, 'getQuizData'])->name('data');
                Route::put('/{quizId}/update', [Page_Quiz::class, 'update'])->name('update');
                Route::delete('/{quizId}/delete', [Page_Quiz::class, 'destroy'])->name('delete');
            });
        });
    });
});
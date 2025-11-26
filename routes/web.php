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
use App\Http\Controllers\Class_Management\Year_Management;
use App\Http\Controllers\Class_Management\Quiz_Attempt;
use App\Http\Controllers\Class_Management\Quiz_Submit;
use App\Http\Controllers\Grade_Management\Grade_Management;
use App\Http\Controllers\Grade_Management\GradeBook_Management;
use App\Http\Controllers\Grade_Management\Grade_list;

use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\Enrollment_Management\Enroll_Management;
use App\Http\Controllers\Enrollment_Management\SectionController;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\User_Management\Profile_Management;
use App\Http\Controllers\User_Management\User_Management;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\QuizAttemptMiddleware;

// ===========================================================================
// PUBLIC ROUTES
// ===========================================================================

Route::get('/', [DeveloperController::class, 'index']);
Route::get('/login', fn() => view('auth.login'));


// ===========================================================================
// ADMIN ROUTES
// ===========================================================================

Route::prefix('admin')->name('admin.')->group(function () {
    // Auth Routes
    Route::get('/login', [Admin::class, 'login'])->name('login');
    Route::get('/', [Admin::class, 'index'])->name('home');


    // ---------------------------------------------------------------------------
    // School Year MANAGEMENT
    // ---------------------------------------------------------------------------
    Route::prefix('schoolyears')->group(function () {
        Route::get('/', [Year_Management::class, 'list_schoolyear'])->name('schoolyears.index');
        Route::get('/list', [Year_Management::class, 'getSchoolYearsData'])->name('schoolyears.list');
        Route::post('/create', [Year_Management::class, 'createSchoolYear'])->name('schoolyears.create');
        Route::put('/{id}/update', [Year_Management::class, 'updateSchoolYear'])->name('schoolyears.update');
        Route::post('/{id}/set-active', [Year_Management::class, 'setActiveSchoolYear'])->name('schoolyears.set-active');

    });

    // Semester Management Routes
    Route::prefix('semesters')->group(function () {
        Route::get('/', [Year_Management::class, 'list_semester'])->name('semesters.index');
        Route::get('/list', [Year_Management::class, 'getSemestersData'])->name('semesters.list');
        Route::post('/create', [Year_Management::class, 'createSemester'])->name('semesters.create');
        Route::put('/{id}/update', [Year_Management::class, 'updateSemester'])->name('semesters.update');
        Route::post('/{id}/set-active', [Year_Management::class, 'setActiveSemester'])->name('semesters.set-active');
        Route::get('/{id}/classes', [Year_Management::class, 'getSemesterClasses'])->name('semesters.classes');
        Route::get('/{semesterId}/class/{classCode}/history', [Year_Management::class, 'getEnrollmentHistory'])->name('semesters.enrollment-history');
    });

    Route::prefix('grades')->name('grades.')->group(function () {
        // View page
        Route::get('/list', [Grade_Management::class, 'list_grades'])->name('list');
        
        // AJAX endpoints
        Route::get('/api/classes', [Grade_Management::class, 'getClassesForFilter'])->name('classes');
        Route::get('/api/semesters', [Grade_Management::class, 'getSemestersForFilter'])->name('semesters');
        Route::get('/api/search', [Grade_Management::class, 'searchGrades'])->name('search');
        Route::get('/api/details/{id}', [Grade_Management::class, 'getGradeDetails'])->name('details');
    });

    // ---------------------------------------------------------------------------
    // USER MANAGEMENT
    // ---------------------------------------------------------------------------
    
    Route::prefix('user_management')->group(function () {
        // Student Pages
        Route::get('/create_student', [User_Management::class, 'create_student'])->name('create_student');
        Route::get('/list_student', [User_Management::class, 'list_students'])->name('list_student');
        
        Route::get('/get_sections/filter', [User_Management::class, 'getSectionsForFilter'])->name('sections.filter');

        // Teacher Pages
        Route::get('/create_teacher', [User_Management::class, 'create_teacher'])->name('create_teacher');
        Route::get('/list_teacher', [User_Management::class, 'list_teacher'])->name('list_teacher');
    });

    // ---------------------------------------------------------------------------
    // ENROLLMENT MANAGEMENT
    // ---------------------------------------------------------------------------
    
    Route::prefix('enrollment_management')->group(function () {
        // Section Pages
        Route::get('/enroll_class', [Enroll_Management::class, 'enroll_class'])->name('enroll_class');
        
        // Route::get('/section-class-enrollment', [Enroll_Management::class, 'sectionClassEnrollment'])->name('section_class_enrollment');
        
        Route::prefix('sections')->group(function () {
            Route::get('/', [SectionController::class, 'index'])
                ->name('enrollment.sections');
            
            Route::get('/list', [SectionController::class, 'getSectionsList'])
                ->name('sections.list');
            
            Route::get('/{id}/details', [SectionController::class, 'getSectionDetails'])
                ->name('sections.details');
            
            Route::get('/{sectionId}/available-classes', [SectionController::class, 'getAvailableClasses'])
                ->name('classes.available');
            
            Route::post('/{id}/enroll', [SectionController::class, 'enrollClass'])
                ->name('sections.enroll');
            
            Route::delete('/{sectionId}/classes/{classId}', [SectionController::class, 'removeClass'])
                ->name('sections.remove-class');
        });

        // Student Pages
        Route::get('/student_irreg_enroll', [Enroll_Management::class, 'studentIrregEnrollment'])->name('student_irreg_class_enrollment');
        Route::get('/students/{id}/enrollment', [Enroll_Management::class, 'studentClassEnrollment'])->name('student_class_enrollment');
        
        // Class Pages
        Route::get('/class-students', [Enroll_Management::class, 'classes_enrollment'])->name('classes.students.index');
    });

    // ---------------------------------------------------------------------------
    // CLASS MANAGEMENT
    // ---------------------------------------------------------------------------
    
    Route::prefix('class_management')->group(function () {
        Route::post('/insert_class', [Class_Management::class, 'insert_class'])->name('insert_class');
        Route::get('/list_class', [Class_Management::class, 'list_class'])->name('list_class');
        Route::get('/list_strand', [Class_Management::class, 'list_strand'])->name('list_strand');
        Route::get('/list_section', [Class_Management::class, 'list_section'])->name('list_section');
    
        Route::get('/get_class/{id}', [Class_Management::class, 'getClassData'])
            ->name('get_class');

        // Update Class
        Route::put('/update_class/{id}', [Class_Management::class, 'updateClass'])
            ->name('update_class');
    
    });
});

// ===========================================================================
// PROFILE MANAGEMENT 
// ===========================================================================

Route::prefix('profile')->name('profile.')->group(function () {
    // Student Profiles
    Route::prefix('student/{id}')->group(function () {
        Route::get('/', [Profile_Management::class, 'show_student'])->name('student.show');
        Route::get('/edit', [Profile_Management::class, 'edit_student'])->name('student.edit');
        Route::post('/update', [Profile_Management::class, 'update_student'])->name('student.update');
    
        Route::get('/enrolled-classes', [Profile_Management::class, 'get_enrolled_classes'])
            ->name('student.enrolled_classes');
    });

    // Teacher Profiles
    Route::prefix('teacher/{id}')->group(function () {
        Route::get('/', [Profile_Management::class, 'show_teacher'])->name('teacher.show');
        Route::get('/edit', [Profile_Management::class, 'edit_teacher'])->name('teacher.edit');
        Route::post('/update', [Profile_Management::class, 'update_teacher'])->name('teacher.update');
    });
});

// Admin Data Routes
Route::get('admin/data/{id}', [Data_Controller::class, 'student_data'])->name('data.student');

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

        // Class Pages
        Route::get('/my_classes', [Class_List::class, 'student_class_list'])->name('list_class');
        Route::get('/my_grades', [Grade_list::class, 'student_grade_list'])->name('list_grade');
        
        // Get Student Grades (AJAX)
        Route::get('/my_grades/list', [Grade_list::class, 'getStudentGrades'])
            ->name('grades.list');
        
        // Get Class Grade Details (AJAX)
        Route::get('/my_grades/details/{classId}', [Grade_list::class, 'getClassGradeDetails'])
            ->name('grades.details');

        // Class Content Pages
        Route::prefix('class/{classId}')->name('class.')->group(function () {
            // Lessons Page
            Route::get('/lessons', [Page_Lesson::class, 'studentIndex'])->name('lessons');
            

            
            // Grades Page
            Route::get('/grades', [Page_Grade::class, 'studentIndex'])->name('grades');

            // Lecture Pages
            Route::prefix('lesson/{lessonId}')->group(function () {
                Route::get('lecture/{lectureId}', [Page_Lecture::class, 'view'])->name('lectures.view');
                Route::get('/{lectureId}/download/{filename}', [Page_Lecture::class, 'download'])->name('download');
                Route::get('/{lectureId}/stream/{filename}', [Page_Lecture::class, 'stream'])->name('stream');

                // Quiz Pages
                Route::get('quiz/{quizId}', [Quiz_Attempt::class, 'viewQuiz'])->name('quiz.view');

                Route::get('/quiz/{quizId}/start', [Quiz_Attempt::class, 'startQuiz'])
                    ->middleware(QuizAttemptMiddleware::class)
                    ->name('quiz.start');                
                Route::get('/quiz/{quizId}/save-progress', [Quiz_Attempt::class, 'saveProgress'])->name('quiz.save-progress');

                Route::post('/quiz/{quizId}/submit', [Quiz_Submit::class, 'submitQuiz'])->name('quiz.submit');
                Route::get('/quiz/{quizId}/results/{attemptId}', [Quiz_Attempt::class, 'getResults'])->name('quiz.results');
                Route::post('/quiz/{quizId}/heartbeat', [Quiz_Attempt::class, 'heartbeat'])->name('quiz.heartbeat');
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

        // Class Pages
        Route::get('/list_class', [Class_List::class, 'teacher_class_list'])->name('list_class');

        // Class Content Pages
        Route::prefix('class/{classId}')->name('class.')->group(function () {
            // Main Pages
            Route::get('/lessons', [Page_Lesson::class, 'teacherIndex'])->name('lessons');
            Route::get('/quizzes', [Page_Quiz::class, 'teacherIndex'])->name('quizzes');
            Route::get('/grades', [Page_Grade::class, 'teacherIndex'])->name('grades');
            Route::get('/participants', [Page_Participant::class, 'teacherIndex'])->name('participants');

            // Lecture Management Pages
            Route::prefix('lesson/{lessonId}/lecture')->name('lectures.')->group(function () {
                Route::get('/create', [Page_Lecture::class, 'create'])->name('create');
                Route::get('/{lectureId}/edit', [Page_Lecture::class, 'edit'])->name('edit');
                Route::get('/{lectureId}/download/{filename}', [Page_Lecture::class, 'download'])->name('download');
                Route::get('/{lectureId}/stream/{filename}', [Page_Lecture::class, 'stream'])->name('stream');
            });

            // Quiz Management Pages
            Route::prefix('lesson/{lessonId}/quiz')->name('quiz.')->group(function () {
                Route::get('/create', [Page_Quiz::class, 'teacherCreate'])->name('create');
                Route::get('/{quizId}/edit', [Page_Quiz::class, 'teacherEdit'])->name('edit');
            });
        });

        Route::prefix('gradebook')->name('gradebook.')->group(function() {
            Route::get('/{classId}', [GradeBook_Management::class, 'list_gradebook'])
                ->name('view');
            
            Route::get('/{classId}/data', [GradeBook_Management::class, 'getGradebookData'])
                ->name('data');
                
            Route::post('/{classId}/column/add', [GradeBook_Management::class, 'addColumn'])
                ->name('column.add');
            
            Route::put('/column/{columnId}', [GradeBook_Management::class, 'updateColumn'])
                ->name('column.update');
            
            Route::post('/{classId}/scores/batch', [GradeBook_Management::class, 'batchUpdateScores'])
                ->name('scores.batch');
            
            Route::get('/{classId}/quizzes', [GradeBook_Management::class, 'getAvailableQuizzes'])
                ->name('quizzes');
            Route::post('/class/{classId}/export', [GradeBook_Management::class, 'exportGradebook'])
                ->name('export');
        });
    });
});
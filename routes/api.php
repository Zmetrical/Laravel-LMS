<?php

use App\Http\Controllers\Class_Management\Class_List;
use App\Http\Controllers\Class_Management\Class_Management;
use App\Http\Controllers\Class_Management\Page_Grade;
use App\Http\Controllers\Class_Management\Page_Lecture;
use App\Http\Controllers\Class_Management\Page_Lesson;
use App\Http\Controllers\Class_Management\Page_Participant;
use App\Http\Controllers\Class_Management\Page_Quiz;
use App\Http\Controllers\Enrollment_Management\Enroll_Management;
use App\Http\Controllers\User_Management\User_Management;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are for AJAX calls and don't require full page authentication.
| They are prefixed with /api automatically.
|
*/

// ===========================================================================
// PUBLIC API ROUTES (No Authentication Required)
// ===========================================================================

// Helper/Lookup Routes - Keep original route names
Route::get('sections/data', [User_Management::class, 'get_Sections'])->name('sections.data');

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

// ===========================================================================
// ADMIN API ROUTES
// ===========================================================================

Route::prefix('admin')->name('admin.')->group(function () {
    // User Management API

    Route::prefix('user_management')->group(function () {
        // Student Pages
        Route::post('/insert_student', [User_Management::class, 'insert_student'])->name('insert_student');
        Route::post('/insert_students', [User_Management::class, 'insert_students'])->name('insert_students');
        
        // Teacher Pages
        Route::post('/insert_teacher', [User_Management::class, 'insert_teacher'])->name('insert_teacher');
    });

    Route::get('/levels/data', [Class_Management::class, 'getLevelsData'])->name('levels.data');



    // Strand Management Routes
    Route::prefix('strands')->group(function () {
        // Get strands data (AJAX)
        Route::get('/data', [Class_Management::class, 'getStrandsData'])->name('strands.data');
        
        // Create strand
        Route::post('/', [Class_Management::class, 'createStrand'])->name('strands.store');
        
        // Update strand
        Route::put('/{id}', [Class_Management::class, 'updateStrand'])->name('strands.update');
        Route::get('/{id}/sections', [Class_Management::class, 'getStrandSections'])->name('strands.sections');
    });

    Route::prefix('sections')->group(function () {
        Route::get('/data', [Class_Management::class, 'getSectionsData'])->name('sections.data');

        Route::post('/create', [Class_Management::class, 'createSection'])->name('sections.create');
        Route::put('/{id}', [Class_Management::class, 'updateSection'])->name('sections.update');
        Route::get('/{id}/classes', [Class_Management::class, 'getSectionClasses'])->name('sections.classes');
    });

    // Enroll Management API
        Route::get('/students', [Enroll_Management::class, 'getStudentsData'])->name('students.list');
        Route::get('/students/{id}/info', [Enroll_Management::class, 'getStudentInfo'])->name('students.info');
        Route::get('/students/{id}/classes', [Enroll_Management::class, 'getStudentClasses'])->name('students.classes');
        Route::post('/students/enroll', [Enroll_Management::class, 'enrollStudentClass'])->name('students.enroll');
        Route::post('/students/unenroll', [Enroll_Management::class, 'removeStudentClass'])->name('students.unenroll');
        
        // Teachers
        Route::get('/teachers', [Enroll_Management::class, 'getTeachersList'])->name('teachers.list');

    // Section Management API
    Route::prefix('sections')->name('sections.')->group(function () {
        Route::get('/', [Enroll_Management::class, 'getSectionsData'])->name('list');
        Route::get('/{id}/details', [Enroll_Management::class, 'getDetails'])->name('details');
        Route::get('/{id}/classes/details', [Enroll_Management::class, 'getSectionClasses'])->name('classes.details');
        
        Route::post('/{id}/enroll-class', [Enroll_Management::class, 'enrollClass'])->name('enroll');
        Route::delete('/{sectionId}/remove-class/{classId}', [Enroll_Management::class, 'removeClass'])->name('remove-class');
    });

    // Class Management API
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::get('/', [Enroll_Management::class, 'getClassesList'])->name('list');
        Route::get('/{id}/details', [Enroll_Management::class, 'getClassDetails'])->name('details');
        Route::get('/{id}/students', [Enroll_Management::class, 'getClassStudents'])->name('students');
        Route::get('/{sectionId}/available', [Enroll_Management::class, 'getAvailableClasses'])->name('available');
        Route::post('/assign-teacher', [Enroll_Management::class, 'assignTeacher'])->name('assign-teacher');
        Route::post('/remove-teacher', [Enroll_Management::class, 'removeTeacher'])->name('remove-teacher');
    });

});

// ===========================================================================
// STUDENT API ROUTES
// ===========================================================================

Route::prefix('student')->name('student.')->middleware(['web', 'auth:student'])->group(function () {
    
    // Classes
    Route::get('/classes', [Class_List::class, 'getStudentClasses'])->name('classes.list');
    Route::get('/classes/{id}/details', [Class_List::class, 'getClassDetails'])->name('classes.details');
    
    // Class Content
    Route::prefix('class/{classId}')->name('class.')->group(function () {
        // Lessons
        Route::get('/lessons', [Page_Lesson::class, 'studentList'])->name('lessons.list');
        
        // Lecture Data
        Route::get('/lesson/{lessonId}/lecture/{lectureId}/data', [Page_Lecture::class, 'getData'])
            ->name('lecture.data');
        
        // Quiz API
        Route::prefix('lesson/{lessonId}/quiz/{quizId}')->name('quiz.')->group(function () {
            Route::get('/start', [Page_Quiz::class, 'studentStartQuiz'])->name('start');
            Route::post('/submit', [Page_Quiz::class, 'studentSubmitQuiz'])->name('submit');
            Route::get('/results/{attemptId}', [Page_Quiz::class, 'studentGetResults'])->name('results');
        });
        
        // Grades
        Route::get('/grades', [Page_Grade::class, 'getGrades'])->name('grades.list');
        Route::get('/student/{studentNumber}/grades', [Page_Grade::class, 'getStudentGrades'])->name('grades.student');
    });
});

// ===========================================================================
// TEACHER API ROUTES
// ===========================================================================

Route::prefix('teacher')->name('teacher.')->middleware(['web', 'auth:teacher'])->group(function () {
    
    // Classes
    Route::get('/classes', [Class_List::class, 'getTeacherClasses'])->name('classes.list');
    
    // Class Content Management
    Route::prefix('class/{classId}')->name('class.')->group(function () {
        // Lessons
        Route::get('/lessons', [Page_Lesson::class, 'teacherList'])->name('lessons.list');
        Route::post('/lessons', [Page_Lesson::class, 'store'])->name('lessons.store');
        Route::put('/lessons/{lessonId}', [Page_Lesson::class, 'update'])->name('lessons.update');
        Route::delete('/lessons/{lessonId}', [Page_Lesson::class, 'destroy'])->name('lessons.delete');
        
        // Lectures
        Route::prefix('lesson/{lessonId}/lecture')->name('lecture.')->group(function () {
            Route::post('/', [Page_Lecture::class, 'store'])->name('store');
            Route::put('/{lectureId}', [Page_Lecture::class, 'update'])->name('update');
            Route::delete('/{lectureId}', [Page_Lecture::class, 'destroy'])->name('delete');
        });
        
        // Quizzes
        Route::prefix('lesson/{lessonId}/quiz')->name('quiz.')->group(function () {
            Route::post('/', [Page_Quiz::class, 'store'])->name('store');
            Route::get('/{quizId}/data', [Page_Quiz::class, 'getQuizData'])->name('data');
            Route::put('/{quizId}', [Page_Quiz::class, 'update'])->name('update');
            Route::delete('/{quizId}', [Page_Quiz::class, 'destroy'])->name('delete');
        });
        
        // Participants
        Route::get('/participants', [Page_Participant::class, 'getParticipants'])->name('participants.list');
        
        // Grades
        Route::get('/grades', [Page_Grade::class, 'getGrades'])->name('grades.list');
        Route::get('/students', [Page_Grade::class, 'getStudents'])->name('students.list');
        Route::get('/quizzes', [Page_Grade::class, 'getQuizzes'])->name('quizzes.list');
    });



});
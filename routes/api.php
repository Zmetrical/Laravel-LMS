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

// ===========================================================================
// PUBLIC API ROUTES
// ===========================================================================

Route::get('/section/data', [User_Management::class, 'get_Sections'])->name('api.sections.data');

Route::get('/level/data', function () {
    return response()->json([
        'success' => true,
        'data' => DB::table('levels')->get()
    ]);
})->name('api.levels.data');

Route::get('/strand/data', function () {
    return response()->json([
        'success' => true,
        'data' => DB::table('strands')->where('status', 1)->get()
    ]);
})->name('api.strands.data');

// ===========================================================================
// ADMIN API ROUTES
// ===========================================================================

Route::prefix('admin')->name('admin.')->group(function () {

    Route::prefix('user_management')->group(function () {
        Route::post('/insert_student', [User_Management::class, 'insert_student'])->name('insert_student');
        Route::post('/insert_students', [User_Management::class, 'insert_students'])->name('insert_students');
        Route::post('/insert_teacher', [User_Management::class, 'insert_teacher'])->name('insert_teacher');
        Route::post('/active/toggle-status', [User_Management::class, 'toggleTeacherStatus'])
            ->name('teachers.active.toggleStatus');
    });

    Route::get('/levels/data', [Class_Management::class, 'getLevelsData'])->name('api.levels.data');

    // Strand Management Routes
    Route::prefix('strands')->group(function () {
        Route::get('/data', [Class_Management::class, 'getStrandsData'])->name('api.strands.data');
        Route::post('/', [Class_Management::class, 'createStrand'])->name('api.strands.store');
        Route::put('/{id}', [Class_Management::class, 'updateStrand'])->name('api.strands.update');
        Route::get('/{id}/sections', [Class_Management::class, 'getStrandSections'])->name('api.strands.sections');
    });

    Route::prefix('sections')->group(function () {
        Route::get('/data', [Class_Management::class, 'getSectionsData'])->name('api.sections.data');
        Route::post('/create', [Class_Management::class, 'createSection'])->name('api.sections.create');
        Route::put('/{id}', [Class_Management::class, 'updateSection'])->name('api.sections.update');
        Route::get('/{id}/classes', [Class_Management::class, 'getSectionClasses'])->name('api.sections.classes');
    });

    // Enroll Management API
    Route::get('/students', [Enroll_Management::class, 'getStudentsData'])->name('students.list');
    Route::get('/students/{id}/info', [Enroll_Management::class, 'getStudentInfo'])->name('students.info');
    Route::get('/students/{id}/classes', [Enroll_Management::class, 'getStudentClasses'])->name('students.classes');
    Route::post('/students/enroll', [Enroll_Management::class, 'enrollStudentClass'])->name('students.enroll');
    Route::post('/students/unenroll', [Enroll_Management::class, 'removeStudentClass'])->name('students.unenroll');

    Route::get('/teachers', [Enroll_Management::class, 'getTeachersList'])->name('teachers.list');

    // Class Management API
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::get('/', [Enroll_Management::class, 'getClassesList'])->name('list');
        Route::get('/{id}/details', [Enroll_Management::class, 'getClassDetails'])->name('details');
        Route::get('/{id}/students', [Enroll_Management::class, 'getClassStudents'])->name('students');
        Route::get('/{sectionId}/available', [Enroll_Management::class, 'getAvailableClasses'])->name('api.available');
        Route::post('/assign-teacher', [Enroll_Management::class, 'assignTeacher'])->name('assign-teacher');
        Route::post('/remove-teacher', [Enroll_Management::class, 'removeTeacher'])->name('remove-teacher');
    });

});

// ===========================================================================
// STUDENT API ROUTES
// ===========================================================================

// api.php
Route::prefix('student')->name('student.')->middleware(['web', 'auth:student'])->group(function () {
    Route::get('/classes', [Class_List::class, 'getStudentClasses'])->name('api.classes.list');
    Route::get('/classes/{id}/details', [Class_List::class, 'getClassDetails'])->name('api.classes.details');

    Route::prefix('class/{classId}')->name('class.')->group(function () {
        Route::get('/lessons', [Page_Lesson::class, 'studentList'])->name('api.lessons.list');
        Route::get('/grades', [Page_Grade::class, 'getGrades'])->name('api.grades.list');
        Route::get('/student/{studentNumber}/grades', [Page_Grade::class, 'getStudentGrades'])->name('api.grades.student');
        Route::get('/lesson/{lessonId}/lecture/{lectureId}/data', [Page_Lecture::class, 'getData'])->name('api.lecture.data');
    });
});
// ===========================================================================
// TEACHER API ROUTES
// ===========================================================================

Route::prefix('teacher')->name('teacher.')->middleware(['web', 'auth:teacher'])->group(function () {

    Route::get('/classes', [Class_List::class, 'getTeacherClasses'])->name('classes.list');

    Route::prefix('class/{classId}')->name('class.')->group(function () {
        Route::get('/lessons', [Page_Lesson::class, 'teacherList'])->name('lessons.list');
        Route::post('/lessons', [Page_Lesson::class, 'store'])->name('lessons.store');
        Route::put('/lessons/{lessonId}', [Page_Lesson::class, 'update'])->name('lessons.update');
        Route::delete('/lessons/{lessonId}', [Page_Lesson::class, 'destroy'])->name('lessons.delete');

        Route::prefix('lesson/{lessonId}/lecture')->name('lecture.')->group(function () {
            Route::post('/', [Page_Lecture::class, 'store'])->name('store');
            Route::put('/{lectureId}', [Page_Lecture::class, 'update'])->name('update');
            Route::delete('/{lectureId}', [Page_Lecture::class, 'destroy'])->name('delete');
        });

        Route::prefix('lesson/{lessonId}/quiz')->name('quiz.')->group(function () {
            Route::post('/', [Page_Quiz::class, 'store'])->name('store');
            Route::get('/{quizId}/data', [Page_Quiz::class, 'getQuizData'])->name('data');
            Route::put('/{quizId}', [Page_Quiz::class, 'update'])->name('update');
            Route::delete('/{quizId}', [Page_Quiz::class, 'destroy'])->name('delete');
        });

        Route::get('/participants', [Page_Participant::class, 'getParticipants'])->name('api.participants.list');
        Route::get('/grades', [Page_Grade::class, 'getGrades'])->name('api.grades.list');
        Route::get('/students', [Page_Grade::class, 'getStudents'])->name('api.students.list');
        Route::get('/quizzes', [Page_Grade::class, 'getQuizzes'])->name('api.quizzes.list');
        Route::get('/lessons', [Page_Lesson::class, 'teacherList'])->name('api.lessons.list');
    });

});
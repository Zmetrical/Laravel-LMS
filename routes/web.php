<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\TestDevController;

use App\Http\Controllers\Auth\Data_Controller;
use App\Http\Controllers\Auth\Login_Controller;
use App\Http\Controllers\Class_Management\Class_List;
use App\Http\Controllers\Class_Management\Teacher_Card_List;

use App\Http\Controllers\Class_Management\Class_Management;
use App\Http\Controllers\Class_Management\Page_Grade;
use App\Http\Controllers\Class_Management\Page_Lecture;
use App\Http\Controllers\Class_Management\Page_Lesson;
use App\Http\Controllers\Class_Management\Page_Participant;
use App\Http\Controllers\Class_Management\Page_Quiz;
use App\Http\Controllers\Class_Management\Quiz_Attempt;
use App\Http\Controllers\Class_Management\Quiz_Submit;
use App\Http\Controllers\Grade_Management\Grade_Management;

use App\Http\Controllers\Class_Management\Year_Management;
use App\Http\Controllers\Class_Management\Semester_Management;
use App\Http\Controllers\Class_Management\Graduation_Management;


use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Audit\TeacherAuditController;
use App\Http\Controllers\Audit\StudentAuditController;


use App\Http\Controllers\Grade_Management\GradeBook_Management;
use App\Http\Controllers\Grade_Management\GradebookViewController;
use App\Http\Controllers\Grade_Management\GradebookEditController;
use App\Http\Controllers\Grade_Management\GradebookImportExportController;
use App\Http\Controllers\Grade_Management\SectionGrade_Management;

use App\Http\Controllers\Grade_Management\Grade_List;
use App\Http\Controllers\Grade_Management\Grade_Card;

use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\Enrollment_Management\Enroll_Management;
use App\Http\Controllers\Enrollment_Management\SectionController;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\GuardianController;

use App\Http\Controllers\User_Management\Profile_Management;
use App\Http\Controllers\User_Management\User_Management;
use App\Http\Controllers\User_Management\GuardianEmailController;
use App\Http\Controllers\User_Management\Admin_Management;

use App\Http\Controllers\User_Management\Section_Management;

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\QuizAttemptMiddleware;

// ===========================================================================
// PUBLIC ROUTES
// ===========================================================================

Route::get('/', [DeveloperController::class, 'index'])->name('index');


// ===========================================================================
// ADMIN ROUTES
// ===========================================================================

Route::prefix('admin')->name('admin.')->group(function () {
    // Auth Routes (accessible without authentication)
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [Admin::class, 'login'])->name('login');
        Route::post('/auth', [Login_Controller::class, 'auth_admin'])->name('auth');
    });

    Route::post('/logout', [Login_Controller::class, 'logout_admin'])->name('logout');

    // Protected Routes (require authentication)
    Route::middleware('auth:admin')->group(function () {
        Route::get('/home', [Admin::class, 'index'])->name('home');

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

        // Inside your admin middleware group:
        Route::prefix('graduation')->name('graduation.')->group(function () {

            // Main graduation page for a school year
            Route::get('/{schoolYearId}', [Graduation_Management::class, 'show'])
                ->name('show');

            // AJAX: get students with eligibility data
            Route::get('/{schoolYearId}/students', [Graduation_Management::class, 'getStudents'])
                ->name('students');

            // AJAX: save/update a single student's graduation record
            Route::post('/{schoolYearId}/save-record', [Graduation_Management::class, 'saveStudentRecord'])
                ->name('save-record');

            // AJAX: finalize the entire graduation list
            Route::post('/{schoolYearId}/finalize', [Graduation_Management::class, 'finalize'])
                ->name('finalize');
        });


        // Admin Management Routes (Super Admin Only)
            Route::get('/create_admin', [Admin_Management::class, 'create_admin'])->name('create_admin');
            Route::post('/insert_admin', [Admin_Management::class, 'insert_admin'])->name('insert_admin');
            Route::get('/list_admin', [Admin_Management::class, 'list_admin'])->name('list_admin');
            Route::post('/update_admin/{id}', [Admin_Management::class, 'update_admin'])->name('update_admin');
            Route::post('/reset_admin_password/{id}', [Admin_Management::class, 'reset_password'])->name('reset_admin_password');
            Route::delete('/delete_admin/{id}', [Admin_Management::class, 'delete_admin'])->name('delete_admin');

            Route::post('/toggle_admin_status/{id}', [Admin_Management::class, 'toggle_status'])->name('toggle_admin_status');


        // ---------------------------------------------------------------------------
        // ARCHIVE MANAGEMENT 
        // ---------------------------------------------------------------------------

        // Archive/Semester Management Routes
        Route::prefix('archive')->name('archive.')->group(function () {
            // Main page - GET request
            Route::get('/', [Semester_Management::class, 'archivePage'])->name('index');
            
            // Verification - POST request
            Route::post('/verify', [Semester_Management::class, 'verifyAdminAccess'])->name('verify');
            
            // Archive Info
            Route::get('/info/{id}', [Semester_Management::class, 'getArchiveInfo'])->name('info');
            Route::get('/semester-details/{id}', [Semester_Management::class, 'getSemesterDetails'])->name('semester-details');
            Route::get('/section-students/{semesterId}/{sectionId}', [Semester_Management::class, 'getSectionStudents'])->name('section-students');
            
            Route::get('/archive/get-previous-semester/{id}', [Semester_Management::class, 'getPreviousSemester'])
            ->name('get-previous-semester');

            // Archive Actions
            Route::post('/semester/{id}/complete', [Semester_Management::class, 'completeSemester'])->name('complete-semester');            
            // Quick Enrollment Routes
            Route::get('/search-sections', [Semester_Management::class, 'searchSections'])->name('search-sections');
            Route::post('/load-students', [Semester_Management::class, 'loadStudentsFromSection'])->name('load-students');
            Route::post('/get-section-details', [Semester_Management::class, 'getSectionDetails'])->name('get-section-details');
            Route::post('/get-target-sections', [Semester_Management::class, 'getTargetSections'])->name('get-target-sections');
            Route::post('/get-section-capacity', [Semester_Management::class, 'getSectionCapacity'])->name('get-section-capacity');
            Route::post('/enroll-students', [Semester_Management::class, 'enrollStudents'])->name('enroll-students');
        });

        // Semester Activation (if not already exists)
        Route::post('/semesters/{id}/set-active', [Semester_Management::class, 'setActive'])->name('semesters.set-active');

        Route::post('/archive/check-graduating-students', [Semester_Management::class, 'checkGraduatingStudents'])
            ->name('archive.check-graduating-students');
            
        Route::get('/archive/get-available-strands', [Semester_Management::class, 'getAvailableStrands'])
            ->name('archive.get-available-strands');
    
        Route::get('archive/semesters', [Semester_Management::class, 'getSemesters'])
            ->name('archive.get-semesters');

        // Semester Management Routes
        Route::prefix('semesters')->group(function () {
            Route::get('/', [Year_Management::class, 'list_semester'])->name('semesters.index');
            Route::get('/list', [Year_Management::class, 'getSemestersData'])->name('semesters.list');
            Route::post('/create', [Year_Management::class, 'createSemester'])->name('semesters.create');
            Route::put('/{id}/update', [Year_Management::class, 'updateSemester'])->name('semesters.update');
            Route::post('/{id}/set-active', [Year_Management::class, 'setActiveSemester'])->name('semesters.set-active');
            Route::get('/{id}/classes', [Year_Management::class, 'getSemesterClasses'])->name('semesters.classes');
            Route::get('/{id}/quarters', [Year_Management::class, 'getQuarters'])
                ->name('semesters.quarters');
            Route::get('/{semesterId}/quarters', [Year_Management::class, 'getQuartersData'])->name('quarters.list');
            Route::get('/{semesterId}/class/{classCode}/history', [Year_Management::class, 'getEnrollmentHistory'])->name('semesters.enrollment-history');


            Route::get('/semesters/{id}/sections',[Year_Management::class, 'getSemesterSections'])
                ->name('semesters.sections');

            Route::get('/semesters/{semesterId}/sections/{sectionId}/enrollment',[Year_Management::class, 'getSectionEnrollment'])
                ->name('sections.enrollment');

                
        });




        Route::prefix('audit')->name('audit.')->group(function () {
            // Admin Audit Logs
        Route::get('/admin', [AuditLogController::class, 'adminIndex'])->name('admin.index');
        Route::get('/admin/data', [AuditLogController::class, 'getAdminLogs'])->name('admin.data');
        Route::get('/admin/details/{id}', [AuditLogController::class, 'getLogDetails'])->name('admin.details');

            // Teacher Audit Logs
            Route::get('/teachers', [AuditLogController::class, 'teacherIndex'])->name('teachers.index');
            Route::get('/teachers/data', [AuditLogController::class, 'getTeacherLogs'])->name('teachers.data');
            Route::get('/teachers/{id}', [AuditLogController::class, 'getLogDetails'])->name('teachers.details');

            // Student/Guardian Audit Logs
            Route::get('/students', [AuditLogController::class, 'studentIndex'])->name('students.index');
            Route::get('/students/data', [AuditLogController::class, 'getStudentLogs'])->name('students.data');
            Route::get('/students/{id}', [AuditLogController::class, 'getLogDetails'])->name('students.details');

            // Login Audit Logs
            Route::get('/login', [AuditLogController::class, 'loginIndex'])->name('login.index');
            Route::get('/login/data', [AuditLogController::class, 'getLoginLogs'])->name('login.data');
            Route::get('/login/{id}', [AuditLogController::class, 'getLoginDetails'])->name('login.details');
        });


        Route::get('/profile/teacher/{id}/credentials', [Profile_Management::class, 'getCredentials'])
            ->middleware('auth:admin')
            ->name('teacher.credentials');

    // Section Assignment Page
    Route::get('/section-assignment', [Section_Management::class, 'assign_section'])
        ->name('assign_section');

    // Section Assignment API Routes
    Route::prefix('section-assignment')->name('section_assignment.')->group(function () {
        // Load all students from current active semester
        Route::post('/load-students', [Section_Management::class, 'load_students'])
            ->name('load_students');
    
    // Get filter options (sections and strands) for current semester
    Route::post('/filter-options', [Section_Management::class, 'get_filter_options'])
        ->name('get_filter_options');
    
    // Get target sections for selected strand
    Route::post('/get-target-sections', [Section_Management::class, 'get_target_sections'])
        ->name('get_target_sections');
    
    // Get section capacity and enrolled count
    Route::post('/get-section-capacity', [Section_Management::class, 'get_section_capacity'])
        ->name('get_section_capacity');
    
    // Assign students to new section
    Route::post('/assign-students', [Section_Management::class, 'assign_students'])
        ->name('assign_students');
});

        // Section Adviser Routes (keep these as they are separate functionality)
        Route::prefix('section-adviser')->name('section_adviser.')->group(function () {
            Route::post('/get', [Section_Management::class, 'get_section_adviser'])
                ->name('get');
            Route::post('/save', [Section_Management::class, 'save_section_adviser'])
                ->name('save');
            Route::post('/search-teachers', [Section_Management::class, 'search_teachers'])
                ->name('search_teachers');
        });

        // Section Grades View
        Route::get('/grades/section-view', [SectionGrade_Management::class, 'index'])
            ->name('grades.section-view');

        // API endpoints for Section Grades View
        Route::get('/sections/grades-list', [SectionGrade_Management::class, 'getSectionsWithGrades'])
            ->name('sections.grades-list');

        Route::get('/sections/{id}/grades-details', [SectionGrade_Management::class, 'getSectionGradeDetails'])
            ->name('sections.grades-details');

        Route::get('/sections/{sectionId}/classes/{classId}/grades', [SectionGrade_Management::class, 'getClassGrades'])
            ->name('sections.class-grades');



        Route::prefix('grades')->name('grades.')->group(function () {
            Route::get('/list', [Grade_Management::class, 'list_grades'])->name('list');
            Route::get('/api/classes', [Grade_Management::class, 'getClassesForFilter'])->name('classes');
            Route::get('/api/semesters', [Grade_Management::class, 'getSemestersForFilter'])->name('semesters');
            Route::get('/api/search', [Grade_Management::class, 'searchGrades'])->name('search');
            Route::get('/api/details/{id}', [Grade_Management::class, 'getGradeDetails'])->name('details');

            Route::get('/cards', [Grade_Card::class, 'card_grades'])->name('cards');
            Route::get('/card/view', [Grade_Card::class, 'getGradeCard'])->name('card.view');
            Route::get('/card/{student_number}/{semester_id}', [Grade_Card::class, 'viewGradeCardPage'])->name('card.view.page');

            Route::get('/evaluation/{student_number}', [Grade_Card::class, 'evaluation'])->name('evaluation');
            Route::get('/evaluation/{student_number}/data', [Grade_Card::class, 'getEvaluationData'])->name('evaluation.data');

        });

        // ---------------------------------------------------------------------------
        // USER MANAGEMENT
        // ---------------------------------------------------------------------------
        Route::prefix('user_management')->group(function () {
            Route::get('/create_student', [User_Management::class, 'create_student'])->name('create_student');
            Route::get('/list_student', [User_Management::class, 'list_students'])->name('list_student');
            Route::get('/get_sections/filter', [User_Management::class, 'getSectionsForFilter'])->name('sections.filter');
            Route::get('/create_teacher', [User_Management::class, 'create_teacher'])->name('create_teacher');

            Route::get('/list_teacher', [User_Management::class, 'list_teacher'])->name('list_teacher');

            Route::post('/teachers/toggle-status', [User_Management::class, 'toggleTeacherStatus'])
                ->name('teachers.toggleStatus');
            Route::get('/teachers/{id}/history', [User_Management::class, 'teacherHistory'])
                ->name('teachers.history');
            Route::post('/teachers/toggle-status', [User_Management::class, 'toggleTeacherStatus'])
                ->name('teachers.toggleStatus');

        });


        // ---------------------------------------------------------------------------
        // GUARDIAN MANAGEMENT
        // ---------------------------------------------------------------------------
        Route::prefix('guardians')->name('guardians.')->group(function () {
            Route::get('/list', [GuardianEmailController::class, 'getGuardians'])->name('list');
            Route::get('/{id}/students', [GuardianEmailController::class, 'getGuardianStudents'])->name('students');
            Route::post('/send-verification', [GuardianEmailController::class, 'manualSendVerification'])->name('send_verification');
            Route::post('/send-access', [GuardianEmailController::class, 'manualSendAccessEmail'])->name('send_access');
            Route::post('/resend-verification', [GuardianEmailController::class, 'resendVerification'])->name('resend_verification');
            Route::post('/batch-send-verifications', [GuardianEmailController::class, 'batchSendVerifications'])->name('batch_send');
        });

        // ---------------------------------------------------------------------------
        // ENROLLMENT MANAGEMENT
        // ---------------------------------------------------------------------------
        Route::prefix('enrollment_management')->group(function () {
            Route::get('/enroll_class', [Enroll_Management::class, 'enroll_class'])->name('enroll_class');

            Route::prefix('sections')->group(function () {
                Route::get('/', [SectionController::class, 'index'])->name('enrollment.sections');
                Route::get('/list', [SectionController::class, 'getSectionsList'])->name('sections.list');
                Route::get('/{id}/details', [SectionController::class, 'getSectionDetails'])->name('sections.details');
                Route::get('/{sectionId}/available-classes', [SectionController::class, 'getAvailableClasses'])->name('classes.available');
                Route::post('/{id}/enroll', [SectionController::class, 'enrollClass'])->name('sections.enroll');
                Route::delete('/{sectionId}/classes/{classId}', [SectionController::class, 'removeClass'])->name('sections.remove-class');

                // Section Adviser routes
                Route::get('/{id}/adviser', [SectionController::class, 'getSectionAdviser'])
                    ->name('sections.adviser');
                Route::get('/teachers/available', [SectionController::class, 'getAvailableTeachers'])
                    ->name('teachers.available');
                Route::post('/{id}/assign-adviser', [SectionController::class, 'assignAdviser'])
                    ->name('sections.assign-adviser');
                Route::delete('/{id}/remove-adviser', [SectionController::class, 'removeAdviser'])
                    ->name('sections.remove-adviser');
            });

            Route::get('/student_irreg_enroll', [Enroll_Management::class, 'studentIrregEnrollment'])->name('student_irreg_class_enrollment');
            Route::get('/students/{id}/enrollment', [Enroll_Management::class, 'studentClassEnrollment'])->name('student_class_enrollment');
            Route::get('/class-students', [Enroll_Management::class, 'classes_enrollment'])->name('classes.students.index');
        });


        // ---------------------------------------------------------------------------
        // Profile MANAGEMENT
        // ---------------------------------------------------------------------------

        // Guardian Management from Student Profile
        Route::post('/profile/student/{id}/guardian/{guardian_id}/resend-verification', 
            [GuardianEmailController::class, 'resendGuardianVerification'])
            ->name('profile.student.guardian.resend_verification');

        Route::post('/profile/student/{id}/guardian/{guardian_id}/resend-access', 
            [GuardianEmailController::class, 'resendGuardianAccess'])
            ->name('profile.student.guardian.resend_access');

        Route::post('/profile/student/{id}/guardian/{guardian_id}/change-email', 
            [GuardianEmailController::class, 'changeGuardianEmail'])
            ->name('profile.student.guardian.change_email');

        Route::get('/profile/student/{id}/guardian/{guardian_id}/status', 
            [GuardianEmailController::class, 'getGuardianStatus'])
            ->name('profile.student.guardian.status');
        Route::get('/profile/student/{id}/credentials', 
            [Profile_Management::class, 'getStudentCredentials'])
            ->name('profile.student.credentials');
        // ---------------------------------------------------------------------------
        // CLASS MANAGEMENT
        // ---------------------------------------------------------------------------
        Route::prefix('class_management')->group(function () {
            // Classes
            Route::post('/insert_class', [Class_Management::class, 'insert_class'])->name('insert_class');
            Route::get('/list_class', [Class_Management::class, 'list_class'])->name('list_class');
            Route::get('/get_class/{id}', [Class_Management::class, 'getClassData'])->name('get_class');
            Route::put('/update_class/{id}', [Class_Management::class, 'updateClass'])->name('update_class');
            Route::get('/get_classes_data', [Class_Management::class, 'getClassesData'])->name('get_classes_data');
            Route::get('/classes/list', [Class_Management::class, 'getClassesList'])->name('get_classes_list');

            // Strands
            Route::get('/list_strand', [Class_Management::class, 'list_strand'])->name('list_strand');
            Route::get('/strands/data', [Class_Management::class, 'getStrandsData'])->name('strands.data');
            Route::post('/strands/create', [Class_Management::class, 'createStrand'])->name('strands.create');
            Route::put('/strands/update/{id}', [Class_Management::class, 'updateStrand'])->name('strands.update');
            Route::get('/strands/{id}/sections', [Class_Management::class, 'getStrandSections'])->name('strands.sections');

            // Sections
            Route::get('/list_section', [Class_Management::class, 'list_section'])->name('list_section');
            Route::get('/sections/data', [Class_Management::class, 'getSectionsData'])->name('sections.data');
            Route::post('/sections/create', [Class_Management::class, 'createSection'])->name('sections.create');
            Route::put('/sections/update/{id}', [Class_Management::class, 'updateSection'])->name('sections.update');
            
            Route::get('/sections/{id}/classes', [Class_Management::class, 'getSectionClasses'])->name('sections.classes');
            Route::get('/sections/{id}/available-classes', [Class_Management::class, 'getAvailableClasses'])->name('sections.available-classes');
            Route::post('/sections/assign-class', [Class_Management::class, 'assignClassToSection'])->name('sections.assign-class');
            Route::delete('/sections/remove-class/{id}', [Class_Management::class, 'removeClassFromSection'])->name('sections-class.remove-class');
            Route::post('/sections/toggle-status', [Class_Management::class, 'toggleSectionStatus'])
                ->name('sections.toggleStatus');

            // Levels
            Route::get('/levels/data', [Class_Management::class, 'getLevelsData'])->name('levels.data');

            // Semesters
            Route::get('/get_semesters', [Class_Management::class, 'getSemesters'])->name('get_semesters');
            Route::get('/semesters/data', [Class_Management::class, 'getSemestersData'])->name('semesters.data');
        });
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

    // Logout - accessible when authenticated
    Route::post('/logout', [Login_Controller::class, 'logout_student'])->name('logout');

    // Authenticated Routes
    Route::middleware('auth:student')->group(function () {
        Route::post('/logout', [Login_Controller::class, 'logout_student'])->name('logout');

        Route::get('/dashboard', [StudentController::class, 'index'])->name('home');

        // Student Profile Routes
        Route::get('/profile', [StudentController::class, 'showProfile'])->name('profile');
        Route::get('/profile/enrollment-history', [StudentController::class, 'getEnrollmentHistory'])->name('profile.enrollment_history');
        Route::get('/profile/enrolled-classes', [StudentController::class, 'getProfileEnrolledClasses'])->name('profile.enrolled_classes');

        // Dashboard API endpoints
        Route::prefix('student/dashboard')->name('dashboard.')->group(function () {
            Route::get('/quarterly-grades', [StudentController::class, 'getQuarterlyGrades'])
                ->name('quarterly-grades');
        });

    Route::get('/gradecard', [StudentController::class, 'listGradecard'])->name('list_gradecard');
    Route::get('/gradecard/semesters', [StudentController::class, 'getGradecardSemesters']);
    Route::get('/gradecard/data', [StudentController::class, 'getGradecardData']);
    Route::get('/gradecard/summary', [StudentController::class, 'getGradecardSummary']);


        // Class Pages
        Route::get('/my_classes', [Class_List::class, 'student_class_list'])->name('list_class');
        Route::get('/my_grades', [Grade_List::class, 'student_grade_list'])->name('list_grade');

        // Get Student Grades (AJAX)
        Route::get('/my_grades/list', [Grade_List::class, 'getStudentGrades'])
            ->name('grades.list');

        // Get Class Grade Details (AJAX)
        Route::get('/grades/{classId}/{quarterId}', [Grade_List::class, 'student_grade_details'])->name('grades.details');
        Route::get('/grades/{classId}/{quarterId}/data', [Grade_List::class, 'getClassGradeDetails'])->name('grades.details.data');


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

                Route::post(
                    '/lecture/{lectureId}/mark-complete',
                    [Page_Lecture::class, 'markAsComplete']
                )
                    ->name('lecture.markComplete');
                Route::get(
                    '/lecture/{lectureId}/progress',
                    [Page_Lecture::class, 'getProgress']
                )
                    ->name('lecture.progress');

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
    Route::middleware('guest:teacher')->group(function () {
        Route::get('/login', [TeacherController::class, 'login'])->name('login');
        Route::post('/auth', [Login_Controller::class, 'auth_teacher'])->name('auth');
    });

    // Logout - accessible when authenticated
    Route::post('/logout', [Login_Controller::class, 'logout_teacher'])->name('logout');

    // Authenticated Routes
    Route::middleware('auth:teacher')->group(function () {
        Route::get('/home', [TeacherController::class, 'index'])->name('home');
        Route::post('/logout', [Login_Controller::class, 'logout_teacher'])->name('logout');

        Route::get('/profile', [TeacherController::class, 'show_profile'])->name('profile');


        Route::get('/audit/my-logs',        [TeacherAuditController::class, 'index'])->name('audit.my_logs');
        Route::get('/audit/my-logs/data',   [TeacherAuditController::class, 'getMyLogs'])->name('audit.my_logs.data');
        Route::get('/audit/my-logs/{id}',   [TeacherAuditController::class, 'getMyLogDetail'])->name('audit.my_logs.detail');

        Route::prefix('audit/student-logs')->name('audit.student_logs.')->group(function () {
            Route::get('/', [StudentAuditController::class, 'index'])->name('index');
            Route::get('/data', [StudentAuditController::class, 'getStudentLogs'])->name('data');
            Route::get('/{id}/detail', [StudentAuditController::class, 'getStudentLogDetail'])->name('detail');
        });

        // Class Pages
        Route::get('/class_list', [Class_List::class, 'teacher_class_list'])->name('list_class');
        Route::get('/adviser/grade-cards', [Teacher_Card_List::class, 'index'])
            ->name('adviser.grade.cards');

        Route::get('adviser/grades/cards', [Grade_Card::class, 'teacherCardGrades'])->name('grades.cards');
        Route::get('adviser/grades/card/{student_number}/{semester_id}', [Grade_Card::class, 'teacherViewGradeCardPage'])->name('grades.card.view');



        // Class Content Pages
        Route::prefix('class/{classId}')->name('class.')->group(function () {
            // Main Pages
            Route::get('/lessons', [Page_Lesson::class, 'teacherIndex'])->name('lessons');
            Route::get('/quizzes', [Page_Quiz::class, 'teacherIndex'])->name('quizzes');
            Route::get('/grades', [Page_Grade::class, 'teacherIndex'])->name('grades');
            Route::get('/grades/list', [Page_Grade::class, 'getTeacherGrades'])->name('grades.list');

            Route::get('/participants', [Page_Participant::class, 'teacherIndex'])->name('participants');
            Route::get('/participants/list', [Page_Participant::class, 'getParticipants'])->name('participants.list');

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

        // Route::prefix('gradebook')->name('gradebook.')->group(function() {
        //     Route::get('/{classId}/edit', [GradeBook_Management::class, 'edit_gradebook'])
        //         ->name('edit');
        //     Route::get('/{classId}/view', [GradeBook_Management::class, 'view_gradebook'])
        //         ->name('view');
        //     Route::post('/{classId}/verify-passcode', [GradeBook_Management::class, 'verify_passcode'])
        //     ->name('verify-passcode');
        //     Route::get('/{classId}/data', [GradeBook_Management::class, 'getGradebookData'])
        //         ->name('data');
        //     Route::get('/{classId}/final-grade', [GradeBook_Management::class, 'getFinalGradeData'])
        //         ->name('final-grade');
        //     Route::post('/{classId}/column/{columnId}/toggle', [GradeBook_Management::class, 'toggleColumn'])
        //         ->name('column.toggle');
        //     Route::put('/{classId}/column/{columnId}', [GradeBook_Management::class, 'updateColumn'])
        //         ->name('column.update');
        //     Route::post('/{classId}/scores/batch', [GradeBook_Management::class, 'batchUpdateScores'])
        //         ->name('scores.batch');
        //     Route::get('/{classId}/quizzes', [GradeBook_Management::class, 'getAvailableQuizzes'])
        //         ->name('quizzes');
        //     Route::post('/class/{classId}/export', [GradeBook_Management::class, 'exportGradebook'])
        //         ->name('export');
        //     // In teacher gradebook routes group
        //     Route::post('/{classId}/import', [GradeBook_Management::class, 'importGrades'])
        //         ->name('import');
        //         // In teacher gradebook routes group
        //     Route::post('/{classId}/column/{columnId}/import', [GradeBook_Management::class, 'importColumnGrades'])
        //         ->name('column.import');
        //                     // Final grades submission
        //         Route::post('/{classId}/submit-final-grades', [
        //             GradeBook_Management::class, 
        //             'submitFinalGrades'
        //         ])->name('submit-final-grades');
        //         // Check final grades status
        //         Route::get('/{classId}/check-final-status', [
        //             GradeBook_Management::class, 
        //             'checkFinalGradesStatus'
        //         ])->name('check-final-status');
        //     });


        Route::prefix('gradebook')->name('gradebook.')->group(function () {

            // View-only routes (GradebookViewController)
            Route::get('/{classId}/view', [GradebookViewController::class, 'view_gradebook'])
                ->name('view');
            Route::get('/{classId}/data', [GradebookViewController::class, 'getGradebookData'])
                ->name('data');
            Route::get('/{classId}/final-grade', [GradebookViewController::class, 'getFinalGradeData'])
                ->name('final-grade');
            Route::get('/{classId}/quizzes', [GradebookViewController::class, 'getAvailableQuizzes'])
                ->name('quizzes');

            // Edit routes (GradebookEditController)
            Route::post('/{classId}/verify-passcode', [GradebookEditController::class, 'verify_passcode'])
                ->name('verify-passcode');
            Route::get('/{classId}/edit', [GradebookEditController::class, 'edit_gradebook'])
                ->name('edit');
            Route::post('/{classId}/column/{columnId}/toggle', [GradebookEditController::class, 'toggleColumn'])
                ->name('column.toggle');
            Route::put('/{classId}/column/{columnId}', [GradebookEditController::class, 'updateColumn'])
                ->name('column.update');
            Route::post('/{classId}/scores/batch', [GradebookEditController::class, 'batchUpdateScores'])
                ->name('scores.batch');
            Route::post('/{classId}/submit-final-grades', [GradebookEditController::class, 'submitFinalGrades'])
                ->name('submit-final-grades');
            Route::get('/{classId}/check-final-status', [GradebookEditController::class, 'checkFinalGradesStatus'])
                ->name('check-final-status');

            // Import/Export routes (GradebookImportExportController)
            Route::post('/class/{classId}/export', [GradebookImportExportController::class, 'exportGradebook'])
                ->name('export');
            Route::post('/{classId}/import', [GradebookImportExportController::class, 'importGrades'])
                ->name('import');
            Route::post('/{classId}/column/{columnId}/import', [GradebookImportExportController::class, 'importColumnGrades'])
                ->name('column.import');
        });
    });

    
});


Route::prefix('guardian')->name('guardian.')->group(function () {
    // Email verification (public)
    Route::get('/verify/{token}', [GuardianEmailController::class, 'verifyEmail'])->name('verify');
    
    // Access portal with token
    Route::get('/access/{token}', [GuardianController::class, 'access'])->name('access');
    
    // Dashboard (after access granted)
    Route::get('/dashboard', [GuardianController::class, 'index'])->name('home');
    Route::get('/student/{student_number}/grades', [GuardianController::class, 'view_student_grades'])->name('student.grades');
    Route::get('/student/{student_number}/grades/data', [GuardianController::class, 'get_student_grades_data'])->name('student.grades.data');
});

Route::prefix('testdev')->group(function () {
    Route::get('/', [TestDevController::class, 'index'])->name('testdev.index');
    Route::post('/send-verification', [TestDevController::class, 'send_verification_email'])->name('testdev.send_verification');
    Route::post('/resend-verification', [TestDevController::class, 'resend_verification'])->name('testdev.resend_verification');
    Route::post('/send-guardian-email', [TestDevController::class, 'send_guardian_email'])->name('testdev.send_guardian_email');
    Route::get('/get-guardians', [TestDevController::class, 'get_guardians'])->name('testdev.get_guardians');
    Route::get('/get-guardian-students/{id}', [TestDevController::class, 'get_guardian_students']);

    Route::get('/dev-flags', [TestDevController::class, 'date'])->name('testdev.date');
    Route::post('/dev-flags/mock-time', [TestDevController::class, 'set_mock_time'])->name('testdev.mock.time');
    Route::post('/dev-flags/toggle', [TestDevController::class, 'toggle_dev_flag'])->name('testdev.toggle.date');
});
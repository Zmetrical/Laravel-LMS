<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class IrregGrade_Management extends MainController
{
    public function index()
    {
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name',
                's.code',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
            )
            ->first();

        return view('admin.grade_management.irreg_grade_view', [
            'scripts'               => ['grade_management/irreg_grade_view.js'],
            'activeSemester'        => $activeSemester,
            'activeSemesterDisplay' => $activeSemester->display_name ?? 'No Active Semester',
        ]);
    }

    // =========================================================================
    // GET IRREGULAR STUDENTS LIST
    // =========================================================================

    /**
     * Get all irregular students enrolled in the active semester
     * with their overall grade submission progress.
     */
    public function getIrregStudents()
    {
        try {
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            // All irregular students that have at least one class enrolled this semester
            $students = DB::table('students as s')
                ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_type', 'irregular')
                ->where('scm.semester_id', $activeSemester->id)
                ->where('scm.enrollment_status', 'enrolled')
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.gender',
                    's.section_id',
                    'sec.name as section_name',
                    'str.name as strand_name',
                    'str.code as strand_code',
                    'lvl.name as level_name',
                    DB::raw('COUNT(DISTINCT scm.class_code) as total_classes')
                )
                ->groupBy(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.gender',
                    's.section_id',
                    'sec.name',
                    'str.name',
                    'str.code',
                    'lvl.name'
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'students' => [],
                    'semester'  => $activeSemester,
                ]);
            }

            // Get submitted grade counts per student for this semester
            $studentNumbers = $students->pluck('student_number')->toArray();

            // Only count class_codes that belong to their enrolled classes this semester
            $submittedCounts = DB::table('grades_final as gf')
                ->join('student_class_matrix as scm', function ($join) use ($activeSemester) {
                    $join->on('gf.student_number', '=', 'scm.student_number')
                         ->on('gf.class_code', '=', 'scm.class_code')
                         ->where('scm.semester_id', '=', $activeSemester->id)
                         ->where('scm.enrollment_status', '=', 'enrolled');
                })
                ->where('gf.semester_id', $activeSemester->id)
                ->whereIn('gf.student_number', $studentNumbers)
                ->select('gf.student_number', DB::raw('COUNT(DISTINCT gf.class_code) as submitted_count'))
                ->groupBy('gf.student_number')
                ->pluck('submitted_count', 'student_number');

            $students = $students->map(function ($student) use ($submittedCounts) {
                $submitted = $submittedCounts[$student->student_number] ?? 0;
                $student->submitted_count      = (int) $submitted;
                $student->submission_percentage = $student->total_classes > 0
                    ? round(($submitted / $student->total_classes) * 100, 1)
                    : 0;
                return $student;
            })->values();

            return response()->json([
                'success'  => true,
                'students' => $students,
                'semester' => $activeSemester,
            ]);

        } catch (Exception $e) {
            \Log::error('IrregGrade: failed to get students', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load students'
            ], 500);
        }
    }

    // =========================================================================
    // GET STUDENT GRADE DETAILS
    // =========================================================================

    /**
     * Get a single irregular student's enrolled classes and grade submission
     * status for the active semester.
     */
    public function getStudentGradeDetails($studentNumber)
    {
        try {
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            // Student info
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_number', $studentNumber)
                ->where('s.student_type', 'irregular')
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.gender',
                    'sec.name as section_name',
                    'str.name as strand_name',
                    'str.code as strand_code',
                    'lvl.name as level_name'
                )
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // Enrolled classes with teacher info
            $classes = DB::table('student_class_matrix as scm')
                ->join('classes as c', 'scm.class_code', '=', 'c.class_code')
                ->leftJoin('teacher_class_matrix as tcm', function ($join) use ($activeSemester) {
                    $join->on('c.id', '=', 'tcm.class_id')
                         ->where('tcm.semester_id', '=', $activeSemester->id);
                })
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->leftJoin('grades_final as gf', function ($join) use ($activeSemester) {
                    $join->on('scm.class_code', '=', 'gf.class_code')
                         ->on('scm.student_number', '=', 'gf.student_number')
                         ->where('gf.semester_id', '=', $activeSemester->id);
                })
                ->where('scm.student_number', $studentNumber)
                ->where('scm.semester_id', $activeSemester->id)
                ->where('scm.enrollment_status', 'enrolled')
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    'c.class_category',
                    DB::raw('GROUP_CONCAT(DISTINCT CONCAT(t.first_name, " ", t.last_name) SEPARATOR ", ") as teachers'),
                    DB::raw('MAX(CASE WHEN gf.id IS NOT NULL THEN 1 ELSE 0 END) as is_submitted'),
                    DB::raw('MAX(gf.final_grade) as final_grade'),
                    DB::raw('MAX(gf.remarks) as remarks'),
                    DB::raw('MAX(gf.computed_at) as submitted_at')
                )
                ->groupBy('c.id', 'c.class_code', 'c.class_name', 'c.class_category')
                ->orderBy('c.class_name')
                ->get()
                ->map(function ($class) {
                    $class->is_submitted = (bool) $class->is_submitted;
                    return $class;
                });

            $totalClasses    = $classes->count();
            $submittedCount  = $classes->where('is_submitted', true)->count();

            return response()->json([
                'success'          => true,
                'student'          => $student,
                'classes'          => $classes,
                'total_classes'    => $totalClasses,
                'submitted_count'  => $submittedCount,
            ]);

        } catch (Exception $e) {
            \Log::error('IrregGrade: failed to get student details', [
                'student_number' => $studentNumber,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load student details'
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeTransmutation;

class Grade_Card extends MainController
{
    public function card_grades()
    {
        // Get semesters
        $semesters = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->select(
                's.id',
                's.name',
                's.code',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name"),
                's.status'
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('s.code', 'asc')
            ->get();

        $activeSemester = $semesters->where('status', 'active')->first();

        // Get sections
        $sections = DB::table('sections as sec')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->select(
                'sec.id',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name'
            )
            ->where('sec.status', 1)
            ->orderBy('sec.code')
            ->get();

        // Get grade cards - one record per student per semester
        $gradeCards = DB::table('grades_final as gf')
            ->select(
                'gf.student_number',
                'gf.semester_id',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name',
                DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                DB::raw("COUNT(gf.id) as total_subjects"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'PASSED' THEN 1 ELSE 0 END) as passed_count"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'FAILED' THEN 1 ELSE 0 END) as failed_count"),
                DB::raw("ROUND(AVG(gf.final_grade), 2) as general_average"),
                DB::raw("MAX(gf.computed_at) as last_computed")
            )
            ->join('students as s', 'gf.student_number', '=', 's.student_number')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->groupBy(
                'gf.student_number',
                'gf.semester_id',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code',
                'sec.name',
                'str.code',
                'lvl.name',
                'semester_display'
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.code', 'asc')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $data = [
            'scripts' => ['grade_management/card_grades.js'],
            'semesters' => $semesters,
            'activeSemester' => $activeSemester,
            'sections' => $sections,
            'gradeCards' => $gradeCards
        ];

        return view('admin.grade_management.card_grades', $data);
    }

    /**
     * Teacher view - Filter grade cards by teacher's assigned classes
     */
    public function teacherCardGrades()
    {
        $teacherId = Auth::guard('teacher')->id();

        // Get semesters
        $semesters = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->select(
                's.id',
                's.name',
                's.code',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name"),
                's.status'
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('s.code', 'asc')
            ->get();

        $activeSemester = $semesters->where('status', 'active')->first();

        // Get sections that have classes taught by this teacher
        $sections = DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->join('teacher_class_matrix as tcm', 'scm.class_id', '=', 'tcm.class_id')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->where('tcm.teacher_id', $teacherId)
            ->where('sec.status', 1)
            ->select(
                'sec.id',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name'
            )
            ->distinct()
            ->orderBy('sec.code')
            ->get();

        // Get grade cards - only for students in teacher's classes
        $gradeCards = DB::table('grades_final as gf')
            ->join('students as s', 'gf.student_number', '=', 's.student_number')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
            ->join('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->where('tcm.teacher_id', $teacherId)
            ->select(
                'gf.student_number',
                'gf.semester_id',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name',
                DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                DB::raw("COUNT(DISTINCT gf.id) as total_subjects"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'PASSED' THEN 1 ELSE 0 END) as passed_count"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'FAILED' THEN 1 ELSE 0 END) as failed_count"),
                DB::raw("ROUND(AVG(gf.final_grade), 2) as general_average"),
                DB::raw("MAX(gf.computed_at) as last_computed")
            )
            ->groupBy(
                'gf.student_number',
                'gf.semester_id',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code',
                'sec.name',
                'str.code',
                'lvl.name',
                'semester_display'
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.code', 'asc')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $data = [
            'scripts' => ['teacher/teacher_view_card.js'],
            'semesters' => $semesters,
            'activeSemester' => $activeSemester,
            'sections' => $sections,
            'gradeCards' => $gradeCards
        ];

        return view('teacher.teacher_view_card', $data);
    }

    public function getGradeCard(Request $request)
    {
        try {
            $studentNumber = $request->student_number;
            $semesterId = $request->semester_id;

            // Get student info
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_number', $studentNumber)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.student_type',
                    's.section_id',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found.'
                ], 404);
            }

            // Get semester info
            $semester = DB::table('semesters as sem')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->where('sem.id', $semesterId)
                ->select(
                    'sem.id',
                    'sem.name',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as display_name")
                )
                ->first();

            // Get all enrolled subjects with grades
            $grades = $this->getEnrolledSubjects($studentNumber, $semesterId, $student->student_type);

            // Calculate statistics
            $gradesWithFinal = $grades->filter(function($grade) {
                return !is_null($grade->final_grade);
            });

            $totalSubjects = $grades->count();
            $passedCount = $gradesWithFinal->where('remarks', 'PASSED')->count();
            $failedCount = $gradesWithFinal->where('remarks', 'FAILED')->count();
            $generalAverage = $gradesWithFinal->avg('final_grade');

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'semester' => $semester,
                    'grades' => $grades,
                    'statistics' => [
                        'total_subjects' => $totalSubjects,
                        'passed_count' => $passedCount,
                        'failed_count' => $failedCount,
                        'general_average' => $generalAverage ? round($generalAverage, 2) : 0
                    ]
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get grade card', [
                'student_number' => $studentNumber,
                'semester_id' => $semesterId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade card.'
            ], 500);
        }
    }

    public function viewGradeCardPage($studentNumber, $semesterId)
    {
        try {
            // Get student info
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_number', $studentNumber)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.gender',
                    's.student_type',
                    's.section_id',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->first();

            if (!$student) {
                abort(404, 'Student not found');
            }

            // Get semester info with school year
            $semester = DB::table('semesters as sem')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->where('sem.id', $semesterId)
                ->select(
                    'sem.id',
                    'sem.name',
                    'sem.code',
                    'sy.id as school_year_id',
                    'sy.code as school_year_code',
                    'sy.year_start',
                    'sy.year_end',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as display_name")
                )
                ->first();

            if (!$semester) {
                abort(404, 'Semester not found');
            }

            // Get all enrolled subjects (with or without grades)
            $enrolled_subjects = $this->getEnrolledSubjects($studentNumber, $semesterId, $student->student_type);

            // Get adviser name for regular students from section_adviser_matrix
            $adviser_name = null;
            if ($student->student_type === 'regular' && $student->section_id) {
                $adviser_name = $this->getAdviserName($student->section_id, $semesterId);
            }

            // Calculate statistics from subjects that have final grades
            $gradesWithFinal = $enrolled_subjects->filter(function($subject) {
                return !is_null($subject->final_grade);
            });

            $totalSubjects = $enrolled_subjects->count();
            $passedCount = $gradesWithFinal->where('remarks', 'PASSED')->count();
            $failedCount = $gradesWithFinal->where('remarks', 'FAILED')->count();
            $generalAverage = $gradesWithFinal->avg('final_grade');

            $statistics = [
                'total_subjects' => $totalSubjects,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'general_average' => $generalAverage ? round($generalAverage, 2) : 0
            ];

            $data = [
                'student' => $student,
                'semester' => $semester,
                'enrolled_subjects' => $enrolled_subjects,
                'adviser_name' => $adviser_name,
                'statistics' => $statistics
            ];

            return view('admin.grade_management.view_card', $data);
        } catch (Exception $e) {
            \Log::error('Failed to load grade card page', [
                'student_number' => $studentNumber,
                'semester_id' => $semesterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Failed to load grade card');
        }
    }

    /**
     * Teacher-specific grade card view page
     */
    public function teacherViewGradeCardPage($studentNumber, $semesterId)
    {
        try {
            $teacherId = Auth::guard('teacher')->id();

            // Get student info
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_number', $studentNumber)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.gender',
                    's.student_type',
                    's.section_id',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->first();

            if (!$student) {
                abort(404, 'Student not found');
            }

            // Verify teacher has access to this student (check if teacher teaches any of the student's subjects)
            if ($student->student_type === 'regular') {
                // For regular students, check via section_class_matrix
                $hasAccess = DB::table('section_class_matrix as scm')
                    ->join('teacher_class_matrix as tcm', function($join) use ($semesterId) {
                        $join->on('scm.class_id', '=', 'tcm.class_id')
                            ->where('tcm.semester_id', '=', $semesterId);
                    })
                    ->where('scm.section_id', $student->section_id)
                    ->where('scm.semester_id', $semesterId)
                    ->where('tcm.teacher_id', $teacherId)
                    ->exists();
            } else {
                // For irregular students, check via student_class_matrix
                $hasAccess = DB::table('student_class_matrix as stcm')
                    ->join('classes as c', 'stcm.class_code', '=', 'c.class_code')
                    ->join('teacher_class_matrix as tcm', function($join) use ($semesterId) {
                        $join->on('c.id', '=', 'tcm.class_id')
                            ->where('tcm.semester_id', '=', $semesterId);
                    })
                    ->where('stcm.student_number', $studentNumber)
                    ->where('stcm.semester_id', $semesterId)
                    ->where('tcm.teacher_id', $teacherId)
                    ->exists();
            }

            if (!$hasAccess) {
                abort(403, 'Unauthorized access');
            }

            // Get semester info with school year
            $semester = DB::table('semesters as sem')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->where('sem.id', $semesterId)
                ->select(
                    'sem.id',
                    'sem.name',
                    'sem.code',
                    'sy.id as school_year_id',
                    'sy.code as school_year_code',
                    'sy.year_start',
                    'sy.year_end',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as display_name")
                )
                ->first();

            if (!$semester) {
                abort(404, 'Semester not found');
            }

            // Get all enrolled subjects (with or without grades)
            $enrolled_subjects = $this->getEnrolledSubjects($studentNumber, $semesterId, $student->student_type);

            // Get adviser name for regular students from section_adviser_matrix
            $adviser_name = null;
            if ($student->student_type === 'regular' && $student->section_id) {
                $adviser_name = $this->getAdviserName($student->section_id, $semesterId);
            }

            // Calculate statistics from subjects that have final grades
            $gradesWithFinal = $enrolled_subjects->filter(function($subject) {
                return !is_null($subject->final_grade);
            });

            $totalSubjects = $enrolled_subjects->count();
            $passedCount = $gradesWithFinal->where('remarks', 'PASSED')->count();
            $failedCount = $gradesWithFinal->where('remarks', 'FAILED')->count();
            $generalAverage = $gradesWithFinal->avg('final_grade');

            $statistics = [
                'total_subjects' => $totalSubjects,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'general_average' => $generalAverage ? round($generalAverage, 2) : 0
            ];

            $data = [
                'student' => $student,
                'semester' => $semester,
                'enrolled_subjects' => $enrolled_subjects,
                'adviser_name' => $adviser_name,
                'statistics' => $statistics
            ];

            return view('teacher.teacher_view_card', $data);
        } catch (Exception $e) {
            \Log::error('Failed to load teacher grade card page', [
                'teacher_id' => $teacherId ?? null,
                'student_number' => $studentNumber,
                'semester_id' => $semesterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Failed to load grade card');
        }
    }

    /**
     * Get all enrolled subjects for a student in a semester
     */
    private function getEnrolledSubjects($studentNumber, $semesterId, $studentType)
    {
        if ($studentType === 'regular') {
            // Get subjects from section_class_matrix for regular students
            return DB::table('students as s')
                ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('grades_final as gf', function($join) use ($studentNumber, $semesterId) {
                    $join->on('gf.class_code', '=', 'c.class_code')
                        ->where('gf.student_number', '=', $studentNumber)
                        ->where('gf.semester_id', '=', $semesterId);
                })
                ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
                ->where('s.student_number', $studentNumber)
                ->where('scm.semester_id', $semesterId)
                ->select(
                    'c.id as class_id',
                    'c.class_code',
                    'c.class_name',
                    'c.class_category',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks',
                    'gf.computed_at',
                    DB::raw("CONCAT(t.first_name, ' ', COALESCE(t.last_name, '')) as teacher_name")
                )
                ->orderBy('c.class_category')
                ->orderBy('c.class_code')
                ->get();
        } else {
            // Get subjects from student_class_matrix for irregular students
            return DB::table('student_class_matrix as stcm')
                ->join('classes as c', 'stcm.class_code', '=', 'c.class_code')
                ->leftJoin('grades_final as gf', function($join) use ($studentNumber, $semesterId) {
                    $join->on('gf.class_code', '=', 'c.class_code')
                        ->where('gf.student_number', '=', $studentNumber)
                        ->where('gf.semester_id', '=', $semesterId);
                })
                ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
                ->where('stcm.student_number', $studentNumber)
                ->where('stcm.semester_id', $semesterId)
                ->where('stcm.enrollment_status', 'enrolled')
                ->select(
                    'c.id as class_id',
                    'c.class_code',
                    'c.class_name',
                    'c.class_category',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks',
                    'gf.computed_at',
                    DB::raw("CONCAT(t.first_name, ' ', COALESCE(t.last_name, '')) as teacher_name")
                )
                ->orderBy('c.class_category')
                ->orderBy('c.class_code')
                ->get();
        }
    }

    /**
     * Get adviser name for a section from section_adviser_matrix
     */
    private function getAdviserName($sectionId, $semesterId)
    {
        $adviser = DB::table('section_adviser_matrix as sam')
            ->join('teachers as t', 'sam.teacher_id', '=', 't.id')
            ->where('sam.section_id', $sectionId)
            ->where('sam.semester_id', $semesterId)
            ->where('t.status', 1)
            ->select(
                't.first_name',
                't.middle_name',
                't.last_name'
            )
            ->first();

        if ($adviser) {
            $middleInitial = $adviser->middle_name ? strtoupper(substr($adviser->middle_name, 0, 1)) . '.' : '';
            return strtoupper(trim($adviser->first_name . ' ' . $middleInitial . ' ' . $adviser->last_name));
        }

        return null;
    }

/**
 * Admin evaluation summary page
 */
public function evaluation($student_number)
{
    $student = DB::table('students')
        ->where('student_number', $student_number)
        ->first();

    if (!$student) {
        abort(404, 'Student not found');
    }

    return view('admin.grade_management.view_evaluation', compact('student'));
}

/**
 * Get evaluation data for admin
 */
public function getEvaluationData($student_number)
{
    $summary = DB::table('grades_final as gf')
        ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
        ->join('semesters as s', 'gf.semester_id', '=', 's.id')
        ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
        ->where('gf.student_number', $student_number)
        ->select(
            'c.class_name',
            'c.class_category',
            's.id as semester_id',
            's.name as semester_name',
            's.status as semester_status',
            'sy.code as school_year_code',
            'sy.year_start',
            's.code as semester_code',
            'gf.q1_grade',
            'gf.q2_grade',
            'gf.final_grade',
            'gf.remarks',
            DB::raw("CONCAT(s.name, ' - SY ', sy.code) as full_semester")
        )
        ->orderBy('sy.year_start', 'desc')
        ->orderBy('s.code', 'desc')
        ->orderBy('c.class_category')
        ->orderBy('c.class_name')
        ->get();

    return response()->json([
        'summary' => $summary
    ]);
}

}
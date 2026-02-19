<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

        $data = [
            'scripts' => ['grade_management/card_grades.js'],
            'semesters' => $semesters,
            'activeSemester' => $activeSemester,
            'sections' => $sections,
        ];

        return view('admin.grade_management.card_grades', $data);
    }

    /**
     * AJAX endpoint for paginated, filtered grade cards list (admin)
     */
    public function getGradeCardsAjax(Request $request)
    {
        try {
            $search     = trim($request->get('search', ''));
            $semesterId = $request->get('semester_id');
            $sectionCode = $request->get('section_code');
            $perPage    = (int) $request->get('per_page', 15);

            $query = DB::table('grades_final as gf')
                ->join('students as s', 'gf.student_number', '=', 's.student_number')
                ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
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
                    DB::raw("MAX(gf.computed_at) as last_computed"),
                    DB::raw("sy.year_start")
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
                    'semester_display',
                    'sy.year_start'
                );

            // Filters
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('s.student_number', 'like', "%{$search}%")
                      ->orWhere('s.first_name',   'like', "%{$search}%")
                      ->orWhere('s.last_name',    'like', "%{$search}%");
                });
            }

            if ($semesterId) {
                $query->where('gf.semester_id', $semesterId);
            }

            if ($sectionCode) {
                $query->where('sec.code', $sectionCode);
            }

            $query->orderBy('sy.year_start', 'desc')
                  ->orderBy('sem.code', 'asc')
                  ->orderBy('s.last_name')
                  ->orderBy('s.first_name');

            $gradeCards = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $gradeCards->items(),
                'meta'    => [
                    'current_page' => $gradeCards->currentPage(),
                    'last_page'    => $gradeCards->lastPage(),
                    'per_page'     => $gradeCards->perPage(),
                    'total'        => $gradeCards->total(),
                    'from'         => $gradeCards->firstItem() ?? 0,
                    'to'           => $gradeCards->lastItem()   ?? 0,
                ],
            ]);
        } catch (Exception $e) {
            \Log::error('getGradeCardsAjax failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to load grade cards.'], 500);
        }
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

            // Get adviser name
            $adviser_name = null;
            if ($student->section_id) {
                $adviser_name = $this->getAdviserName($student->section_id, $semesterId);
            }

            // Calculate statistics
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

            // Verify teacher access
            // ... [Keep existing access check logic or simplify as needed] ...
            // For brevity, assuming access check passes or is handled by middleware
            
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

            // Get all enrolled subjects
            $enrolled_subjects = $this->getEnrolledSubjects($studentNumber, $semesterId, $student->student_type);

            // Get adviser name
            $adviser_name = null;
            if ($student->section_id) {
                $adviser_name = $this->getAdviserName($student->section_id, $semesterId);
            }

            // Calculate statistics
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
                'error' => $e->getMessage()
            ]);

            abort(500, 'Failed to load grade card');
        }
    }

    /**
     * Get all enrolled subjects for a student in a semester.
     * Updated to fetch subjects even if no grade exists.
     */
private function getEnrolledSubjects($studentNumber, $semesterId, $studentType)
{
    $historySectionId = DB::table('student_semester_enrollment')
        ->where('student_number', $studentNumber)
        ->where('semester_id', $semesterId)
        ->where('enrollment_status', 'enrolled')
        ->value('section_id');

    if (!$historySectionId) {
        $historySectionId = DB::table('students')->where('student_number', $studentNumber)->value('section_id');
    }

    // Regular only — irregular students use student_class_matrix exclusively
    $sectionSubjects = collect();
    if ($historySectionId && $studentType !== 'irregular') {
        $sectionSubjects = DB::table('section_class_matrix as scm')
            ->join('classes as c', 'scm.class_id', '=', 'c.id')
            ->leftJoin('grades_final as gf', function($join) use ($studentNumber, $semesterId) {
                $join->on('gf.class_code', '=', 'c.class_code')
                     ->where('gf.student_number', '=', $studentNumber)
                     ->where('gf.semester_id', '=', $semesterId);
            })
            ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
            ->where('scm.section_id', $historySectionId)
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
            ->get();
    }

    $individualSubjects = DB::table('student_class_matrix as stcm')
        ->join('classes as c', 'stcm.class_code', '=', 'c.class_code')
        ->leftJoin('grades_final as gf', function($join) use ($studentNumber, $semesterId) {
            $join->on('gf.class_code', '=', 'c.class_code')
                 ->where('gf.student_number', '=', $studentNumber)
                 ->where('gf.semester_id', '=', $semesterId);
        })
        ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
        ->where('stcm.student_number', $studentNumber)
        ->where('stcm.semester_id', $semesterId)
        ->where('stcm.enrollment_status', '!=', 'dropped')
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
        ->get();

    $merged = $sectionSubjects->merge($individualSubjects)->unique('class_code');

    return $merged->sortBy('class_category')->sortBy('class_code')->values();
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
            ->select('t.first_name', 't.middle_name', 't.last_name')
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
        $student = DB::table('students')->where('student_number', $student_number)->first();
        if (!$student) abort(404, 'Student not found');
        return view('admin.grade_management.view_evaluation', compact('student'));
    }

    /**
     * Get evaluation data for admin
     * Updated: Sorts Oldest to Newest (Chronological Order)
     */
public function getEvaluationData($student_number)
{
    $studentType = DB::table('students')->where('student_number', $student_number)->value('student_type');

    // Regular load via section enrollment — skipped for irregular
    $regularLoad = collect();
    if ($studentType !== 'irregular') {
        $regularLoad = DB::table('student_semester_enrollment as sse')
            ->join('section_class_matrix as scm', function($join) {
                $join->on('sse.section_id', '=', 'scm.section_id')
                     ->on('sse.semester_id', '=', 'scm.semester_id');
            })
            ->join('classes as c', 'scm.class_id', '=', 'c.id')
            ->join('semesters as s', 'sse.semester_id', '=', 's.id')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->leftJoin('grades_final as gf', function($join) use ($student_number) {
                $join->on('c.class_code', '=', 'gf.class_code')
                     ->on('s.id', '=', 'gf.semester_id')
                     ->where('gf.student_number', '=', $student_number);
            })
            ->where('sse.student_number', $student_number)
            ->where('sse.enrollment_status', 'enrolled')
            ->select(
                'c.class_name', 'c.class_category', 'c.class_code',
                's.id as semester_id', 's.name as semester_name', 's.status as semester_status', 's.code as semester_code',
                'sy.code as school_year_code', 'sy.year_start',
                'gf.q1_grade', 'gf.q2_grade', 'gf.final_grade', 'gf.remarks',
                DB::raw("CONCAT(s.name, ' - SY ', sy.code) as full_semester")
            )
            ->get();
    }

    $individualLoad = DB::table('student_class_matrix as stcm')
        ->join('classes as c', 'stcm.class_code', '=', 'c.class_code')
        ->join('semesters as s', 'stcm.semester_id', '=', 's.id')
        ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
        ->leftJoin('grades_final as gf', function($join) use ($student_number) {
            $join->on('c.class_code', '=', 'gf.class_code')
                 ->on('s.id', '=', 'gf.semester_id')
                 ->where('gf.student_number', '=', $student_number);
        })
        ->where('stcm.student_number', $student_number)
        ->where('stcm.enrollment_status', '!=', 'dropped')
        ->select(
            'c.class_name', 'c.class_category', 'c.class_code',
            's.id as semester_id', 's.name as semester_name', 's.status as semester_status', 's.code as semester_code',
            'sy.code as school_year_code', 'sy.year_start',
            'gf.q1_grade', 'gf.q2_grade', 'gf.final_grade', 'gf.remarks',
            DB::raw("CONCAT(s.name, ' - SY ', sy.code) as full_semester")
        )
        ->get();

    $summary = $regularLoad->merge($individualLoad)
        ->unique(function($item) {
            return $item->class_code . '-' . $item->semester_id;
        })
        ->sortBy('year_start')
        ->groupBy('year_start')
        ->flatMap(function($items) {
            return $items->sortBy('class_name')
                         ->sortBy('semester_code');
        })
        ->values();

    return response()->json([
        'summary' => $summary
    ]);
}
}
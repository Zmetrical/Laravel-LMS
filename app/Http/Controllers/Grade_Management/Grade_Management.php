<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class Grade_Management extends MainController
{
    public function list_grades() 
    {
        $data = [
            'scripts' => ['grade_management/list_grades.js'],
        ];

        return view('admin.grade_management.list_grades', $data);
    }

    /**
     * Get all classes for filter dropdown
     */
    public function getClassesForFilter()
    {
        try {
            $classes = DB::table('classes')
                ->select('id', 'class_code', 'class_name')
                ->orderBy('class_code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get classes for filter', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes.'
            ], 500);
        }
    }

    /**
     * Search and get student grades - now includes students without final grades
     */
    public function searchGrades(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'class_code' => 'nullable|string|exists:classes,class_code',
                'status_filter' => 'nullable|in:all,passed,failed,inc,drp,w,no_grade'
            ]);

            // Get active semester - check multiple sources
            $activeSemester = null;
            
            // First try from view shared data
            if (view()->shared('active_semester')) {
                $activeSemester = view()->shared('active_semester');
            }
            // Then try session
            elseif (session()->has('active_semester')) {
                $activeSemester = session('active_semester');
            }
            // Finally query database
            else {
                $activeSemester = DB::table('semesters')
                    ->where('status', 'active')
                    ->first();
            }
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found. Please set an active semester first.'
                ], 400);
            }

            $semesterId = is_object($activeSemester) ? $activeSemester->id : $activeSemester['id'];

            // Start with student_class_matrix to get all enrolled students
            $query = DB::table('student_class_matrix as scm')
                ->join('students as s', function ($join) {
                    $join->on(
                        DB::raw('scm.student_number COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('s.student_number COLLATE utf8mb4_general_ci')
                    );
                })
                ->join('classes as c', function ($join) {
                    $join->on(
                        DB::raw('scm.class_code COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('c.class_code COLLATE utf8mb4_general_ci')
                    );
                })
                ->leftJoin('grades_final as gf', function ($join) use ($semesterId) {
                    $join->on(
                        DB::raw('scm.student_number COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('gf.student_number COLLATE utf8mb4_general_ci')
                    )
                    ->on(
                        DB::raw('scm.class_code COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('gf.class_code COLLATE utf8mb4_general_ci')
                    )
                    ->where('gf.semester_id', '=', $semesterId);
                })
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->leftJoin('admins as comp', 'gf.computed_by', '=', 'comp.id')
                ->where('scm.semester_id', $semesterId)
                ->where('scm.enrollment_status', 'enrolled');

            // Filter by class if specified
            if ($request->filled('class_code')) {
                $query->where('scm.class_code', $request->class_code);
            }

            // Search by student name or number
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('s.student_number', 'LIKE', "%{$search}%")
                        ->orWhere(DB::raw("CONCAT(s.first_name, ' ', s.last_name)"), 'LIKE', "%{$search}%")
                        ->orWhere(DB::raw("CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name)"), 'LIKE', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->filled('status_filter') && $request->status_filter !== 'all') {
                if ($request->status_filter === 'no_grade') {
                    $query->whereNull('gf.id');
                } else {
                    $query->where('gf.remarks', strtoupper($request->status_filter));
                }
            }

            $grades = $query->select(
                'gf.id as grade_id',
                'scm.student_number',
                'scm.class_code',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'c.class_name',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name',
                'gf.ww_score',
                'gf.ww_percentage',
                'gf.pt_score',
                'gf.pt_percentage',
                'gf.qa_score',
                'gf.qa_percentage',
                'gf.final_grade',
                'gf.remarks',
                'gf.is_locked',
                'gf.computed_at',
                'comp.admin_name as computed_by_name'
            )
            ->orderBy('c.class_code')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

            // Add full name and check if grade exists
            $grades = $grades->map(function ($grade) {
                $grade->full_name = trim($grade->first_name . ' ' . 
                                        ($grade->middle_name ? substr($grade->middle_name, 0, 1) . '. ' : '') . 
                                        $grade->last_name);
                $grade->has_grade = !is_null($grade->grade_id);
                return $grade;
            });

            // Calculate statistics
            $gradesWithFinal = $grades->where('has_grade', true);
            $stats = [
                'total_enrolled' => $grades->count(),
                'with_grades' => $gradesWithFinal->count(),
                'without_grades' => $grades->where('has_grade', false)->count(),
                'passed' => $gradesWithFinal->where('remarks', 'PASSED')->count(),
                'failed' => $gradesWithFinal->where('remarks', 'FAILED')->count(),
                'inc' => $gradesWithFinal->where('remarks', 'INC')->count(),
                'drp' => $gradesWithFinal->where('remarks', 'DRP')->count(),
                'w' => $gradesWithFinal->where('remarks', 'W')->count(),
                'locked' => $gradesWithFinal->where('is_locked', 1)->count(),
                'average_grade' => $gradesWithFinal->count() > 0 ? 
                    round($gradesWithFinal->avg('final_grade'), 2) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => $grades,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to search grades', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search grades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get grade details for a specific student and class
     */
    public function getGradeDetails($gradeId)
    {
        try {
            $grade = DB::table('grades_final as gf')
                ->join('students as s', function ($join) {
                    $join->on(
                        DB::raw('gf.student_number COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('s.student_number COLLATE utf8mb4_general_ci')
                    );
                })
                ->join('classes as c', function ($join) {
                    $join->on(
                        DB::raw('gf.class_code COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('c.class_code COLLATE utf8mb4_general_ci')
                    );
                })
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->leftJoin('admins as comp', 'gf.computed_by', '=', 'comp.id')
                ->where('gf.id', $gradeId)
                ->select(
                    'gf.*',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.student_type',
                    'c.class_name',
                    'c.ww_perc',
                    'c.pt_perc',
                    'c.qa_perce',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name',
                    'comp.admin_name as computed_by_name'
                )
                ->first();

            if (!$grade) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade record not found.'
                ], 404);
            }

            // Get component breakdown
            $components = DB::table('grade_components')
                ->where('student_number', $grade->student_number)
                ->where('class_code', $grade->class_code)
                ->where('semester_id', $grade->semester_id)
                ->orderBy('component_type')
                ->orderBy('created_at')
                ->get();

            $grade->full_name = trim($grade->first_name . ' ' . 
                                    ($grade->middle_name ? substr($grade->middle_name, 0, 1) . '. ' : '') . 
                                    $grade->last_name);

            return response()->json([
                'success' => true,
                'data' => $grade,
                'components' => $components
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get grade details', [
                'grade_id' => $gradeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade details.'
            ], 500);
        }
    }
}
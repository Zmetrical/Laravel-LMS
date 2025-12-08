<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentController extends Controller
{
    public function index()
    {
        $student = Auth::guard('student')->user();
        
        // Get active semester
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                'sy.year_start',
                'sy.year_end'
            )
            ->first();
        
        $activeSemesterDisplay = null;
        if ($activeSemester) {
            $activeSemesterDisplay = $activeSemester->year_start . '-' . 
                $activeSemester->year_end . ' - ' . 
                $activeSemester->semester_name;
        }
        
        $data = [
            'scripts' => ['student/dashboard.js'],
            'activeSemester' => $activeSemester,
            'activeSemesterDisplay' => $activeSemesterDisplay
        ];

        return view('student.dashboard', $data);
    }

    public function login()
    {
        $data = [
            'scripts' => ['student/login.js'],
        ];

        return view('student.login', $data);
    }

   /**
     * Get quarterly grades for current semester
     * Shows both finalized grades from quarter_grades AND real-time calculations
     */
    public function getQuarterlyGrades()
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Get active semester
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'quarters' => []
                ]);
            }
            
            // Get quarters for this semester
            $quarters = DB::table('quarters')
                ->where('semester_id', $activeSemester->id)
                ->orderBy('order_number')
                ->get();
            
            if ($quarters->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'quarters' => []
                ]);
            }
            
            // Get enrolled classes
            $enrolledClasses = $this->getEnrolledClasses($student, $activeSemester->id);
            
            if ($enrolledClasses->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'quarters' => $quarters->map(function($q) {
                        return ['name' => $q->name, 'code' => $q->code];
                    })
                ]);
            }
            
            $gradesData = [];
            
            foreach ($enrolledClasses as $class) {
                $classGrades = [
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'quarters' => []
                ];
                
                foreach ($quarters as $quarter) {
                    // Check if quarter grade exists (finalized or not)
                    $quarterGrade = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarter->id)
                        ->first();
                    
                    if ($quarterGrade) {
                        // Use existing quarter grade (finalized or computed)
                        $classGrades['quarters'][] = [
                            'quarter_name' => $quarter->name,
                            'quarter_code' => $quarter->code,
                            'initial_grade' => $quarterGrade->initial_grade,
                            'transmuted_grade' => $quarterGrade->transmuted_grade,
                            'ww_ws' => $quarterGrade->ww_ws,
                            'pt_ws' => $quarterGrade->pt_ws,
                            'qa_ws' => $quarterGrade->qa_ws,
                            'is_locked' => (bool)$quarterGrade->is_locked
                        ];
                    } else {
                        // No grade record yet
                        $classGrades['quarters'][] = [
                            'quarter_name' => $quarter->name,
                            'quarter_code' => $quarter->code,
                            'initial_grade' => null,
                            'transmuted_grade' => null,
                            'ww_ws' => null,
                            'pt_ws' => null,
                            'qa_ws' => null,
                            'is_locked' => false
                        ];
                    }
                }
                
                // Get semester final grade
                $finalGrade = DB::table('grades_final')
                    ->where('student_number', $student->student_number)
                    ->where('class_code', $class->class_code)
                    ->where('semester_id', $activeSemester->id)
                    ->first();
                
                if ($finalGrade) {
                    $classGrades['semester_final'] = [
                        'q1_grade' => $finalGrade->q1_grade,
                        'q2_grade' => $finalGrade->q2_grade,
                        'final_grade' => $finalGrade->final_grade,
                        'remarks' => $finalGrade->remarks,
                        'is_locked' => true
                    ];
                } else {
                    // Calculate semester average from quarter grades if available
                    $q1Grade = isset($classGrades['quarters'][0]) ? $classGrades['quarters'][0]['transmuted_grade'] : null;
                    $q2Grade = isset($classGrades['quarters'][1]) ? $classGrades['quarters'][1]['transmuted_grade'] : null;
                    
                    $semesterFinal = null;
                    $remarks = null;
                    
                    if ($q1Grade !== null && $q2Grade !== null) {
                        $semesterFinal = round(($q1Grade + $q2Grade) / 2, 2);
                        $remarks = $semesterFinal >= 75 ? 'PASSED' : 'FAILED';
                    }
                    
                    $classGrades['semester_final'] = [
                        'q1_grade' => $q1Grade,
                        'q2_grade' => $q2Grade,
                        'final_grade' => $semesterFinal,
                        'remarks' => $remarks,
                        'is_locked' => false
                    ];
                }
                
                $gradesData[] = $classGrades;
            }
            
            return response()->json([
                'success' => true,
                'data' => $gradesData,
                'quarters' => $quarters->map(function($q) {
                    return ['name' => $q->name, 'code' => $q->code];
                })
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get quarterly grades', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load grades: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get grade component breakdown for charts
     */
    public function getGradeBreakdown()
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Get active semester
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Get quarters for this semester
            $quarters = DB::table('quarters')
                ->where('semester_id', $activeSemester->id)
                ->orderBy('order_number')
                ->get();
            
            // Get enrolled classes
            $enrolledClasses = $this->getEnrolledClasses($student, $activeSemester->id);
            
            $breakdownData = [];
            
            foreach ($quarters as $quarter) {
                $quarterBreakdown = [
                    'quarter_id' => $quarter->id,
                    'quarter_name' => $quarter->name,
                    'quarter_code' => $quarter->code,
                    'classes' => []
                ];
                
                foreach ($enrolledClasses as $class) {
                    // Get class weightings
                    $classInfo = DB::table('classes')
                        ->where('class_code', $class->class_code)
                        ->first();
                    
                    // Get quarter grade (finalized or computed)
                    $quarterGrade = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarter->id)
                        ->first();
                    
                    $gradeData = null;
                    
                    if ($quarterGrade) {
                        $gradeData = [
                            'ww_ws' => $quarterGrade->ww_ws,
                            'pt_ws' => $quarterGrade->pt_ws,
                            'qa_ws' => $quarterGrade->qa_ws,
                            'transmuted_grade' => $quarterGrade->transmuted_grade,
                            'is_locked' => (bool)$quarterGrade->is_locked
                        ];
                    } else {
                        $gradeData = [
                            'ww_ws' => null,
                            'pt_ws' => null,
                            'qa_ws' => null,
                            'transmuted_grade' => null,
                            'is_locked' => false
                        ];
                    }
                    
                    $quarterBreakdown['classes'][] = [
                        'class_name' => $class->class_name,
                        'class_code' => $class->class_code,
                        'ww_ws' => $gradeData['ww_ws'],
                        'pt_ws' => $gradeData['pt_ws'],
                        'qa_ws' => $gradeData['qa_ws'],
                        'transmuted_grade' => $gradeData['transmuted_grade'],
                        'is_locked' => $gradeData['is_locked'],
                        'ww_perc' => $classInfo->ww_perc,
                        'pt_perc' => $classInfo->pt_perc,
                        'qa_perc' => $classInfo->qa_perce
                    ];
                }
                
                $breakdownData[] = $quarterBreakdown;
            }
            
            return response()->json([
                'success' => true,
                'data' => $breakdownData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get grade breakdown', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade breakdown'
            ], 500);
        }
    }
    
    /**
     * Get semester summary
     */
    public function getSemesterSummary()
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Get active semester
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'q1_average' => null,
                        'q2_average' => null,
                        'semester_average' => null
                    ]
                ]);
            }
            
            // Get quarters
            $quarters = DB::table('quarters')
                ->where('semester_id', $activeSemester->id)
                ->orderBy('order_number')
                ->get();
            
            // Get enrolled classes
            $enrolledClasses = $this->getEnrolledClasses($student, $activeSemester->id);
            
            $q1Grades = [];
            $q2Grades = [];
            
            foreach ($enrolledClasses as $class) {
                // Q1 Grade
                if (isset($quarters[0])) {
                    $q1Grade = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarters[0]->id)
                        ->value('transmuted_grade');
                    
                    if ($q1Grade !== null) {
                        $q1Grades[] = $q1Grade;
                    }
                }
                
                // Q2 Grade
                if (isset($quarters[1])) {
                    $q2Grade = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarters[1]->id)
                        ->value('transmuted_grade');
                    
                    if ($q2Grade !== null) {
                        $q2Grades[] = $q2Grade;
                    }
                }
            }
            
            $q1Average = !empty($q1Grades) ? round(array_sum($q1Grades) / count($q1Grades), 2) : null;
            $q2Average = !empty($q2Grades) ? round(array_sum($q2Grades) / count($q2Grades), 2) : null;
            
            $semesterAverage = null;
            if ($q1Average !== null && $q2Average !== null) {
                $semesterAverage = round(($q1Average + $q2Average) / 2, 2);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'q1_average' => $q1Average,
                    'q2_average' => $q2Average,
                    'semester_average' => $semesterAverage
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get semester summary', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load semester summary'
            ], 500);
        }
    }
    
   private function getEnrolledClasses($student, $semesterId)
    {
        if ($student->student_type === 'regular' && $student->section_id) {
            return DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->where('scm.section_id', $student->section_id)
                ->where('scm.semester_id', $semesterId)
                ->select('c.id', 'c.class_code', 'c.class_name')
                ->get();
        } else {
            return DB::table('student_class_matrix as scm')
                ->join('classes as c', function ($join) {
                    $join->on(
                        DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->where('scm.student_number', $student->student_number)
                ->where('scm.semester_id', $semesterId)
                ->where('scm.enrollment_status', 'enrolled')
                ->select('c.id', 'c.class_code', 'c.class_name')
                ->get();
        }
    }
    /**
     * Show student's own profile
     */
    public function showProfile()
    {
        $student = Auth::guard('student')->user();
        
        if (!$student) {
            return redirect()->route('student.login')->with('error', 'Please login first');
        }

        $studentData = DB::table('students')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
            ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
            ->select(
                'students.*',
                'sections.name as section',
                'levels.name as level',
                'strands.code as strand'
            )
            ->where('students.id', '=', $student->id)
            ->first();

        $data = [
            'student' => $studentData,
            'scripts' => ['student/student_profile.js']
        ];

        return view('student.student_profile', $data);
    }

    /**
     * Get enrollment history for logged-in student
     */
    public function getEnrollmentHistory()
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if ($student->student_type === 'regular') {
                $enrolledSemesters = DB::table('section_class_matrix as scm')
                    ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
                    ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                    ->select(
                        'sem.id as semester_id',
                        'sem.name as semester_name',
                        'sy.year_start',
                        'sy.year_end',
                        'sy.code as school_year_code'
                    )
                    ->where('scm.section_id', '=', $student->section_id)
                    ->groupBy('sem.id', 'sem.name', 'sy.year_start', 'sy.year_end', 'sy.code')
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('sem.name', 'asc')
                    ->get();
            } else {
                $enrolledSemesters = DB::table('student_class_matrix as scm')
                    ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
                    ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                    ->select(
                        'sem.id as semester_id',
                        'sem.name as semester_name',
                        'sy.year_start',
                        'sy.year_end',
                        'sy.code as school_year_code'
                    )
                    ->where('scm.student_number', '=', $student->student_number)
                    ->where('scm.enrollment_status', '=', 'enrolled')
                    ->groupBy('sem.id', 'sem.name', 'sy.year_start', 'sy.year_end', 'sy.code')
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('sem.name', 'asc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $enrolledSemesters
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get enrollment history', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load enrollment history'
            ], 500);
        }
    }

    /**
     * Get enrolled classes for logged-in student
     */
    public function getProfileEnrolledClasses()
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            if ($student->student_type === 'regular') {
                $enrolledClasses = DB::table('section_class_matrix as scm')
                    ->join('classes as c', 'scm.class_id', '=', 'c.id')
                    ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
                    ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                    ->select(
                        'c.class_code',
                        'c.class_name',
                        'sem.name as semester_name',
                        'sy.year_start',
                        'sy.year_end',
                        'scm.semester_id'
                    )
                    ->where('scm.section_id', '=', $student->section_id)
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('sem.name', 'asc')
                    ->orderBy('c.class_name', 'asc')
                    ->get();
            } else {
                $enrolledClasses = DB::table('student_class_matrix as scm')
                    ->join('classes as c', function ($join) {
                        $join->on(
                            DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                            '=',
                            DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                        );
                    })
                    ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
                    ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                    ->select(
                        'c.class_code',
                        'c.class_name',
                        'sem.name as semester_name',
                        'sy.year_start',
                        'sy.year_end',
                        'scm.semester_id',
                        'scm.enrollment_status'
                    )
                    ->where('scm.student_number', '=', $student->student_number)
                    ->where('scm.enrollment_status', '=', 'enrolled')
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('sem.name', 'asc')
                    ->orderBy('c.class_name', 'asc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $enrolledClasses,
                'student_type' => $student->student_type
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch enrolled classes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enrolled classes'
            ], 500);
        }
    }
}
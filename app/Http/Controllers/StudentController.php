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
     * Get quarterly grades for current semester (real-time calculation)
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
            
            $gradesData = [];
            
            foreach ($enrolledClasses as $class) {
                $classGrades = [
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'quarters' => []
                ];
                
                // Get class weightings
                $classInfo = DB::table('classes')
                    ->where('class_code', $class->class_code)
                    ->first();
                
                foreach ($quarters as $quarter) {
                    // First check if finalized grade exists
                    $finalizedGrade = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarter->id)
                        ->first();
                    
                    if ($finalizedGrade && $finalizedGrade->is_locked) {
                        // Use finalized grade if locked
                        $classGrades['quarters'][] = [
                            'quarter_name' => $quarter->name,
                            'quarter_code' => $quarter->code,
                            'initial_grade' => $finalizedGrade->initial_grade,
                            'transmuted_grade' => $finalizedGrade->transmuted_grade,
                            'ww_ws' => $finalizedGrade->ww_ws,
                            'pt_ws' => $finalizedGrade->pt_ws,
                            'qa_ws' => $finalizedGrade->qa_ws,
                            'is_locked' => true
                        ];
                    } else {
                        // Calculate real-time grade from gradebook
                        $calculatedGrade = $this->calculateQuarterGrade(
                            $student->student_number, 
                            $class->class_code, 
                            $quarter->id,
                            $classInfo
                        );
                        
                        $classGrades['quarters'][] = [
                            'quarter_name' => $quarter->name,
                            'quarter_code' => $quarter->code,
                            'initial_grade' => $calculatedGrade['initial_grade'],
                            'transmuted_grade' => $calculatedGrade['transmuted_grade'],
                            'ww_ws' => $calculatedGrade['ww_ws'],
                            'pt_ws' => $calculatedGrade['pt_ws'],
                            'qa_ws' => $calculatedGrade['qa_ws'],
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
                
                if ($finalGrade && $finalGrade->is_locked) {
                    $classGrades['semester_final'] = [
                        'q1_grade' => $finalGrade->q1_grade,
                        'q2_grade' => $finalGrade->q2_grade,
                        'final_grade' => $finalGrade->final_grade,
                        'remarks' => $finalGrade->remarks,
                        'is_locked' => true
                    ];
                } else {
                    // Calculate semester average from quarter grades
                    $q1Grade = $classGrades['quarters'][0]['transmuted_grade'] ?? null;
                    $q2Grade = $classGrades['quarters'][1]['transmuted_grade'] ?? null;
                    
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
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load grades'
            ], 500);
        }
    }
    
    /**
     * Calculate quarter grade from gradebook scores
     * Shows partial grades even if not all components are complete
     */
    private function calculateQuarterGrade($studentNumber, $classCode, $quarterId, $classInfo)
    {
        // Get all columns for this quarter and class
        $columns = DB::table('gradebook_columns')
            ->where('class_code', $classCode)
            ->where('quarter_id', $quarterId)
            ->where('is_active', 1)
            ->get();
        
        if ($columns->isEmpty()) {
            return [
                'initial_grade' => null,
                'transmuted_grade' => null,
                'ww_ws' => null,
                'pt_ws' => null,
                'qa_ws' => null
            ];
        }
        
        // Group by component type
        $wwColumns = $columns->where('component_type', 'WW');
        $ptColumns = $columns->where('component_type', 'PT');
        $qaColumns = $columns->where('component_type', 'QA');
        
        // Calculate WW score (only from completed items)
        $wwScore = $this->calculateComponentScore($studentNumber, $wwColumns);
        $wwPS = $wwScore['percentage'];
        $wwWS = $wwPS !== null ? round($wwPS * ($classInfo->ww_perc / 100), 2) : null;
        
        // Calculate PT score (only from completed items)
        $ptScore = $this->calculateComponentScore($studentNumber, $ptColumns);
        $ptPS = $ptScore['percentage'];
        $ptWS = $ptPS !== null ? round($ptPS * ($classInfo->pt_perc / 100), 2) : null;
        
        // Calculate QA score (only from completed items)
        $qaScore = $this->calculateComponentScore($studentNumber, $qaColumns);
        $qaPS = $qaScore['percentage'];
        $qaWS = $qaPS !== null ? round($qaPS * ($classInfo->qa_perce / 100), 2) : null;
        
        // Calculate initial grade (even if partial)
        // If any component has a score, calculate partial grade
        $initialGrade = null;
        $componentsWithScores = 0;
        $totalWeightedScore = 0;
        
        if ($wwWS !== null) {
            $totalWeightedScore += $wwWS;
            $componentsWithScores++;
        }
        if ($ptWS !== null) {
            $totalWeightedScore += $ptWS;
            $componentsWithScores++;
        }
        if ($qaWS !== null) {
            $totalWeightedScore += $qaWS;
            $componentsWithScores++;
        }
        
        // Show partial grade if at least one component has scores
        if ($componentsWithScores > 0) {
            $initialGrade = round($totalWeightedScore, 2);
        }
        
        // Transmute grade (using simple transmutation table)
        $transmutedGrade = null;
        if ($initialGrade !== null) {
            $transmutedGrade = $this->transmuteGrade($initialGrade);
        }
        
        return [
            'initial_grade' => $initialGrade,
            'transmuted_grade' => $transmutedGrade,
            'ww_ws' => $wwWS,
            'pt_ws' => $ptWS,
            'qa_ws' => $qaWS,
            'is_partial' => $componentsWithScores > 0 && $componentsWithScores < 3
        ];
    }
    
    /**
     * Calculate component score (WW, PT, or QA)
     * Only counts columns where student has a score
     */
    private function calculateComponentScore($studentNumber, $columns)
    {
        if ($columns->isEmpty()) {
            return ['total' => 0, 'max' => 0, 'percentage' => null];
        }
        
        $totalScore = 0;
        $totalMaxPoints = 0;
        
        foreach ($columns as $column) {
            $score = DB::table('gradebook_scores')
                ->where('column_id', $column->id)
                ->where('student_number', $studentNumber)
                ->value('score');
            
            // Only count columns where student has a score (including 0)
            if ($score !== null) {
                $totalScore += $score;
                $totalMaxPoints += $column->max_points;
            }
        }
        
        $percentage = null;
        if ($totalMaxPoints > 0) {
            $percentage = round(($totalScore / $totalMaxPoints) * 100, 2);
        }
        
        return [
            'total' => $totalScore,
            'max' => $totalMaxPoints,
            'percentage' => $percentage
        ];
    }
    
    /**
     * Transmute grade (simple transmutation)
     */
    private function transmuteGrade($initialGrade)
    {
        // Simple transmutation table (adjust as needed)
        if ($initialGrade >= 96.5) return 100;
        if ($initialGrade >= 95.5) return 99;
        if ($initialGrade >= 94.5) return 98;
        if ($initialGrade >= 93.5) return 97;
        if ($initialGrade >= 92.5) return 96;
        if ($initialGrade >= 91.5) return 95;
        if ($initialGrade >= 90.5) return 94;
        if ($initialGrade >= 89.5) return 93;
        if ($initialGrade >= 88.5) return 92;
        if ($initialGrade >= 87.5) return 91;
        if ($initialGrade >= 86.5) return 90;
        if ($initialGrade >= 85.5) return 89;
        if ($initialGrade >= 84.5) return 88;
        if ($initialGrade >= 83.5) return 87;
        if ($initialGrade >= 82.5) return 86;
        if ($initialGrade >= 81.5) return 85;
        if ($initialGrade >= 80.5) return 84;
        if ($initialGrade >= 79.5) return 83;
        if ($initialGrade >= 78.5) return 82;
        if ($initialGrade >= 77.5) return 81;
        if ($initialGrade >= 76.5) return 80;
        if ($initialGrade >= 75.5) return 79;
        if ($initialGrade >= 74.5) return 78;
        if ($initialGrade >= 73.5) return 77;
        if ($initialGrade >= 72.5) return 76;
        if ($initialGrade >= 71.5) return 75;
        
        // Below 75 fails
        return round($initialGrade);
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
                    
                    // Check for finalized grade first
                    $finalizedGrade = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarter->id)
                        ->first();
                    
                    $gradeData = null;
                    
                    if ($finalizedGrade && $finalizedGrade->is_locked) {
                        $gradeData = [
                            'ww_ws' => $finalizedGrade->ww_ws,
                            'pt_ws' => $finalizedGrade->pt_ws,
                            'qa_ws' => $finalizedGrade->qa_ws,
                            'transmuted_grade' => $finalizedGrade->transmuted_grade,
                            'is_locked' => true
                        ];
                    } else {
                        // Calculate real-time grade
                        $calculated = $this->calculateQuarterGrade(
                            $student->student_number,
                            $class->class_code,
                            $quarter->id,
                            $classInfo
                        );
                        
                        $gradeData = [
                            'ww_ws' => $calculated['ww_ws'],
                            'pt_ws' => $calculated['pt_ws'],
                            'qa_ws' => $calculated['qa_ws'],
                            'transmuted_grade' => $calculated['transmuted_grade'],
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                // Get class weightings
                $classInfo = DB::table('classes')
                    ->where('class_code', $class->class_code)
                    ->first();
                
                // Q1 Grade
                if (isset($quarters[0])) {
                    $finalizedQ1 = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarters[0]->id)
                        ->first();
                    
                    if ($finalizedQ1 && $finalizedQ1->is_locked) {
                        if ($finalizedQ1->transmuted_grade !== null) {
                            $q1Grades[] = $finalizedQ1->transmuted_grade;
                        }
                    } else {
                        $calculated = $this->calculateQuarterGrade(
                            $student->student_number,
                            $class->class_code,
                            $quarters[0]->id,
                            $classInfo
                        );
                        if ($calculated['transmuted_grade'] !== null) {
                            $q1Grades[] = $calculated['transmuted_grade'];
                        }
                    }
                }
                
                // Q2 Grade
                if (isset($quarters[1])) {
                    $finalizedQ2 = DB::table('quarter_grades')
                        ->where('student_number', $student->student_number)
                        ->where('class_code', $class->class_code)
                        ->where('quarter_id', $quarters[1]->id)
                        ->first();
                    
                    if ($finalizedQ2 && $finalizedQ2->is_locked) {
                        if ($finalizedQ2->transmuted_grade !== null) {
                            $q2Grades[] = $finalizedQ2->transmuted_grade;
                        }
                    } else {
                        $calculated = $this->calculateQuarterGrade(
                            $student->student_number,
                            $class->class_code,
                            $quarters[1]->id,
                            $classInfo
                        );
                        if ($calculated['transmuted_grade'] !== null) {
                            $q2Grades[] = $calculated['transmuted_grade'];
                        }
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
    
    // Helper methods
    
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

    // Add these methods to your StudentController.php

/**
 * Show student's own profile
 */
public function showProfile()
{
    $student = Auth::guard('student')->user();
    
    if (!$student) {
        return redirect()->route('student.login')->with('error', 'Please login first');
    }

    // Get student with section, level, and strand information
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

        // Get enrolled semesters based on student type
        if ($student->student_type === 'regular') {
            // For regular students, get semesters from section_class_matrix
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
            // For irregular students, get semesters from student_class_matrix
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
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
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

        // Check student type
        if ($student->student_type === 'regular') {
            // For regular students, get classes from section_class_matrix
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
            // For irregular students, get classes from student_class_matrix
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
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch enrolled classes'
        ], 500);
    }
}

}
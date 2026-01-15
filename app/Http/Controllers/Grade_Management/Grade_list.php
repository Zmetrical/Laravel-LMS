<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeTransmutation;

class Grade_List extends MainController
{
    /**
     * Show student grade list page
     */
    public function student_grade_list()
    {
        $student = Auth::guard('student')->user();
        
        // Get active semester with school year
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->first();
        
        // Format display text
        $activeSemesterDisplay = null;
        if ($activeSemester) {
            $activeSemesterDisplay = ' ' .
            $activeSemester->year_start . '-' . 
            $activeSemester->year_end . ' - ' . 
            $activeSemester->semester_name;
        }
        
        // Get quarters for the active semester
        $quarters = DB::table('quarters')
            ->where('semester_id', $activeSemester->semester_id)
            ->orderBy('order_number')
            ->get();
        
        $data = [
            'scripts' => ['student/list_gradebook.js'],
            'activeSemester' => $activeSemester,
            'activeSemesterDisplay' => $activeSemesterDisplay,
            'quarters' => $quarters
        ];
        
        return view('student.list_gradebook', $data);
    }
    
    /**
     * Show student grade details page for a specific class and quarter
     */
    public function student_grade_details($classId, $quarterId)
    {
        $student = Auth::guard('student')->user();
        
        // Get class info
        $class = DB::table('classes')->where('id', $classId)->first();
        
        if (!$class) {
            return redirect()->route('student.grades.index')
                ->with('error', 'Class not found');
        }
        
        // Get quarter info
        $quarter = DB::table('quarters')->where('id', $quarterId)->first();
        
        if (!$quarter) {
            return redirect()->route('student.grades.index')
                ->with('error', 'Quarter not found');
        }
        
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
        
        $data = [
            'scripts' => ['student/details_gradebook.js'],
            'class' => $class,
            'quarter' => $quarter,
            'activeSemester' => $activeSemester,
            'classId' => $classId,
            'quarterId' => $quarterId
        ];
        
        return view('student.details_gradebook', $data);
    }
    
    /**
     * Get student grades with transmutation - now supports quarter filter
     */
    public function getStudentGrades(Request $request)
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No active semester'
                ]);
            }
            
            // Get filter parameter (q1, q2, or final)
            $filter = $request->input('filter', 'q1');
            
            $quarters = DB::table('quarters')
                ->where('semester_id', $activeSemester->id)
                ->orderBy('order_number')
                ->get()
                ->keyBy('order_number');
            
            $classes = $this->getStudentEnrolledClasses($student, $activeSemester->id);
            
            if ($filter === 'final') {
                // Show final grades
                $gradesData = $this->getFinalGradesData($student, $classes, $activeSemester->id, $quarters);
            } else {
                // Show specific quarter grades
                $quarterNumber = $filter === 'q1' ? 1 : 2;
                $quarter = $quarters->get($quarterNumber);
                
                if (!$quarter) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quarter not found'
                    ], 404);
                }
                
                $gradesData = $this->getQuarterGradesData($student, $classes, $quarter, $activeSemester->id);
            }
            
            return response()->json([
                'success' => true,
                'data' => $gradesData,
                'filter' => $filter,
                'semester' => [
                    'id' => $activeSemester->id,
                    'name' => $activeSemester->name,
                    'code' => $activeSemester->code
                ]
            ]);
            
        } catch (Exception $e) {
            \Log::error('Failed to get student grades', [
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
     * Get quarter grades data for list view
     */
    private function getQuarterGradesData($student, $classes, $quarter, $semesterId)
    {
        $gradesData = [];
        
        foreach ($classes as $class) {
            // Check for locked grade first
            $lockedGrade = DB::table('quarter_grades')
                ->where('student_number', $student->student_number)
                ->where('class_code', $class->class_code)
                ->where('quarter_id', $quarter->id)
                ->where('is_locked', 1)
                ->first();
            
            $quarterGrade = null;
            
            if ($lockedGrade) {
                $quarterGrade = $lockedGrade->transmuted_grade;
            } else {
                // Calculate real-time grade
                $scores = $this->getQuarterDetailedScores(
                    $student->student_number,
                    $class->class_code,
                    $quarter->id
                );
                
                $calculatedGrade = $this->calculateGradeFromScores(
                    $scores,
                    $class->ww_perc,
                    $class->pt_perc,
                    $class->qa_perce
                );
                
                if ($calculatedGrade !== null) {
                    $quarterGrade = GradeTransmutation::transmute($calculatedGrade);
                }
            }
            
            $gradesData[] = [
                'class_id' => $class->id,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'teacher_name' => $class->teacher_name,
                'ww_percentage' => $class->ww_perc,
                'pt_percentage' => $class->pt_perc,
                'qa_percentage' => $class->qa_perce,
                'quarter_grade' => $quarterGrade,
                'quarter_id' => $quarter->id,
                'quarter_name' => $quarter->name
            ];
        }
        
        return $gradesData;
    }

    /**
     * Get final grades data for list view
     */
    private function getFinalGradesData($student, $classes, $semesterId, $quarters)
    {
        $allFinalGrades = DB::table('grades_final')
            ->where('student_number', $student->student_number)
            ->whereIn('class_code', $classes->pluck('class_code'))
            ->where('semester_id', $semesterId)
            ->get()
            ->keyBy('class_code');
        
        $gradesData = [];
        
        foreach ($classes as $class) {
            $finalGrade = $allFinalGrades->get($class->class_code);
            
            $gradesData[] = [
                'class_id' => $class->id,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'teacher_name' => $class->teacher_name,
                'ww_percentage' => $class->ww_perc,
                'pt_percentage' => $class->pt_perc,
                'qa_percentage' => $class->qa_perce,
                'q1_grade' => $finalGrade ? $finalGrade->q1_grade : null,
                'q2_grade' => $finalGrade ? $finalGrade->q2_grade : null,
                'final_grade' => $finalGrade ? $finalGrade->final_grade : null,
                'remarks' => $finalGrade ? $finalGrade->remarks : null,
                'has_final' => $finalGrade !== null
            ];
        }
        
        return $gradesData;
    }

    /**
     * Calculate grade from scores and return INITIAL grade (before transmutation)
     */
    private function calculateGradeFromScores($scores, $wwPerc, $ptPerc, $qaPerc)
    {
        if (empty($scores)) {
            return null;
        }
        
        $components = [
            'WW' => ['total' => 0, 'max' => 0],
            'PT' => ['total' => 0, 'max' => 0],
            'QA' => ['total' => 0, 'max' => 0]
        ];
        
        foreach ($scores as $score) {
            $type = $score['component_type'];
            $components[$type]['max'] += $score['max_points'];
            
            if ($score['score'] !== null) {
                $components[$type]['total'] += $score['score'];
            }
        }
        
        $weightedScore = 0;
        $hasAnyScore = false;
        
        if ($components['WW']['max'] > 0) {
            $percentage = ($components['WW']['total'] / $components['WW']['max']) * 100;
            $weightedScore += $percentage * ($wwPerc / 100);
            $hasAnyScore = true;
        }
        
        if ($components['PT']['max'] > 0) {
            $percentage = ($components['PT']['total'] / $components['PT']['max']) * 100;
            $weightedScore += $percentage * ($ptPerc / 100);
            $hasAnyScore = true;
        }
        
        if ($components['QA']['max'] > 0) {
            $percentage = ($components['QA']['total'] / $components['QA']['max']) * 100;
            $weightedScore += $percentage * ($qaPerc / 100);
            $hasAnyScore = true;
        }
        
        return $hasAnyScore ? round($weightedScore, 2) : null;
    }

    /**
     * Get detailed grade breakdown for a specific class and quarter (AJAX)
     */
    public function getClassGradeDetails($classId, $quarterId)
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Get class info
            $class = DB::table('classes')->where('id', $classId)->first();
            
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }
            
            // Get quarter info
            $quarter = DB::table('quarters')->where('id', $quarterId)->first();
            
            if (!$quarter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quarter not found'
                ], 404);
            }
            
            // Get locked quarter grade first
            $quarterGrade = DB::table('quarter_grades')
                ->where('student_number', $student->student_number)
                ->where('class_code', $class->class_code)
                ->where('quarter_id', $quarterId)
                ->first();
            
            // Get detailed scores for this quarter
            $scores = $this->getQuarterDetailedScores(
                $student->student_number, 
                $class->class_code, 
                $quarterId
            );
            
            // If no locked grade, calculate with transmutation
            $calculatedGrade = null;
            if (!$quarterGrade || !$quarterGrade->is_locked) {
                $initialGrade = $this->calculateGradeFromScores(
                    $scores,
                    $class->ww_perc,
                    $class->pt_perc,
                    $class->qa_perce
                );
                
                if ($initialGrade !== null) {
                    $calculatedGrade = [
                        'initial_grade' => $initialGrade,
                        'transmuted_grade' => GradeTransmutation::transmute($initialGrade),
                        'is_calculated' => true
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'quarter' => $quarter,
                    'grades' => $quarterGrade,
                    'calculated_grade' => $calculatedGrade,
                    'scores' => $scores
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get detailed scores for a specific quarter
     */
    private function getQuarterDetailedScores($studentNumber, $classCode, $quarterId)
    {
        $columns = DB::table('gradebook_columns')
            ->where('class_code', $classCode)
            ->where('quarter_id', $quarterId)
            ->where('is_active', true)
            ->orderBy('component_type')
            ->orderBy('order_number')
            ->get();
        
        $detailedScores = [];
        
        foreach ($columns as $column) {
            $score = null;
            
            if ($column->source_type === 'online' && $column->quiz_id) {
                $score = $this->getQuizScore($studentNumber, $column->quiz_id, $column->max_points);
            } else {
                $manualScore = DB::table('gradebook_scores')
                    ->where('column_id', $column->id)
                    ->where('student_number', $studentNumber)
                    ->first();
                
                $score = $manualScore ? $manualScore->score : null;
            }
            
            $detailedScores[] = [
                'column_id' => $column->id,
                'column_name' => $column->column_name,
                'component_type' => $column->component_type,
                'score' => $score,
                'max_points' => $column->max_points,
                'source_type' => $column->source_type,
                'percentage' => $score && $column->max_points > 0 
                    ? round(($score / $column->max_points) * 100, 2) 
                    : 0
            ];
        }
        
        return $detailedScores;
    }
    
    /**
     * Get student's enrolled classes for active semester
     */
    private function getStudentEnrolledClasses($student, $semesterId)
    {
        if ($student->student_type === 'regular' && $student->section_id) {
            return DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('scm.section_id', $student->section_id)
                ->where('scm.semester_id', $semesterId)
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    'c.ww_perc',
                    'c.pt_perc',
                    'c.qa_perce',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name")
                )
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
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('scm.student_number', $student->student_number)
                ->where('scm.semester_id', $semesterId)
                ->where('scm.enrollment_status', 'enrolled')
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    'c.ww_perc',
                    'c.pt_perc',
                    'c.qa_perce',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name")
                )
                ->get();
        }
    }
    
    /**
     * Get quiz score adjusted to max points
     */
    private function getQuizScore($studentNumber, $quizId, $maxPoints)
    {
        $attempt = DB::table('student_quiz_attempts')
            ->where('student_number', $studentNumber)
            ->where('quiz_id', $quizId)
            ->where('status', 'graded')
            ->orderBy('score', 'desc')
            ->first();
        
        if (!$attempt || $attempt->total_points == 0) {
            return null;
        }
        
        $percentage = ($attempt->score / $attempt->total_points) * 100;
        return round(($percentage / 100) * $maxPoints, 2);
    }
}
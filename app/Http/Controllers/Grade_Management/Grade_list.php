<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Grade_list extends MainController
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
        
        $data = [
            'scripts' => ['student/list_gradebook.js'],
            'activeSemester' => $activeSemester,
            'activeSemesterDisplay' => $activeSemesterDisplay
        ];
        
        return view('student.list_gradebook', $data);
    }
    
    /**
     * Show student grade details page for a specific class
     */
    public function student_grade_details($classId)
    {
        $student = Auth::guard('student')->user();
        
        // Get class info
        $class = DB::table('classes')->where('id', $classId)->first();
        
        if (!$class) {
            return redirect()->route('student.grades.index')
                ->with('error', 'Class not found');
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
        
        // Get quarters for this semester
        $quarters = DB::table('quarters')
            ->where('semester_id', $activeSemester->semester_id)
            ->orderBy('order_number')
            ->get();
        
        $data = [
            'scripts' => ['student/details_gradebook.js'],
            'class' => $class,
            'activeSemester' => $activeSemester,
            'quarters' => $quarters,
            'classId' => $classId
        ];
        
        return view('student.details_gradebook', $data);
    }
    
    /**
     * Get student's grades for all enrolled classes
     */
    public function getStudentGrades()
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
                    'message' => 'No active semester'
                ]);
            }
            
            // Get enrolled classes
            $classes = $this->getStudentEnrolledClasses($student, $activeSemester->id);
            
            $gradesData = [];
            
            foreach ($classes as $class) {
                $gradeInfo = $this->getClassGradeInfo($student->student_number, $class->class_code, $activeSemester->id);
                
                $gradesData[] = [
                    'class_id' => $class->id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'teacher_name' => $class->teacher_name,
                    'ww_percentage' => $class->ww_perc,
                    'pt_percentage' => $class->pt_perc,
                    'qa_percentage' => $class->qa_perce,
                    'components' => $gradeInfo['components'],
                    'final_grade' => $gradeInfo['final_grade'],
                    'has_final' => $gradeInfo['has_final']
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $gradesData,
                'semester' => [
                    'id' => $activeSemester->id,
                    'name' => $activeSemester->name,
                    'code' => $activeSemester->code
                ]
            ]);
            
        } catch (Exception $e) {
            \Log::error('Failed to get student grades', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load grades: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get detailed grade breakdown for a specific class (AJAX)
     */
    public function getClassGradeDetails($classId)
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
                    'success' => false,
                    'message' => 'No active semester'
                ], 400);
            }
            
            // Get class info
            $class = DB::table('classes')->where('id', $classId)->first();
            
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }
            
            // Get quarters
            $quarters = DB::table('quarters')
                ->where('semester_id', $activeSemester->id)
                ->orderBy('order_number')
                ->get();
            
            $quarterData = [];
            
            foreach ($quarters as $quarter) {
                // Get quarter grades
                $quarterGrade = DB::table('quarter_grades')
                    ->where('student_number', $student->student_number)
                    ->where('class_code', $class->class_code)
                    ->where('quarter_id', $quarter->id)
                    ->first();
                
                // Get detailed scores for this quarter
                $scores = $this->getQuarterDetailedScores(
                    $student->student_number, 
                    $class->class_code, 
                    $quarter->id
                );
                
                $quarterData[] = [
                    'quarter' => $quarter,
                    'grades' => $quarterGrade,
                    'scores' => $scores
                ];
            }
            
            // Get final grade
            $finalGrade = DB::table('grades_final')
                ->where('student_number', $student->student_number)
                ->where('class_code', $class->class_code)
                ->where('semester_id', $activeSemester->id)
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'quarters' => $quarterData,
                    'final_grade' => $finalGrade
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
     * Get grade information for a specific class
     */
    private function getClassGradeInfo($studentNumber, $classCode, $semesterId)
    {
        // Get columns
        $columns = DB::table('gradebook_columns')
            ->where('class_code', $classCode)
            ->where('is_active', true)
            ->orderBy('component_type')
            ->orderBy('order_number')
            ->get()
            ->groupBy('component_type');
        
        $components = [
            'WW' => ['scores' => [], 'average' => 0, 'count' => 0],
            'PT' => ['scores' => [], 'average' => 0, 'count' => 0],
            'QA' => ['scores' => [], 'average' => 0, 'count' => 0]
        ];
        
        foreach (['WW', 'PT', 'QA'] as $type) {
            $typeColumns = $columns->get($type, collect());
            
            foreach ($typeColumns as $column) {
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
                
                if ($score !== null) {
                    $percentage = ($score / $column->max_points) * 100;
                    $components[$type]['scores'][] = $percentage;
                    $components[$type]['count']++;
                }
            }
            
            if ($components[$type]['count'] > 0) {
                $components[$type]['average'] = round(
                    array_sum($components[$type]['scores']) / $components[$type]['count'],
                    2
                );
            }
        }
        
        // Check for final grade
        $finalGrade = DB::table('grades_final')
            ->where('student_number', $studentNumber)
            ->where('class_code', $classCode)
            ->where('semester_id', $semesterId)
            ->first();
        
        return [
            'components' => $components,
            'final_grade' => $finalGrade,
            'has_final' => $finalGrade !== null
        ];
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
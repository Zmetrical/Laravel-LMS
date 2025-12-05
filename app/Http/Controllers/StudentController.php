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
     * Get dashboard statistics
     */
    public function getDashboardStats()
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
                        'enrolled_classes' => 0,
                        'completed_lessons' => 0,
                        'pending_quizzes' => 0,
                        'average_grade' => 0
                    ]
                ]);
            }
            
            // Get enrolled classes count
            $enrolledClasses = $this->getEnrolledClassesCount($student, $activeSemester->id);
            
            // Get completed lessons count
            $completedLessons = DB::table('student_lecture_progress')
                ->where('student_number', $student->student_number)
                ->where('semester_id', $activeSemester->id)
                ->where('is_completed', 1)
                ->count();
            
            // Get pending quizzes count
            $pendingQuizzes = $this->getPendingQuizzesCount($student, $activeSemester->id);
            
            // Get average grade
            $averageGrade = $this->getAverageGrade($student->student_number, $activeSemester->id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'enrolled_classes' => $enrolledClasses,
                    'completed_lessons' => $completedLessons,
                    'pending_quizzes' => $pendingQuizzes,
                    'average_grade' => $averageGrade
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get dashboard stats', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }
    
    /**
     * Get available quizzes
     */
    public function getAvailableQuizzes()
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
            
            // Get enrolled classes
            $enrolledClasses = $this->getEnrolledClasses($student, $activeSemester->id);
            $classIds = $enrolledClasses->pluck('id')->toArray();
            
            if (empty($classIds)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Get available quizzes
            $now = Carbon::now();
            
            $quizzes = DB::table('quizzes as q')
                ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
                ->join('classes as c', 'l.class_id', '=', 'c.id')
                ->leftJoin('student_quiz_attempts as sqa', function($join) use ($student) {
                    $join->on('q.id', '=', 'sqa.quiz_id')
                        ->where('sqa.student_number', '=', $student->student_number)
                        ->where('sqa.status', '=', 'graded');
                })
                ->whereIn('l.class_id', $classIds)
                ->where('q.status', 1)
                ->where('q.semester_id', $activeSemester->id)
                ->where(function($query) use ($now) {
                    $query->whereNull('q.available_from')
                        ->orWhere('q.available_from', '<=', $now);
                })
                ->where(function($query) use ($now) {
                    $query->whereNull('q.available_until')
                        ->orWhere('q.available_until', '>=', $now);
                })
                ->whereRaw('(COALESCE(sqa.attempt_count, 0) < q.max_attempts OR q.max_attempts = 0)')
                ->select(
                    'q.id',
                    'q.title',
                    'q.description',
                    'q.time_limit',
                    'q.available_until',
                    'q.max_attempts',
                    'c.class_name',
                    'c.class_code',
                    'l.title as lesson_title',
                    DB::raw('COALESCE(COUNT(sqa.id), 0) as attempts_taken')
                )
                ->groupBy('q.id', 'q.title', 'q.description', 'q.time_limit', 'q.available_until', 
                         'q.max_attempts', 'c.class_name', 'c.class_code', 'l.title')
                ->orderBy('q.available_until')
                ->limit(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $quizzes
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get available quizzes', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quizzes'
            ], 500);
        }
    }
    
    /**
     * Get recent grades
     */
    public function getRecentGrades()
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
            
            // Get recent quiz attempts
            $recentGrades = DB::table('student_quiz_attempts as sqa')
                ->join('quizzes as q', 'sqa.quiz_id', '=', 'q.id')
                ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
                ->join('classes as c', 'l.class_id', '=', 'c.id')
                ->where('sqa.student_number', $student->student_number)
                ->where('sqa.status', 'graded')
                ->where('sqa.semester_id', $activeSemester->id)
                ->select(
                    'q.title as quiz_title',
                    'c.class_name',
                    'sqa.score',
                    'sqa.total_points',
                    'sqa.submitted_at',
                    DB::raw('ROUND((sqa.score / sqa.total_points * 100), 2) as percentage')
                )
                ->orderBy('sqa.submitted_at', 'desc')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $recentGrades
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get recent grades', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent grades'
            ], 500);
        }
    }
    
    /**
     * Get performance chart data
     */
    public function getPerformanceChart()
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
            
            // Get enrolled classes
            $enrolledClasses = $this->getEnrolledClasses($student, $activeSemester->id);
            
            $performanceData = [];
            
            foreach ($enrolledClasses as $class) {
                $gradeInfo = $this->getClassGradeComponents($student->student_number, $class->class_code);
                
                $performanceData[] = [
                    'class_name' => $class->class_name,
                    'ww_avg' => $gradeInfo['WW'],
                    'pt_avg' => $gradeInfo['PT'],
                    'qa_avg' => $gradeInfo['QA']
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $performanceData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get performance chart data', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load performance data'
            ], 500);
        }
    }
    
    // Helper methods
    
    private function getEnrolledClassesCount($student, $semesterId)
    {
        if ($student->student_type === 'regular' && $student->section_id) {
            return DB::table('section_class_matrix')
                ->where('section_id', $student->section_id)
                ->where('semester_id', $semesterId)
                ->count();
        } else {
            return DB::table('student_class_matrix')
                ->where('student_number', $student->student_number)
                ->where('semester_id', $semesterId)
                ->where('enrollment_status', 'enrolled')
                ->count();
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
    
    private function getPendingQuizzesCount($student, $semesterId)
    {
        $enrolledClasses = $this->getEnrolledClasses($student, $semesterId);
        $classIds = $enrolledClasses->pluck('id')->toArray();
        
        if (empty($classIds)) {
            return 0;
        }
        
        $now = Carbon::now();
        
        return DB::table('quizzes as q')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->leftJoin('student_quiz_attempts as sqa', function($join) use ($student) {
                $join->on('q.id', '=', 'sqa.quiz_id')
                    ->where('sqa.student_number', '=', $student->student_number)
                    ->where('sqa.status', '=', 'graded');
            })
            ->whereIn('l.class_id', $classIds)
            ->where('q.status', 1)
            ->where('q.semester_id', $semesterId)
            ->where(function($query) use ($now) {
                $query->whereNull('q.available_from')
                    ->orWhere('q.available_from', '<=', $now);
            })
            ->where(function($query) use ($now) {
                $query->whereNull('q.available_until')
                    ->orWhere('q.available_until', '>=', $now);
            })
            ->whereRaw('(COALESCE(COUNT(sqa.id), 0) < q.max_attempts OR q.max_attempts = 0)')
            ->groupBy('q.id')
            ->get()
            ->count();
    }
    
    private function getAverageGrade($studentNumber, $semesterId)
    {
        $finalGrades = DB::table('grades_final')
            ->where('student_number', $studentNumber)
            ->where('semester_id', $semesterId)
            ->whereNotNull('final_grade')
            ->avg('final_grade');
        
        return $finalGrades ? round($finalGrades, 2) : 0;
    }
    
    private function getClassGradeComponents($studentNumber, $classCode)
    {
        $columns = DB::table('gradebook_columns')
            ->where('class_code', $classCode)
            ->where('is_active', true)
            ->get()
            ->groupBy('component_type');
        
        $components = [
            'WW' => 0,
            'PT' => 0,
            'QA' => 0
        ];
        
        foreach (['WW', 'PT', 'QA'] as $type) {
            $typeColumns = $columns->get($type, collect());
            $scores = [];
            
            foreach ($typeColumns as $column) {
                $score = DB::table('gradebook_scores')
                    ->where('column_id', $column->id)
                    ->where('student_number', $studentNumber)
                    ->first();
                
                if ($score && $score->score !== null && $column->max_points > 0) {
                    $scores[] = ($score->score / $column->max_points) * 100;
                }
            }
            
            if (count($scores) > 0) {
                $components[$type] = round(array_sum($scores) / count($scores), 2);
            }
        }
        
        return $components;
    }
}
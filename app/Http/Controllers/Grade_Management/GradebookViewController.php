<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeTransmutation;

class GradebookViewController extends MainController
{
    /**
     * View-only gradebook page
     */
    public function view_gradebook($classId) 
    {
        $teacher = Auth::guard('teacher')->user();
        
        $hasAccess = DB::table('teacher_class_matrix')
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Unauthorized access to this class');
        }

        $class = DB::table('classes')->where('id', $classId)->first();
        
        $quarters = DB::table('quarters as q')
            ->join('semesters as s', 'q.semester_id', '=', 's.id')
            ->where('s.status', 'active')
            ->orderBy('q.order_number')
            ->select('q.*')
            ->get();

        $sections = DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->select('sec.id', 'sec.name', 'sec.code')
            ->get();

        $data = [
            'scripts' => ['gradebook/view_gradebook.js'],
            'classId' => $classId,
            'class' => $class,
            'quarters' => $quarters,
            'sections' => $sections
        ];

        return view('teacher.gradebook.view_gradebook', $data);
    }

    /**
     * Get gradebook structure and data for a specific quarter
     */
    public function getGradebookData($classId, Request $request)
    {
        try {
            $teacher = Auth::guard('teacher')->user();
            
            $hasAccess = DB::table('teacher_class_matrix')
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $classId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $quarterId = $request->input('quarter_id');
            $sectionId = $request->input('section_id');
            
            if (!$quarterId) {
                return response()->json(['success' => false, 'message' => 'Quarter ID required'], 400);
            }
            
            if (!$sectionId) {
                return response()->json(['success' => false, 'message' => 'Section ID required'], 400);
            }

            $class = DB::table('classes')->where('id', $classId)->first();
            $quarter = DB::table('quarters')->where('id', $quarterId)->first();

            $this->ensureColumnsExist($class->class_code, $quarterId);

            $columns = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('quarter_id', $quarterId)
                ->orderBy('component_type')
                ->orderBy('order_number')
                ->get()
                ->groupBy('component_type');

            $students = $this->getEnrolledStudentsBySection($classId, $sectionId);

            $scores = DB::table('gradebook_scores as gs')
                ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
                ->where('gc.class_code', $class->class_code)
                ->where('gc.quarter_id', $quarterId)
                ->select('gs.*', 'gc.component_type', 'gc.column_name')
                ->get()
                ->groupBy('student_number');

            $quizScores = $this->getQuizScores($columns, $students, $quarterId);

            $gradebookData = [];
            foreach ($students as $student) {
                $studentScores = $scores->get($student->student_number, collect());
                
                $row = [
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'gender' => $student->gender,
                    'section_id' => $student->section_id,
                    'ww' => [],
                    'pt' => [],
                    'qa' => []
                ];

                foreach (['WW', 'PT', 'QA'] as $type) {
                    $typeColumns = $columns->get($type, collect());
                    foreach ($typeColumns as $col) {
                        $score = $studentScores->firstWhere('column_name', $col->column_name);
                        
                        $quizScore = null;
                        if ($col->quiz_id && isset($quizScores[$col->quiz_id][$student->student_number])) {
                            $quizScore = $quizScores[$col->quiz_id][$student->student_number];
                        }
                        
                        $row[strtolower($type)][$col->column_name] = [
                            'score' => $quizScore ?? ($score ? $score->score : null),
                            'max_points' => $col->max_points,
                            'source' => $col->source_type,
                            'column_id' => $col->id,
                            'is_active' => $col->is_active
                        ];
                    }
                }

                $gradebookData[] = $row;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'quarter' => $quarter,
                    'columns' => $columns,
                    'students' => $gradebookData
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load gradebook', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load gradebook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get final semester grades for both quarters
     */
    public function getFinalGradeData($classId, Request $request)
    {
        try {
            $teacher = Auth::guard('teacher')->user();
            
            $hasAccess = DB::table('teacher_class_matrix')
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $classId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $sectionId = $request->input('section_id');
            if (!$sectionId) {
                return response()->json(['success' => false, 'message' => 'Section ID required'], 400);
            }

            $class = DB::table('classes')->where('id', $classId)->first();
            
            $semester = DB::table('semesters')->where('status', 'active')->first();
            if (!$semester) {
                return response()->json(['success' => false, 'message' => 'No active semester'], 400);
            }

            $quarters = DB::table('quarters')
                ->where('semester_id', $semester->id)
                ->orderBy('order_number')
                ->get();

            if ($quarters->count() < 2) {
                return response()->json(['success' => false, 'message' => 'Semester must have 2 quarters'], 400);
            }

            $q1 = $quarters[0];
            $q2 = $quarters[1];

            $students = $this->getEnrolledStudentsBySection($classId, $sectionId);

            $finalGrades = [];

            foreach ($students as $student) {
                $q1Data = $this->calculateQuarterGrade($student->student_number, $class, $q1->id);
                $q2Data = $this->calculateQuarterGrade($student->student_number, $class, $q2->id);

                $q1Grade = $q1Data['quarterly_grade'];
                $q2Grade = $q2Data['quarterly_grade'];

                // Semester average is simple average of Q1 and Q2 (both already transmuted)
                $semesterAverage = ($q1Grade + $q2Grade) / 2;
                
                // Final grade is rounded average (no transmutation)
                $finalGrade = round($semesterAverage);
                
                $remarks = GradeTransmutation::getRemarks($finalGrade);

                $finalGrades[] = [
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'gender' => $student->gender,
                    'q1_grade' => $q1Grade,
                    'q2_grade' => $q2Grade,
                    'final_grade' => $finalGrade,
                    'remarks' => $remarks
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'semester' => $semester,
                    'quarters' => $quarters,
                    'students' => $finalGrades
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load final grades', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load final grades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available quizzes for mapping
     */
    public function getAvailableQuizzes($classId, Request $request)
    {
        try {
            $quarterId = $request->input('quarter_id');
            
            if (!$quarterId) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Quarter ID is required'
                ], 400);
            }
            
            $class = DB::table('classes')->where('id', $classId)->first();
            
            if (!$class) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Class not found'
                ], 404);
            }

            $mappedQuizIds = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('quarter_id', $quarterId)
                ->whereNotNull('quiz_id')
                ->where('is_active', 1)
                ->pluck('quiz_id')
                ->toArray();

            $quizzes = DB::table('quizzes as q')
                ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
                ->where('l.class_id', $classId)
                ->where('q.status', 1)
                ->where(function($query) use ($quarterId) {
                    $query->where('q.quarter_id', $quarterId)
                        ->orWhereNull('q.quarter_id');
                })
                ->whereNotIn('q.id', $mappedQuizIds)
                ->select(
                    'q.id', 
                    'q.title',
                    'q.quarter_id',
                    'l.title as lesson_title',
                    DB::raw('(SELECT SUM(points) FROM quiz_questions WHERE quiz_id = q.id) as total_points')
                )
                ->orderBy('l.title')
                ->orderBy('q.title')
                ->get();

            if ($quizzes->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No available quizzes found for this quarter.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $quizzes
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load quizzes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quizzes: ' . $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function ensureColumnsExist($classCode, $quarterId)
    {
        $existingColumns = DB::table('gradebook_columns')
            ->where('class_code', $classCode)
            ->where('quarter_id', $quarterId)
            ->get()
            ->groupBy('component_type');

        // Create WW columns
        $existingWW = $existingColumns->get('WW', collect());
        $wwCount = $existingWW->count();
        
        for ($i = $wwCount + 1; $i <= 10; $i++) {
            $exists = DB::table('gradebook_columns')
                ->where('class_code', $classCode)
                ->where('quarter_id', $quarterId)
                ->where('component_type', 'WW')
                ->where('column_name', 'WW' . $i)
                ->exists();
                
            if (!$exists) {
                DB::table('gradebook_columns')->insert([
                    'class_code' => $classCode,
                    'quarter_id' => $quarterId,
                    'component_type' => 'WW',
                    'column_name' => 'WW' . $i,
                    'max_points' => 10,
                    'order_number' => $i,
                    'source_type' => 'manual',
                    'is_active' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Create PT columns
        $existingPT = $existingColumns->get('PT', collect());
        $ptCount = $existingPT->count();
        
        for ($i = $ptCount + 1; $i <= 10; $i++) {
            $exists = DB::table('gradebook_columns')
                ->where('class_code', $classCode)
                ->where('quarter_id', $quarterId)
                ->where('component_type', 'PT')
                ->where('column_name', 'PT' . $i)
                ->exists();
                
            if (!$exists) {
                DB::table('gradebook_columns')->insert([
                    'class_code' => $classCode,
                    'quarter_id' => $quarterId,
                    'component_type' => 'PT',
                    'column_name' => 'PT' . $i,
                    'max_points' => 10,
                    'order_number' => $i,
                    'source_type' => 'manual',
                    'is_active' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Create QA column
        $existingQA = $existingColumns->get('QA', collect());
        $qaCount = $existingQA->count();
        
        if ($qaCount === 0) {
            $exists = DB::table('gradebook_columns')
                ->where('class_code', $classCode)
                ->where('quarter_id', $quarterId)
                ->where('component_type', 'QA')
                ->where('column_name', 'QA')
                ->exists();
                
            if (!$exists) {
                DB::table('gradebook_columns')->insert([
                    'class_code' => $classCode,
                    'quarter_id' => $quarterId,
                    'component_type' => 'QA',
                    'column_name' => 'QA',
                    'max_points' => 50,
                    'order_number' => 1,
                    'source_type' => 'manual',
                    'is_active' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function getQuizScores($columns, $students, $quarterId)
    {
        $quizScores = [];
        
        foreach (['WW', 'PT', 'QA'] as $type) {
            $typeColumns = $columns->get($type, collect());
            
            foreach ($typeColumns as $col) {
                if (!$col->quiz_id) continue;
                
                $attempts = DB::table('student_quiz_attempts as sqa')
                    ->where('quiz_id', $col->quiz_id)
                    ->where('quarter_id', $quarterId)
                    ->where('status', 'graded')
                    ->select(
                        'student_number',
                        DB::raw('MAX(score) as best_score'),
                        DB::raw('MAX(total_points) as total_points')
                    )
                    ->groupBy('student_number')
                    ->get();
                
                foreach ($attempts as $attempt) {
                    if ($attempt->total_points > 0) {
                        $percentage = ($attempt->best_score / $attempt->total_points) * 100;
                        $adjustedScore = round(($percentage / 100) * $col->max_points, 2);
                        
                        $quizScores[$col->quiz_id][$attempt->student_number] = $adjustedScore;
                    }
                }
            }
        }
        
        return $quizScores;
    }

    private function calculateQuarterGrade($studentNumber, $class, $quarterId)
    {
        $columns = DB::table('gradebook_columns')
            ->where('class_code', $class->class_code)
            ->where('quarter_id', $quarterId)
            ->where('is_active', 1)
            ->get()
            ->groupBy('component_type');

        $scores = DB::table('gradebook_scores as gs')
            ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
            ->where('gc.class_code', $class->class_code)
            ->where('gc.quarter_id', $quarterId)
            ->where('gs.student_number', $studentNumber)
            ->select('gs.*', 'gc.component_type', 'gc.column_name', 'gc.max_points')
            ->get()
            ->groupBy('component_type');

        $quizScores = $this->getQuizScoresForStudent($columns, $studentNumber, $quarterId);

        // Calculate WW
        $wwTotal = 0;
        $wwMax = 0;
        foreach ($columns->get('WW', []) as $col) {
            $wwMax += $col->max_points;
            $score = null;
            
            if ($col->quiz_id && isset($quizScores[$col->quiz_id])) {
                $score = $quizScores[$col->quiz_id];
            } else {
                $scoreRecord = $scores->get('WW', collect())->firstWhere('column_name', $col->column_name);
                $score = $scoreRecord ? $scoreRecord->score : null;
            }
            
            if ($score !== null) {
                $wwTotal += floatval($score);
            }
        }
        $wwPerc = $wwMax > 0 ? ($wwTotal / $wwMax * 100) : 0;
        $wwWeighted = $wwPerc * ($class->ww_perc / 100);

        // Calculate PT
        $ptTotal = 0;
        $ptMax = 0;
        foreach ($columns->get('PT', []) as $col) {
            $ptMax += $col->max_points;
            $score = null;
            
            if ($col->quiz_id && isset($quizScores[$col->quiz_id])) {
                $score = $quizScores[$col->quiz_id];
            } else {
                $scoreRecord = $scores->get('PT', collect())->firstWhere('column_name', $col->column_name);
                $score = $scoreRecord ? $scoreRecord->score : null;
            }
            
            if ($score !== null) {
                $ptTotal += floatval($score);
            }
        }
        $ptPerc = $ptMax > 0 ? ($ptTotal / $ptMax * 100) : 0;
        $ptWeighted = $ptPerc * ($class->pt_perc / 100);

        // Calculate QA
        $qaTotal = 0;
        $qaMax = 0;
        foreach ($columns->get('QA', []) as $col) {
            $qaMax += $col->max_points;
            $score = null;
            
            if ($col->quiz_id && isset($quizScores[$col->quiz_id])) {
                $score = $quizScores[$col->quiz_id];
            } else {
                $scoreRecord = $scores->get('QA', collect())->firstWhere('column_name', $col->column_name);
                $score = $scoreRecord ? $scoreRecord->score : null;
            }
            
            if ($score !== null) {
                $qaTotal += floatval($score);
            }
        }
        $qaPerc = $qaMax > 0 ? ($qaTotal / $qaMax * 100) : 0;
        $qaWeighted = $qaPerc * ($class->qa_perce / 100);

        $initialGrade = $wwWeighted + $ptWeighted + $qaWeighted;
        $transmutedGrade = GradeTransmutation::transmute($initialGrade);
        
        return [
            'ww_weighted' => $wwWeighted,
            'pt_weighted' => $ptWeighted,
            'qa_weighted' => $qaWeighted,
            'initial_grade' => $initialGrade,
            'quarterly_grade' => $transmutedGrade
        ];
    }

    private function getQuizScoresForStudent($columns, $studentNumber, $quarterId)
    {
        $quizScores = [];
        
        foreach (['WW', 'PT', 'QA'] as $type) {
            $typeColumns = $columns->get($type, collect());
            
            foreach ($typeColumns as $col) {
                if (!$col->quiz_id) continue;
                
                $attempt = DB::table('student_quiz_attempts')
                    ->where('quiz_id', $col->quiz_id)
                    ->where('student_number', $studentNumber)
                    ->where('quarter_id', $quarterId)
                    ->where('status', 'graded')
                    ->select(DB::raw('MAX(score) as best_score'), DB::raw('MAX(total_points) as total_points'))
                    ->first();
                
                if ($attempt && $attempt->total_points > 0) {
                    $percentage = ($attempt->best_score / $attempt->total_points) * 100;
                    $adjustedScore = round(($percentage / 100) * $col->max_points, 2);
                    
                    $quizScores[$col->quiz_id] = $adjustedScore;
                }
            }
        }
        
        return $quizScores;
    }

    private function getEnrolledStudentsBySection($classId, $sectionId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $regular = DB::table('students as s')
            ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.section_id', $sectionId)
            ->where('s.student_type', 'regular')
            ->select(
                's.student_number',
                's.gender',
                's.section_id',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        $irregular = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->where('scm.class_code', $class->class_code)
            ->where('s.section_id', $sectionId)
            ->where('scm.enrollment_status', 'enrolled')
            ->where('s.student_type', 'irregular')
            ->select(
                's.student_number',
                's.gender',
                's.section_id',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        return $regular->union($irregular)
            ->orderBy('gender')
            ->orderBy('full_name')
            ->get();
    }
}
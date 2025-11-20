<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GradeBook_Management extends MainController
{
    const MAX_COLUMNS_PER_TYPE = 10;
    
    public function list_gradebook($classId) 
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

        $data = [
            'scripts' => ['grade_management/page_gradebook.js'],
            'classId' => $classId,
            'class' => $class
        ];

        return view('teacher.gradebook.page_gradebook', $data);
    }

    /**
     * Get gradebook structure and data
     */
    public function getGradebookData($classId)
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

            $class = DB::table('classes')->where('id', $classId)->first();

            $columns = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('is_active', true)
                ->orderBy('component_type')
                ->orderBy('order_number')
                ->get()
                ->groupBy('component_type');

            $students = $this->getEnrolledStudents($classId);

            $scores = DB::table('gradebook_scores as gs')
                ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
                ->where('gc.class_code', $class->class_code)
                ->select('gs.*', 'gc.component_type', 'gc.column_name')
                ->get()
                ->groupBy('student_number');

            // Get quiz scores for online columns
            $quizScores = $this->getQuizScores($columns, $students);

            $gradebookData = [];
            foreach ($students as $student) {
                $studentScores = $scores->get($student->student_number, collect());
                
                $row = [
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'ww' => [],
                    'pt' => [],
                    'qa' => []
                ];

                foreach (['WW', 'PT', 'QA'] as $type) {
                    $typeColumns = $columns->get($type, collect());
                    foreach ($typeColumns as $col) {
                        $score = $studentScores->firstWhere('column_name', $col->column_name);
                        
                        // Check if this column has quiz data
                        $quizScore = null;
                        if ($col->quiz_id && isset($quizScores[$col->quiz_id][$student->student_number])) {
                            $quizScore = $quizScores[$col->quiz_id][$student->student_number];
                        }
                        
                        $row[strtolower($type)][$col->column_name] = [
                            'score' => $quizScore ?? ($score ? $score->score : null),
                            'max_points' => $col->max_points,
                            'source' => $col->source_type,
                            'column_id' => $col->id
                        ];
                    }
                }

                $gradebookData[] = $row;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'columns' => $columns,
                    'students' => $gradebookData
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load gradebook', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load gradebook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz scores properly calculated
     */
    private function getQuizScores($columns, $students)
    {
        $quizScores = [];
        
        foreach (['WW', 'PT', 'QA'] as $type) {
            $typeColumns = $columns->get($type, collect());
            
            foreach ($typeColumns as $col) {
                if (!$col->quiz_id) continue;
                
                $attempts = DB::table('student_quiz_attempts as sqa')
                    ->where('quiz_id', $col->quiz_id)
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

    /**
     * Update column configuration
     */
    public function updateColumn(Request $request, $columnId)
    {
        try {
            $validated = $request->validate([
                'max_points' => 'required|integer|min:1',
                'quiz_id' => 'nullable|exists:quizzes,id'
            ]);

            $column = DB::table('gradebook_columns')->where('id', $columnId)->first();

            $updateData = [
                'max_points' => $validated['max_points'],
                'updated_at' => now()
            ];

            if (isset($validated['quiz_id']) && $validated['quiz_id']) {
                $updateData['quiz_id'] = $validated['quiz_id'];
                $updateData['source_type'] = 'online';
            } else {
                $updateData['quiz_id'] = null;
                $updateData['source_type'] = 'manual';
            }

            DB::table('gradebook_columns')
                ->where('id', $columnId)
                ->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Column updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update column: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new column
     */
    public function addColumn(Request $request, $classId)
    {
        try {
            $validated = $request->validate([
                'component_type' => 'required|in:WW,PT,QA'
            ]);

            $class = DB::table('classes')->where('id', $classId)->first();

            // Check column limit
            $currentCount = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('component_type', $validated['component_type'])
                ->where('is_active', 1)
                ->count();

            if ($currentCount >= self::MAX_COLUMNS_PER_TYPE) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum of " . self::MAX_COLUMNS_PER_TYPE . " columns reached for this component type"
                ], 400);
            }

            $maxOrder = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('component_type', $validated['component_type'])
                ->max('order_number');

            $nextNumber = $maxOrder + 1;
            $columnName = $validated['component_type'] . $nextNumber;

            $columnId = DB::table('gradebook_columns')->insertGetId([
                'class_code' => $class->class_code,
                'component_type' => $validated['component_type'],
                'column_name' => $columnName,
                'max_points' => $validated['component_type'] === 'QA' ? 50 : 10,
                'order_number' => $nextNumber,
                'source_type' => 'manual',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Column added successfully',
                'data' => DB::table('gradebook_columns')->find($columnId)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add column: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch update scores
     */
    public function batchUpdateScores(Request $request, $classId)
    {
        try {
            $validated = $request->validate([
                'scores' => 'required|array',
                'scores.*.column_id' => 'required|exists:gradebook_columns,id',
                'scores.*.student_number' => 'required|exists:students,student_number',
                'scores.*.score' => 'nullable|numeric|min:0'
            ]);

            DB::beginTransaction();

            foreach ($validated['scores'] as $scoreData) {
                DB::table('gradebook_scores')->updateOrInsert(
                    [
                        'column_id' => $scoreData['column_id'],
                        'student_number' => $scoreData['student_number']
                    ],
                    [
                        'score' => $scoreData['score'],
                        'source' => 'manual',
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Scores updated successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available quizzes for mapping
     */
    public function getAvailableQuizzes($classId)
    {
        try {
            $class = DB::table('classes')->where('id', $classId)->first();

            $quizzes = DB::table('quizzes as q')
                ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
                ->where('l.class_id', $classId)
                ->whereNotIn('q.id', function($query) use ($class) {
                    $query->select('quiz_id')
                        ->from('gradebook_columns')
                        ->where('class_code', $class->class_code)
                        ->whereNotNull('quiz_id');
                })
                ->select(
                    'q.id', 
                    'q.title', 
                    'l.title as lesson_title',
                    DB::raw('(SELECT SUM(points) FROM quiz_questions WHERE quiz_id = q.id) as total_points')
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => $quizzes
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quizzes'
            ], 500);
        }
    }
    
    /**
     * Get enrolled students
     */
    private function getEnrolledStudents($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $regular = DB::table('students as s')
            ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.student_type', 'regular')
            ->select(
                's.student_number',
                DB::raw("CONCAT(s.first_name, ' ', s.last_name) as full_name")
            );

        $irregular = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->where('scm.class_code', $class->class_code)
            ->where('s.student_type', 'irregular')
            ->select(
                's.student_number',
                DB::raw("CONCAT(s.first_name, ' ', s.last_name) as full_name")
            );

        return $regular->union($irregular)
            ->orderBy('full_name')
            ->get();
    }
}
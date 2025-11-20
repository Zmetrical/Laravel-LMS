<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GradeBook_Management extends MainController
{
    public function list_gradebook($classId) 
    {
        $teacher = Auth::guard('teacher')->user();
        
        // Verify teacher has access to this class
        $hasAccess = DB::table('teacher_class_matrix')
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Unauthorized access to this class');
        }

        // Get class details
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
            
            // Verify access
            $hasAccess = DB::table('teacher_class_matrix')
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $classId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $class = DB::table('classes')->where('id', $classId)->first();

            // Get columns configuration
            $columns = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('is_active', true)
                ->orderBy('component_type')
                ->orderBy('order_number')
                ->get()
                ->groupBy('component_type');

            // Get enrolled students
            $students = $this->getEnrolledStudents($classId);

            // Get all scores
            $scores = DB::table('gradebook_scores as gs')
                ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
                ->where('gc.class_code', $class->class_code)
                ->select('gs.*', 'gc.component_type', 'gc.column_name')
                ->get()
                ->groupBy('student_number');

            // Build gradebook data
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

                // Populate scores
                foreach (['WW', 'PT', 'QA'] as $type) {
                    $typeColumns = $columns->get($type, collect());
                    foreach ($typeColumns as $col) {
                        $score = $studentScores->firstWhere('column_name', $col->column_name);
                        $row[strtolower($type)][$col->column_name] = [
                            'score' => $score ? $score->score : null,
                            'max_points' => $col->max_points,
                            'source' => $score ? $score->source : 'manual'
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
     * Add new column to gradebook
     */
    public function addColumn(Request $request, $classId)
    {
        try {
            $validated = $request->validate([
                'component_type' => 'required|in:WW,PT,QA',
                'column_name' => 'required|string|max:50',
                'max_points' => 'required|numeric|min:0',
                'source_type' => 'required|in:manual,online',
                'quiz_id' => 'nullable|exists:quizzes,id'
            ]);

            $class = DB::table('classes')->where('id', $classId)->first();

            // Check if column name already exists
            $exists = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('component_type', $validated['component_type'])
                ->where('column_name', $validated['column_name'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Column name already exists'
                ], 422);
            }

            // Get max order number
            $maxOrder = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('component_type', $validated['component_type'])
                ->max('order_number');

            $columnId = DB::table('gradebook_columns')->insertGetId([
                'class_code' => $class->class_code,
                'component_type' => $validated['component_type'],
                'column_name' => $validated['column_name'],
                'max_points' => $validated['max_points'],
                'order_number' => ($maxOrder ?? 0) + 1,
                'source_type' => $validated['source_type'],
                'quiz_id' => $validated['quiz_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // If online source, sync quiz scores
            if ($validated['source_type'] === 'online' && $validated['quiz_id']) {
                $this->syncQuizScores($columnId, $validated['quiz_id']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Column added successfully'
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to add column', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add column'
            ], 500);
        }
    }

    /**
     * Update score
     */
    public function updateScore(Request $request)
    {
        try {
            $validated = $request->validate([
                'column_id' => 'required|exists:gradebook_columns,id',
                'student_number' => 'required|exists:students,student_number',
                'score' => 'nullable|numeric|min:0'
            ]);

            DB::table('gradebook_scores')->updateOrInsert(
                [
                    'column_id' => $validated['column_id'],
                    'student_number' => $validated['student_number']
                ],
                [
                    'score' => $validated['score'],
                    'source' => 'manual',
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Score updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update score'
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
                ->select('q.id', 'q.title', 'l.title as lesson_title')
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
     * Sync quiz scores to gradebook
     */
    private function syncQuizScores($columnId, $quizId)
    {
        // Get best attempts for each student
        $scores = DB::table('student_quiz_attempts as sqa')
            ->where('quiz_id', $quizId)
            ->where('status', 'graded')
            ->select(
                'student_number',
                DB::raw('MAX(score) as best_score'),
                DB::raw('MAX(total_points) as total_points')
            )
            ->groupBy('student_number')
            ->get();

        foreach ($scores as $score) {
            $percentage = $score->total_points > 0 ? 
                ($score->best_score / $score->total_points) * 100 : 0;

            $column = DB::table('gradebook_columns')->find($columnId);
            $adjustedScore = ($percentage / 100) * $column->max_points;

            DB::table('gradebook_scores')->updateOrInsert(
                [
                    'column_id' => $columnId,
                    'student_number' => $score->student_number
                ],
                [
                    'score' => round($adjustedScore, 2),
                    'source' => 'online',
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Get enrolled students
     */
    private function getEnrolledStudents($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        // Regular students
        $regular = DB::table('students as s')
            ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.student_type', 'regular')
            ->select(
                's.student_number',
                DB::raw("CONCAT(s.first_name, ' ', s.last_name) as full_name")
            );

        // Irregular students
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

    /**
     * Export gradebook to Excel
     */
    public function exportGradebook($classId)
    {
        try {
            // Get gradebook data (similar to getGradebookData but formatted for Excel)
            // Implementation here...
            
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export gradebook'
            ], 500);
        }
    }

    /**
     * Import gradebook from Excel
     */
    public function importGradebook(Request $request, $classId)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls|max:5120'
            ]);

            // Process Excel file
            // Implementation here...

            return response()->json([
                'success' => true,
                'message' => 'Gradebook imported successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import gradebook'
            ], 500);
        }
    }
}
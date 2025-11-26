<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


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

        /**
     * Export gradebook to Excel using template
     */
    public function exportGradebook(Request $request, $classId)
    {
        try {
            $teacher = Auth::guard('teacher')->user();
            
            // Check access
            $hasAccess = DB::table('teacher_class_matrix')
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $classId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $quarter = $request->input('quarter', '1st'); // '1st' or '2nd'
            
            // Get class and related data
            $class = DB::table('classes')->where('id', $classId)->first();
            $section = $this->getClassSection($classId);
            $semester = $this->getCurrentSemester();
            
            // Load template
            $templatePath = storage_path('app/templates/SHS-E-Class-Record.xlsx');
            
            if (!file_exists($templatePath)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Template file not found. Please contact administrator.'
                ], 404);
            }

            $spreadsheet = IOFactory::load($templatePath);
            
            // Populate INPUT DATA sheet
            $this->populateInputDataSheet($spreadsheet, $class, $section, $semester, $teacher);
            
            // Populate grade sheet based on quarter
            $sheetName = $quarter === '1st' ? '1ST' : '2ND';
            $this->populateGradeSheet($spreadsheet, $sheetName, $classId, $class);
            
            // Generate filename
            $filename = $this->generateFilename($class, $section, $quarter);
            $exportPath = storage_path('app/exports/' . $filename);
            
            // Ensure exports directory exists
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }
            
            // Save file
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($exportPath);
            
            return response()->download($exportPath, $filename)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            \Log::error('Failed to export gradebook', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export gradebook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Populate INPUT DATA sheet
     */
    private function populateInputDataSheet($spreadsheet, $class, $section, $semester, $teacher)
    {
        $sheet = $spreadsheet->getSheetByName('INPUT DATA');
        
        if (!$sheet) {
            throw new Exception('INPUT DATA sheet not found in template');
        }

        // Basic information
        $sheet->setCellValue('K7', $section->name ?? ''); // Grade & Section
        $sheet->setCellValue('S7', $teacher->first_name . ' ' . $teacher->last_name); // Teacher
        $sheet->setCellValue('S8', $semester->name ?? '1ST'); // Semester
        $sheet->setCellValue('AE7', $class->class_name); // Subject
        
        // Additional metadata
        $sheet->setCellValue('AE8', $class->class_code); // Store class code for reference

        // Get students (male first, then female)
        $maleStudents = $this->getEnrolledStudentsByGender($class->id, 'Male');
        $femaleStudents = $this->getEnrolledStudentsByGender($class->id, 'Female');
        
        // Populate male students (A13:B62)
        $row = 13;
        foreach ($maleStudents as $student) {
            $sheet->setCellValue('A' . $row, $student->student_number);
            $sheet->setCellValue('B' . $row, $student->full_name);
            $row++;
            if ($row > 62) break;
        }
        
        // Populate female students (A64:B113)
        $row = 64;
        foreach ($femaleStudents as $student) {
            $sheet->setCellValue('A' . $row, $student->student_number);
            $sheet->setCellValue('B' . $row, $student->full_name);
            $row++;
            if ($row > 113) break;
        }
    }

    /**
     * Populate grade sheet (1ST or 2ND)
     */
    private function populateGradeSheet($spreadsheet, $sheetName, $classId, $class)
    {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        
        if (!$sheet) {
            throw new Exception($sheetName . ' sheet not found in template');
        }

        // Get columns and scores
        $columns = DB::table('gradebook_columns')
            ->where('class_code', $class->class_code)
            ->where('is_active', true)
            ->orderBy('component_type')
            ->orderBy('order_number')
            ->get()
            ->groupBy('component_type');

        // Written Work columns (F11:O11)
        $this->populateComponentColumns($sheet, $columns->get('WW', collect()), 'F', 11, 10);
        
        // Performance Tasks columns (S11:AB11)
        $this->populateComponentColumns($sheet, $columns->get('PT', collect()), 'S', 11, 10);
        
        // Quarterly Assessment (AF11)
        $qaColumns = $columns->get('QA', collect());
        if ($qaColumns->isNotEmpty()) {
            $qaMaxScore = $qaColumns->sum('max_points');
            $sheet->setCellValue('AF11', $qaMaxScore);
        }

        // Get students and their scores
        $students = $this->getEnrolledStudentsWithGender($classId);
        $scores = $this->getStudentScores($class->class_code, $columns);

        // Populate student scores
        $maleRow = 13;
        $femaleRow = 64;
        
        foreach ($students as $student) {
            $row = $student->gender === 'Male' ? $maleRow : $femaleRow;
            
            if (($student->gender === 'Male' && $row > 62) || 
                ($student->gender === 'Female' && $row > 113)) {
                continue;
            }

            $studentScores = $scores->get($student->student_number, []);
            
            // Written Work scores (F:O)
            $this->populateStudentScores($sheet, $row, $columns->get('WW', collect()), $studentScores, 'F');
            
            // Performance Tasks scores (S:AB)
            $this->populateStudentScores($sheet, $row, $columns->get('PT', collect()), $studentScores, 'S');
            
            // Quarterly Assessment score (AF)
            $this->populateQAScore($sheet, $row, $columns->get('QA', collect()), $studentScores);

            if ($student->gender === 'Male') {
                $maleRow++;
            } else {
                $femaleRow++;
            }
        }
    }

    /**
     * Populate component columns (WW or PT)
     */
    private function populateComponentColumns($sheet, $columns, $startCol, $row, $maxCols)
    {
        $colIndex = 0;
        foreach ($columns as $column) {
            if ($colIndex >= $maxCols) break;
            
            $currentCol = chr(ord($startCol) + $colIndex);
            $sheet->setCellValue($currentCol . $row, $column->max_points);
            $colIndex++;
        }
    }

    /**
     * Populate student scores for WW or PT
     */
    private function populateStudentScores($sheet, $row, $columns, $studentScores, $startCol)
    {
        $colIndex = 0;
        foreach ($columns as $column) {
            if ($colIndex >= 10) break;
            
            $currentCol = chr(ord($startCol) + $colIndex);
            $score = $studentScores[$column->column_name] ?? null;
            
            if ($score !== null) {
                $sheet->setCellValue($currentCol . $row, $score);
            }
            
            $colIndex++;
        }
    }

    /**
     * Populate QA score
     */
    private function populateQAScore($sheet, $row, $qaColumns, $studentScores)
    {
        if ($qaColumns->isEmpty()) return;
        
        // Sum all QA scores for this student
        $totalScore = 0;
        foreach ($qaColumns as $column) {
            $score = $studentScores[$column->column_name] ?? 0;
            $totalScore += $score;
        }
        
        if ($totalScore > 0) {
            $sheet->setCellValue('AF' . $row, $totalScore);
        }
    }

    /**
     * Get student scores indexed by student_number and column_name
     */
    private function getStudentScores($classCode, $columns)
    {
        $columnIds = collect();
        foreach ($columns as $typeColumns) {
            $columnIds = $columnIds->merge($typeColumns->pluck('id'));
        }

        $scores = DB::table('gradebook_scores as gs')
            ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
            ->whereIn('gs.column_id', $columnIds)
            ->select('gs.student_number', 'gc.column_name', 'gs.score')
            ->get();

        $grouped = [];
        foreach ($scores as $score) {
            if (!isset($grouped[$score->student_number])) {
                $grouped[$score->student_number] = [];
            }
            $grouped[$score->student_number][$score->column_name] = $score->score;
        }

        return collect($grouped);
    }

    /**
     * Get enrolled students with gender
     */
    private function getEnrolledStudentsWithGender($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $regular = DB::table('students as s')
            ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.student_type', 'regular')
            ->select(
                's.student_number',
                's.gender',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        $irregular = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->where('scm.class_code', $class->class_code)
            ->where('s.student_type', 'irregular')
            ->select(
                's.student_number',
                's.gender',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        return $regular->union($irregular)
            ->orderBy('gender', 'desc') // Male first
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get students by gender
     */
    private function getEnrolledStudentsByGender($classId, $gender)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $regular = DB::table('students as s')
            ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.student_type', 'regular')
            ->where('s.gender', $gender)
            ->select(
                's.student_number',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        $irregular = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->where('scm.class_code', $class->class_code)
            ->where('s.student_type', 'irregular')
            ->where('s.gender', $gender)
            ->select(
                's.student_number',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        return $regular->union($irregular)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get class section
     */
    private function getClassSection($classId)
    {
        return DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->select('sec.*')
            ->first();
    }

    /**
     * Get current semester
     */
    private function getCurrentSemester()
    {
        return DB::table('semesters')
            ->where('status', 'active')
            ->first();
    }

    /**
     * Generate export filename
     */
    private function generateFilename($class, $section, $quarter)
    {
        $classCode = str_replace(' ', '_', $class->class_code);
        $sectionName = $section ? str_replace(' ', '_', $section->name) : 'NoSection';
        $timestamp = now()->format('Ymd_His');
        
        return "{$classCode}_{$sectionName}_{$quarter}Q_{$timestamp}.xlsx";
    }
}
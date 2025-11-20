<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
     * Get available sheets from uploaded Excel file
     */
    public function getExcelSheets(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls|max:5120'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            $sheets = [];
            foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
                $sheets[] = [
                    'index' => $index,
                    'name' => $sheet->getTitle(),
                    'row_count' => $sheet->getHighestRow()
                ];
            }

            return response()->json([
                'success' => true,
                'sheets' => $sheets
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to read Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import gradebook from Excel with sheet selection
     */
    public function importGradebook(Request $request, $classId)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls|max:5120',
                'action' => 'required|in:replace,keep',
                'sheet_index' => 'required|integer|min:0'
            ]);

            $teacher = Auth::guard('teacher')->user();
            $class = DB::table('classes')->where('id', $classId)->first();

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            // Get the selected sheet
            $sheetIndex = $request->sheet_index;
            $allSheets = $spreadsheet->getAllSheets();
            
            if (!isset($allSheets[$sheetIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid sheet index selected.'
                ], 400);
            }
            
            $sheet = $allSheets[$sheetIndex];
            $data = $sheet->toArray();

            if (count($data) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file format. File must contain proper template structure.'
                ], 400);
            }

            // Find the header rows
            $componentRow = null;
            $columnNumberRow = null;
            $dataStartRow = null;
            $studentNameCol = null;

            // Scan for "WRITTEN WORK", "PERFORMANCE TASK", "QUARTERLY ASSESSMENT" headers
            for ($i = 0; $i < count($data); $i++) {
                $rowText = implode('|', array_map('strtoupper', $data[$i]));
                
                // Find row with component headers (WRITTEN WORK, PERFORMANCE TASK, etc)
                if (stripos($rowText, 'WRITTEN WORK') !== false || 
                    stripos($rowText, 'PERFORMANCE TASK') !== false ||
                    stripos($rowText, 'QUARTERLY ASSESSMENT') !== false) {
                    $componentRow = $i;
                    continue;
                }
                
                // Find row with column numbers (1, 2, 3, 4...)
                if ($componentRow !== null && $columnNumberRow === null) {
                    $hasNumbers = false;
                    foreach ($data[$i] as $cell) {
                        if (is_numeric($cell) && intval($cell) > 0 && intval($cell) <= 10) {
                            $hasNumbers = true;
                            break;
                        }
                    }
                    if ($hasNumbers) {
                        $columnNumberRow = $i;
                        $dataStartRow = $i + 1;
                        break;
                    }
                }
            }

            if ($componentRow === null || $columnNumberRow === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not find gradebook structure. Please use the correct template.'
                ], 400);
            }

            // Find student name column (LEARNERS' NAMES)
            foreach ($data[$componentRow] as $colIndex => $cell) {
                if (stripos($cell, "LEARNER") !== false || stripos($cell, "NAME") !== false) {
                    $studentNameCol = $colIndex;
                    break;
                }
            }

            if ($studentNameCol === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not find student names column.'
                ], 400);
            }

            // Parse column structure
            $columnMapping = $this->parseTemplateColumns(
                $data[$componentRow], 
                $data[$columnNumberRow], 
                $class->class_code, 
                $request->action,
                $studentNameCol
            );

            if (!$columnMapping['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $columnMapping['message']
                ], 400);
            }

            DB::beginTransaction();

            $imported = 0;
            $errors = [];

            // Process data rows
            for ($i = $dataStartRow; $i < count($data); $i++) {
                $row = $data[$i];
                $studentName = trim($row[$studentNameCol] ?? '');

                if (empty($studentName)) continue;

                // Try to match student by name (last name, first name pattern)
                $nameParts = $this->parseStudentName($studentName);
                
                $student = DB::table('students')
                    ->join('student_class_matrix', 'students.student_number', '=', 'student_class_matrix.student_number')
                    ->where('student_class_matrix.class_code', $class->class_code)
                    ->where(function($query) use ($nameParts) {
                        if (!empty($nameParts['last'])) {
                            $query->where('students.last_name', 'LIKE', '%' . $nameParts['last'] . '%');
                        }
                        if (!empty($nameParts['first'])) {
                            $query->where('students.first_name', 'LIKE', '%' . $nameParts['first'] . '%');
                        }
                    })
                    ->select('students.student_number')
                    ->first();

                if (!$student) {
                    $errors[] = "Row " . ($i + 1) . ": Could not match student '$studentName'";
                    continue;
                }

                // Import scores for each mapped column
                foreach ($columnMapping['columns'] as $excelColIndex => $dbColumnId) {
                    $score = $row[$excelColIndex] ?? null;
                    
                    if ($score !== null && $score !== '' && is_numeric($score)) {
                        if ($request->action === 'replace') {
                            DB::table('gradebook_scores')->updateOrInsert(
                                [
                                    'column_id' => $dbColumnId,
                                    'student_number' => $student->student_number
                                ],
                                [
                                    'score' => floatval($score),
                                    'source' => 'imported',
                                    'updated_at' => now()
                                ]
                            );
                        } else {
                            // Only insert if no score exists
                            $existing = DB::table('gradebook_scores')
                                ->where('column_id', $dbColumnId)
                                ->where('student_number', $student->student_number)
                                ->first();
                            
                            if (!$existing || $existing->score === null) {
                                DB::table('gradebook_scores')->updateOrInsert(
                                    [
                                        'column_id' => $dbColumnId,
                                        'student_number' => $student->student_number
                                    ],
                                    [
                                        'score' => floatval($score),
                                        'source' => 'imported',
                                        'updated_at' => now()
                                    ]
                                );
                            }
                        }
                    }
                }

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported scores for $imported students from sheet '{$sheet->getTitle()}'",
                'errors' => $errors,
                'new_columns' => $columnMapping['new_columns'] ?? []
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse template columns based on position
     */
    private function parseTemplateColumns($componentRow, $numberRow, $classCode, $action, $studentNameCol)
    {
        $columnMapping = [];
        $newColumns = [];
        $currentComponent = null;

        foreach ($componentRow as $colIndex => $header) {
            if ($colIndex <= $studentNameCol) continue; // Skip before student names

            $headerUpper = strtoupper(trim($header));
            
            // Detect component type
            if (stripos($headerUpper, 'WRITTEN WORK') !== false) {
                $currentComponent = 'WW';
            } elseif (stripos($headerUpper, 'PERFORMANCE TASK') !== false) {
                $currentComponent = 'PT';
            } elseif (stripos($headerUpper, 'QUARTERLY ASSESSMENT') !== false) {
                $currentComponent = 'QA';
            }

            // Check if this column has a number in the number row
            $columnNumber = $numberRow[$colIndex] ?? null;
            
            if ($currentComponent && is_numeric($columnNumber) && intval($columnNumber) > 0) {
                $orderNumber = intval($columnNumber);

                // Find or create column in database
                $column = DB::table('gradebook_columns')
                    ->where('class_code', $classCode)
                    ->where('component_type', $currentComponent)
                    ->where('order_number', $orderNumber)
                    ->first();

                if ($column) {
                    if ($action === 'replace') {
                        // Clear existing scores for this column
                        DB::table('gradebook_scores')
                            ->where('column_id', $column->id)
                            ->where('source', '!=', 'online') // Don't delete online quiz scores
                            ->delete();
                    }
                    $columnMapping[$colIndex] = $column->id;
                } else {
                    // Create new column
                    $columnName = $currentComponent . $orderNumber;
                    
                    // Check column limit
                    $currentCount = DB::table('gradebook_columns')
                        ->where('class_code', $classCode)
                        ->where('component_type', $currentComponent)
                        ->count();

                    if ($currentCount >= self::MAX_COLUMNS_PER_TYPE) {
                        return [
                            'success' => false,
                            'message' => "Cannot create $columnName: Maximum columns reached for $currentComponent"
                        ];
                    }

                    // Determine default max points based on component type
                    $maxPoints = match($currentComponent) {
                        'WW' => 10,
                        'PT' => 20,
                        'QA' => 50,
                        default => 10
                    };

                    $columnId = DB::table('gradebook_columns')->insertGetId([
                        'class_code' => $classCode,
                        'component_type' => $currentComponent,
                        'column_name' => $columnName,
                        'max_points' => $maxPoints,
                        'order_number' => $orderNumber,
                        'source_type' => 'imported',
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $columnMapping[$colIndex] = $columnId;
                    $newColumns[] = $columnName;
                }
            }
        }

        if (empty($columnMapping)) {
            return [
                'success' => false,
                'message' => 'No valid gradebook columns found in the template'
            ];
        }

        return [
            'success' => true,
            'columns' => $columnMapping,
            'new_columns' => $newColumns
        ];
    }

    /**
     * Parse student name from Excel (handles "Last Name, First Name" format)
     */
    private function parseStudentName($fullName)
    {
        $parts = ['first' => '', 'last' => ''];
        
        if (strpos($fullName, ',') !== false) {
            // Format: "Last Name, First Name"
            $nameParts = explode(',', $fullName);
            $parts['last'] = trim($nameParts[0]);
            $parts['first'] = trim($nameParts[1] ?? '');
        } else {
            // Try to split by space
            $nameParts = explode(' ', $fullName);
            if (count($nameParts) >= 2) {
                $parts['first'] = trim($nameParts[0]);
                $parts['last'] = trim(implode(' ', array_slice($nameParts, 1)));
            } else {
                $parts['last'] = trim($fullName);
            }
        }
        
        return $parts;
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
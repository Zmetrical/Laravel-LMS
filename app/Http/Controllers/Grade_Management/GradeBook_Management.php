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
    const MAX_WW_COLUMNS = 10;
    const MAX_PT_COLUMNS = 10;
    const MAX_QA_COLUMNS = 1;
    

    /**
     * View-only gradebook page for exporting
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
        
        // Get active quarters for current semester
        $quarters = DB::table('quarters as q')
            ->join('semesters as s', 'q.semester_id', '=', 's.id')
            ->where('s.status', 'active')
            ->orderBy('q.order_number')
            ->select('q.*')
            ->get();
        // Get sections for this class
        $sections = DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->select('sec.id', 'sec.name', 'sec.code')
            ->get();
        $data = [
            'scripts' => ['grade_management/view_gradebook.js'],
            'classId' => $classId,
            'class' => $class,
            'quarters' => $quarters,
            'sections' => $sections
        ];

        return view('teacher.gradebook.view_gradebook', $data);
    }
    public function edit_gradebook($classId) 
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
        
        // Get active quarters for current semester
        $quarters = DB::table('quarters as q')
            ->join('semesters as s', 'q.semester_id', '=', 's.id')
            ->where('s.status', 'active')
            ->orderBy('q.order_number')
            ->select('q.*')
            ->get();
        // Get sections for this class
        $sections = DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->select('sec.id', 'sec.name', 'sec.code')
            ->get();
        $data = [
            'scripts' => ['grade_management/edit_gradebook.js'],
            'classId' => $classId,
            'class' => $class,
            'quarters' => $quarters,
            'sections' => $sections
        ];

        return view('teacher.gradebook.edit_gradebook', $data);
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
        if (!$quarterId) {
            return response()->json(['success' => false, 'message' => 'Quarter ID required'], 400);
        }

        $class = DB::table('classes')->where('id', $classId)->first();
        $quarter = DB::table('quarters')->where('id', $quarterId)->first();

        // Get or create columns for this quarter
        $this->ensureColumnsExist($class->class_code, $quarterId);

        $columns = DB::table('gradebook_columns')
            ->where('class_code', $class->class_code)
            ->where('quarter_id', $quarterId)
            ->orderBy('component_type')
            ->orderBy('order_number')
            ->get()
            ->groupBy('component_type');

        $students = $this->getEnrolledStudents($classId);

        $scores = DB::table('gradebook_scores as gs')
            ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
            ->where('gc.class_code', $class->class_code)
            ->where('gc.quarter_id', $quarterId)
            ->select('gs.*', 'gc.component_type', 'gc.column_name')
            ->get()
            ->groupBy('student_number');

        // Get quiz scores for online columns
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
                    
                    // Check if this column has quiz data
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
 * Get enrolled students with gender and section info
 */
private function getEnrolledStudents($classId)
{
    $class = DB::table('classes')->where('id', $classId)->first();

    // Regular students (enrolled via section)
    $regular = DB::table('students as s')
        ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
        ->where('scm.class_id', $classId)
        ->where('s.student_type', 'regular')
        ->select(
            's.student_number',
            's.gender',
            's.section_id',
            DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
        );

    // Irregular students (enrolled individually)
    $irregular = DB::table('students as s')
        ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
        ->where('scm.class_code', $class->class_code)
        ->where('scm.enrollment_status', 'enrolled')
        ->where('s.student_type', 'irregular')
        ->select(
            's.student_number',
            's.gender',
            's.section_id',
            DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
        );

    // Union both queries and order by gender then name
    return $regular->union($irregular)
        ->orderBy('gender')
        ->orderBy('full_name')
        ->get();
}

/**
 * Ensure all columns exist for a quarter
 */
private function ensureColumnsExist($classCode, $quarterId)
{
    // Get existing columns for THIS specific quarter
    $existingColumns = DB::table('gradebook_columns')
        ->where('class_code', $classCode)
        ->where('quarter_id', $quarterId)
        ->get()
        ->groupBy('component_type');

    // Create WW columns (1-10) only if they don't exist for this quarter
    $existingWW = $existingColumns->get('WW', collect());
    $wwCount = $existingWW->count();
    
    for ($i = $wwCount + 1; $i <= self::MAX_WW_COLUMNS; $i++) {
        // Double-check to prevent duplicates
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

    // Create PT columns (1-10) only if they don't exist for this quarter
    $existingPT = $existingColumns->get('PT', collect());
    $ptCount = $existingPT->count();
    
    for ($i = $ptCount + 1; $i <= self::MAX_PT_COLUMNS; $i++) {
        // Double-check to prevent duplicates
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

    // Create QA column (only 1) only if it doesn't exist for this quarter
    $existingQA = $existingColumns->get('QA', collect());
    $qaCount = $existingQA->count();
    
    if ($qaCount === 0) {
        // Double-check to prevent duplicates
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
    /**
     * Get quiz scores properly calculated
     */
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



    /**
     * Enable/activate a column
     */
public function toggleColumn(Request $request, $classId, $columnId)
{
    try {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'max_points' => 'nullable|integer|min:1',
            'quiz_id' => 'nullable|exists:quizzes,id'
        ]);

        $column = DB::table('gradebook_columns')->where('id', $columnId)->first();

        if (!$column) {
            return response()->json(['success' => false, 'message' => 'Column not found'], 404);
        }

        $updateData = [
            'is_active' => $validated['is_active'],
            'updated_at' => now()
        ];

        if ($validated['is_active']) {
            if (isset($validated['max_points'])) {
                $updateData['max_points'] = $validated['max_points'];
            }
            
            if (isset($validated['quiz_id']) && $validated['quiz_id']) {
                $updateData['quiz_id'] = $validated['quiz_id'];
                $updateData['source_type'] = 'online';
            } else {
                $updateData['quiz_id'] = null;
                $updateData['source_type'] = 'manual';
            }
        }

        DB::table('gradebook_columns')
            ->where('id', $columnId)
            ->update($updateData);

        return response()->json([
            'success' => true,
            'message' => $validated['is_active'] ? 'Column enabled successfully' : 'Column disabled successfully'
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update column: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Update column configuration
     */
public function updateColumn(Request $request, $classId, $columnId)
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
    public function getAvailableQuizzes($classId, Request $request)
    {
        try {
            $quarterId = $request->input('quarter_id');
            
            \Log::info('Getting quizzes', [
                'class_id' => $classId,
                'quarter_id' => $quarterId
            ]);
            
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

            // Get already mapped quizzes for this quarter AND class
            $mappedQuizIds = DB::table('gradebook_columns')
                ->where('class_code', $class->class_code)
                ->where('quarter_id', $quarterId)
                ->whereNotNull('quiz_id')
                ->where('is_active', 1) // Only consider active columns as "mapped"
                ->pluck('quiz_id')
                ->toArray();
                    
            \Log::info('Mapped quiz IDs', ['ids' => $mappedQuizIds]);

            // Get available quizzes - matching the quarter OR having no quarter assigned
            $quizzes = DB::table('quizzes as q')
                ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
                ->where('l.class_id', $classId)
                ->where('q.status', 1) // Only active quizzes
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
                    
            \Log::info('Available quizzes', [
                'count' => $quizzes->count(),
                'quizzes' => $quizzes->toArray()
            ]);

            // If no quizzes found, return helpful message
            if ($quizzes->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No available quizzes found for this quarter. Create a quiz first or assign an existing quiz to this quarter.'
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
        

    /**
     * Export gradebook to Excel using template
     */
    public function exportGradebook(Request $request, $classId)
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
            
            $class = DB::table('classes')->where('id', $classId)->first();
            $quarter = DB::table('quarters')->where('id', $quarterId)->first();
            $section = $this->getClassSection($classId);
            $semester = DB::table('semesters')->where('id', $quarter->semester_id)->first();
            
            $templatePath = storage_path('app/templates/SHS-E-Class-Record.xlsx');
            
            if (!file_exists($templatePath)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Template file not found. Please contact administrator.'
                ], 404);
            }

            $spreadsheet = IOFactory::load($templatePath);
            
            $this->populateInputDataSheet($spreadsheet, $class, $section, $semester, $teacher, $quarter);
            
            $sheetName = $quarter->code === 'Q1' ? '1ST' : '2ND';
            $this->populateGradeSheet($spreadsheet, $sheetName, $classId, $class, $quarterId);
            
            $filename = $this->generateFilename($class, $section, $quarter);
            $exportPath = storage_path('app/exports/' . $filename);
            
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }
            
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

private function populateInputDataSheet($spreadsheet, $class, $section, $semester, $teacher, $quarter)
{
    $sheet = $spreadsheet->getSheetByName('INPUT DATA');
    
    if (!$sheet) {
        throw new Exception('INPUT DATA sheet not found in template');
    }

    // Get school year from semester
    $schoolYear = DB::table('school_years')
        ->where('id', $semester->school_year_id)
        ->first();

    // Basic Info
    $sheet->setCellValue('K7', $section->name ?? '');
    $sheet->setCellValue('S7', $teacher->first_name . ' ' . $teacher->last_name);
    $sheet->setCellValue('S8', $quarter->code === 'Q1' ? '1ST' : '2ND'); // Changed from semester name
    $sheet->setCellValue('AE7', $class->class_name);
    $sheet->setCellValue('AE8', $class->class_code);
    $sheet->setCellValue('AG5', $schoolYear ? $schoolYear->code : ''); // Added school year

    // Get students by gender
    $maleStudents = $this->getEnrolledStudentsByGender($class->id, 'Male');
    $femaleStudents = $this->getEnrolledStudentsByGender($class->id, 'Female');
    
    // Male students (A13-A62, B13-B62)
    $row = 13;
    foreach ($maleStudents as $student) {
        $sheet->setCellValue('A' . $row, $student->student_number);
        $sheet->setCellValue('B' . $row, $student->full_name);
        $row++;
        if ($row > 62) break;
    }
    
    // Female students (A64-A113, B64-B113)
    $row = 64;
    foreach ($femaleStudents as $student) {
        $sheet->setCellValue('A' . $row, $student->student_number);
        $sheet->setCellValue('B' . $row, $student->full_name);
        $row++;
        if ($row > 113) break;
    }
}


private function populateGradeSheet($spreadsheet, $sheetName, $classId, $class, $quarterId)
{
    $sheet = $spreadsheet->getSheetByName($sheetName);
    
    if (!$sheet) {
        throw new Exception($sheetName . ' sheet not found in template');
    }

    // Get quarter info
    $quarter = DB::table('quarters')->where('id', $quarterId)->first();
    $section = $this->getClassSection($classId);
    $teacher = Auth::guard('teacher')->user();

    // Set basic info
    $sheet->setCellValue('K7', $section->name ?? '');
    $sheet->setCellValue('S7', $teacher->first_name . ' ' . $teacher->last_name);
    $sheet->setCellValue('S8', $quarter->code === 'Q1' ? '1ST' : '2ND');
    $sheet->setCellValue('AE7', $class->class_name);

    // Get columns
    $columns = DB::table('gradebook_columns')
        ->where('class_code', $class->class_code)
        ->where('quarter_id', $quarterId)
        ->where('is_active', true)
        ->orderBy('component_type')
        ->orderBy('order_number')
        ->get()
        ->groupBy('component_type');

    // Written Work (F11-O11)
    $this->populateComponentColumns($sheet, $columns->get('WW', collect()), 'F', 11, 10);
    
    // Performance Task (S11-AB11)
    $this->populateComponentColumns($sheet, $columns->get('PT', collect()), 'S', 11, 10);
    
    // Quarterly Assessment (AF11)
    $qaColumns = $columns->get('QA', collect());
    if ($qaColumns->isNotEmpty()) {
        $qaMaxScore = $qaColumns->sum('max_points');
        $sheet->setCellValue('AF11', $qaMaxScore);
    }

    // Get students and scores
    $students = $this->getEnrolledStudentsWithGender($classId);
    $scores = $this->getStudentScores($class->class_code, $columns, $quarterId);

    $maleRow = 13;
    $femaleRow = 64;
    
    foreach ($students as $student) {
        $row = $student->gender === 'Male' ? $maleRow : $femaleRow;
        
        if (($student->gender === 'Male' && $row > 62) || 
            ($student->gender === 'Female' && $row > 113)) {
            continue;
        }

        // Set student info
        $sheet->setCellValue('A' . $row, $student->student_number);
        $sheet->setCellValue('B' . $row, $student->full_name);

        $studentScores = $scores->get($student->student_number, []);
        
        // Written Work scores (F-O)
        $this->populateStudentScores($sheet, $row, $columns->get('WW', collect()), $studentScores, 'F');
        
        // Performance Task scores (S-AB)
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
private function populateComponentColumns($sheet, $columns, $startCol, $row, $maxCols)
{
    $colIndex = 0;
    foreach ($columns as $column) {
        if ($colIndex >= $maxCols) break;
        
        // Use PhpSpreadsheet's built-in column index conversion
        $currentCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) + $colIndex
        );
        
        $sheet->setCellValue($currentCol . $row, $column->max_points);
        $colIndex++;
    }
}

private function populateStudentScores($sheet, $row, $columns, $studentScores, $startCol)
{
    $colIndex = 0;
    foreach ($columns as $column) {
        if ($colIndex >= 10) break;
        
        // Use PhpSpreadsheet's built-in column index conversion
        $currentCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) + $colIndex
        );
        
        $score = $studentScores[$column->column_name] ?? null;
        
        if ($score !== null) {
            $sheet->setCellValue($currentCol . $row, $score);
        }
        
        $colIndex++;
    }
}

    private function populateQAScore($sheet, $row, $qaColumns, $studentScores)
    {
        if ($qaColumns->isEmpty()) return;
        
        $totalScore = 0;
        foreach ($qaColumns as $column) {
            $score = $studentScores[$column->column_name] ?? 0;
            $totalScore += $score;
        }
        
        if ($totalScore > 0) {
            $sheet->setCellValue('AF' . $row, $totalScore);
        }
    }

    private function getStudentScores($classCode, $columns, $quarterId)
    {
        $columnIds = collect();
        foreach ($columns as $typeColumns) {
            $columnIds = $columnIds->merge($typeColumns->pluck('id'));
        }

        $scores = DB::table('gradebook_scores as gs')
            ->join('gradebook_columns as gc', 'gs.column_id', '=', 'gc.id')
            ->whereIn('gs.column_id', $columnIds)
            ->where('gc.quarter_id', $quarterId)
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
            ->orderBy('gender', 'desc')
            ->orderBy('full_name')
            ->get();
    }

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

    private function getClassSection($classId)
    {
        return DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->select('sec.*')
            ->first();
    }

private function generateFilename($class, $section, $quarter)
{
    $classCode = str_replace(' ', '_', $class->class_code);
    $sectionName = $section ? str_replace(' ', '_', $section->name) : 'NoSection';
    $quarterCode = $quarter->code; // Use Q1 or Q2
    $timestamp = now()->format('Ymd_His');
    
    return "{$classCode}_{$sectionName}_{$quarterCode}_{$timestamp}.xlsx";
}
}
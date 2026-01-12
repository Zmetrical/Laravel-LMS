<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Helpers\GradeTransmutation;

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
            'scripts' => ['gradebook/view_gradebook.js'],
            'classId' => $classId,
            'class' => $class,
            'quarters' => $quarters,
            'sections' => $sections
        ];

        return view('teacher.gradebook.view_gradebook', $data);
    }


    // Add this new method to verify passcode
public function verify_passcode(Request $request, $classId)
{
    $request->validate([
        'passcode' => 'required'
    ]);

    $teacher = Auth::guard('teacher')->user();
    
    // Get plain passcode from teacher_password_matrix
    $plainPasscode = DB::table('teacher_password_matrix')
        ->where('teacher_id', $teacher->id)
        ->value('plain_passcode');

    if ($request->passcode === $plainPasscode) {
        // Store verified status in session with timestamp
        session([
            'gradebook_passcode_verified_' . $classId => true,
            'gradebook_passcode_time_' . $classId => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Passcode verified successfully',
            'redirect' => route('teacher.gradebook.edit', ['classId' => $classId])
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid passcode. Please try again.'
    ], 401);
}

// Update the edit_gradebook method
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

    // Check if passcode was verified recently (within last 5 minutes)
    $sessionKey = 'gradebook_passcode_verified_' . $classId;
    $timeKey = 'gradebook_passcode_time_' . $classId;
    
    if (!session($sessionKey) || 
        !session($timeKey) || 
        now()->diffInMinutes(session($timeKey)) > 5) {
        // Clear expired session
        session()->forget([$sessionKey, $timeKey]);
        return redirect()->route('teacher.gradebook.view', ['classId' => $classId])
            ->with('error', 'Please verify your passcode to access edit mode');
    }

    // Clear the session after successful access
    session()->forget([$sessionKey, $timeKey]);

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
        'scripts' => ['gradebook/edit_gradebook.js'],
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
        $sectionId = $request->input('section_id');
        
        if (!$quarterId) {
            return response()->json(['success' => false, 'message' => 'Quarter ID required'], 400);
        }
        
        if (!$sectionId) {
            return response()->json(['success' => false, 'message' => 'Section ID required'], 400);
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

        // FILTER STUDENTS BY SECTION - Use the section-specific method
        $students = $this->getEnrolledStudentsBySection($classId, $sectionId);

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
 * Export gradebook to Excel using template - SECTION SPECIFIC
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

        // Validate section_id is required
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id'
        ]);

        $sectionId = $validated['section_id'];
        
        $class = DB::table('classes')->where('id', $classId)->first();
        $section = DB::table('sections')->where('id', $sectionId)->first();
        
        if (!$section) {
            return response()->json([
                'success' => false, 
                'message' => 'Section not found'
            ], 404);
        }
        
        // Get the active semester
        $activeSemester = DB::table('semesters')->where('status', 'active')->first();
        if (!$activeSemester) {
            return response()->json([
                'success' => false, 
                'message' => 'No active semester found'
            ], 404);
        }
        
        // Get both quarters for the active semester
        $quarters = DB::table('quarters')
            ->where('semester_id', $activeSemester->id)
            ->orderBy('order_number')
            ->get();
            
        if ($quarters->count() < 2) {
            return response()->json([
                'success' => false, 
                'message' => 'Both quarters must be configured for the active semester'
            ], 404);
        }
        
        // Use order_number to get Q1 and Q2
        $q1 = $quarters->where('order_number', 1)->first();
        $q2 = $quarters->where('order_number', 2)->first();
        
        if (!$q1 || !$q2) {
            return response()->json([
                'success' => false, 
                'message' => 'Q1 and Q2 quarters not found'
            ], 404);
        }
        
        $templatePath = storage_path('app/templates/SHS-E-Class-Record.xlsx');
        
        if (!file_exists($templatePath)) {
            return response()->json([
                'success' => false, 
                'message' => 'Template file not found. Please contact administrator.'
            ], 404);
        }

        $spreadsheet = IOFactory::load($templatePath);
        
        // Populate INPUT DATA sheet once - with section parameter
        $this->populateInputDataSheet($spreadsheet, $class, $section, $activeSemester, $teacher, $q1, $sectionId);
        
        // Populate 1ST quarter sheet (Q1) - with section parameter
        $this->populateGradeSheet($spreadsheet, '1ST', $classId, $class, $q1->id, $sectionId);
        
        // Populate 2ND quarter sheet (Q2) - with section parameter
        $this->populateGradeSheet($spreadsheet, '2ND', $classId, $class, $q2->id, $sectionId);
        
        $filename = $this->generateFilename($class, $section, $activeSemester);
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

private function populateInputDataSheet($spreadsheet, $class, $section, $semester, $teacher, $quarter, $sectionId)
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
    $sheet->setCellValue('S8', $quarter->code === 'Q1' ? '1ST' : '2ND');
    $sheet->setCellValue('AE7', $class->class_name);
    $sheet->setCellValue('AE8', $class->class_code);
    $sheet->setCellValue('AG5', $schoolYear ? $schoolYear->code : '');

    // Get students by gender FOR THIS SECTION ONLY
    $maleStudents = $this->getEnrolledStudentsByGenderAndSection($class->id, 'Male', $sectionId);
    $femaleStudents = $this->getEnrolledStudentsByGenderAndSection($class->id, 'Female', $sectionId);
    
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
/**
 * Get enrolled students by gender and section
 */
private function getEnrolledStudentsByGenderAndSection($classId, $gender, $sectionId)
{
    $class = DB::table('classes')->where('id', $classId)->first();

    $regular = DB::table('students as s')
        ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
        ->where('scm.class_id', $classId)
        ->where('s.section_id', $sectionId)
        ->where('s.student_type', 'regular')
        ->where('s.gender', $gender)
        ->select(
            's.student_number',
            DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
        );

    $irregular = DB::table('students as s')
        ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
        ->where('scm.class_code', $class->class_code)
        ->where('s.section_id', $sectionId)
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
private function populateGradeSheet($spreadsheet, $sheetName, $classId, $class, $quarterId, $sectionId)
{
    $sheet = $spreadsheet->getSheetByName($sheetName);
    
    if (!$sheet) {
        throw new Exception($sheetName . ' sheet not found in template');
    }

    // Get quarter info
    $quarter = DB::table('quarters')->where('id', $quarterId)->first();
    $section = DB::table('sections')->where('id', $sectionId)->first();
    $teacher = Auth::guard('teacher')->user();
    
    // Get school year from semester
    $semester = DB::table('semesters')->where('id', $quarter->semester_id)->first();
    $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

    // Set basic info
    $sheet->setCellValue('K7', $section->name ?? '');
    $sheet->setCellValue('S7', $teacher->first_name . ' ' . $teacher->last_name);
    $sheet->setCellValue('S8', $quarter->order_number == 1 ? '1ST' : '2ND');
    $sheet->setCellValue('AE7', $class->class_name);
    $sheet->setCellValue('AG5', $schoolYear ? $schoolYear->code : '');
    
    // Set component percentage LABELS in row 9
    $sheet->setCellValue('F9', "Written Work ({$class->ww_perc}%)");
    $sheet->setCellValue('S9', "Performance Task ({$class->pt_perc}%)");
    $sheet->setCellValue('AF9', "Quarterly Assessment ({$class->qa_perce}%)");

    // Get columns
    $columns = DB::table('gradebook_columns')
        ->where('class_code', $class->class_code)
        ->where('quarter_id', $quarterId)
        ->where('is_active', true)
        ->orderBy('component_type')
        ->orderBy('order_number')
        ->get()
        ->groupBy('component_type');

    // Get component totals for HPS
    $wwColumns = $columns->get('WW', collect());
    $ptColumns = $columns->get('PT', collect());
    $qaColumns = $columns->get('QA', collect());

    $wwTotalHPS = $wwColumns->sum('max_points');
    $ptTotalHPS = $ptColumns->sum('max_points');
    $qaTotalHPS = $qaColumns->sum('max_points');

    // Set HPS values (row 11) - individual column max points
    $this->populateComponentColumns($sheet, $wwColumns, 'F', 11, 10);
    $this->populateComponentColumns($sheet, $ptColumns, 'S', 11, 10);
    if ($qaColumns->isNotEmpty()) {
        $sheet->setCellValue('AF11', $qaTotalHPS);
    }

    // Set total HPS and weighted percentages in row 11
    $sheet->setCellValue('P11', $wwTotalHPS);
    $sheet->setCellValueExplicit('R11', $class->ww_perc / 100, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    
    $sheet->setCellValue('AC11', $ptTotalHPS);
    $sheet->setCellValueExplicit('AE11', $class->pt_perc / 100, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    
    $sheet->setCellValue('AF11', $qaTotalHPS);
    $sheet->setCellValueExplicit('AH11', $class->qa_perce / 100, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

    // Get students FOR THIS SECTION ONLY and scores
    $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
    $scores = $this->getStudentScores($class->class_code, $columns, $quarterId);
    $quizScores = $this->getQuizScores($columns, $students, $quarterId);

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
        
        // Calculate WW scores and totals
        $wwTotal = 0;
        $this->populateStudentScoresWithTotal($sheet, $row, $wwColumns, $studentScores, $quizScores, $student->student_number, 'F', $wwTotal);
        
        // Calculate PT scores and totals
        $ptTotal = 0;
        $this->populateStudentScoresWithTotal($sheet, $row, $ptColumns, $studentScores, $quizScores, $student->student_number, 'S', $ptTotal);
        
        // Calculate QA scores
        $qaTotal = 0;
        $this->populateQAScoreWithTotal($sheet, $row, $qaColumns, $studentScores, $quizScores, $student->student_number, $qaTotal);

        // Calculate PS and WS for each component
        if ($wwTotalHPS > 0) {
            $wwPS = ($wwTotal / $wwTotalHPS) * 100;
            $sheet->setCellValue('P' . $row, $wwTotal);
            $sheet->setCellValue('Q' . $row, number_format($wwPS, 2));
        }

        if ($ptTotalHPS > 0) {
            $ptPS = ($ptTotal / $ptTotalHPS) * 100;
            $sheet->setCellValue('AC' . $row, $ptTotal);
            $sheet->setCellValue('AD' . $row, number_format($ptPS, 2));
        }

        if ($qaTotalHPS > 0) {
            $qaPS = ($qaTotal / $qaTotalHPS) * 100;
            $sheet->setCellValue('AF' . $row, $qaTotal);
            $sheet->setCellValue('AG' . $row, number_format($qaPS, 2));
        }

        if ($student->gender === 'Male') {
            $maleRow++;
        } else {
            $femaleRow++;
        }
    }
}



private function populateComponentColumns($sheet, $columns, $startCol, $row, $maxCols)
{
    foreach ($columns as $column) {
        // Extract the column NUMBER from the column name
        preg_match('/(\d+)$/', $column->column_name, $matches);
        $columnNumber = isset($matches[1]) ? (int)$matches[1] : 1;
        
        // Skip if column number exceeds max
        if ($columnNumber > $maxCols) continue;
        
        // Calculate the ACTUAL Excel column based on the column number
        $excelColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) + ($columnNumber - 1);
        $currentCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($excelColumnIndex);
        
        $sheet->setCellValue($currentCol . $row, $column->max_points);
    }
}

private function populateStudentScoresWithTotal($sheet, $row, $columns, $studentScores, $quizScores, $studentNumber, $startCol, &$total)
{
    foreach ($columns as $column) {
        // Extract the column NUMBER from the column name (e.g., "WW5" -> 5, "PT3" -> 3)
        preg_match('/(\d+)$/', $column->column_name, $matches);
        $columnNumber = isset($matches[1]) ? (int)$matches[1] : 1;
        
        // Calculate the ACTUAL Excel column based on the column number
        // For WW: WW1=F(6), WW2=G(7), WW3=H(8), etc.
        // For PT: PT1=S(19), PT2=T(20), PT3=U(21), etc.
        $excelColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) + ($columnNumber - 1);
        $currentCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($excelColumnIndex);
        
        $score = null;
        
        // Check if this is an online quiz column
        if ($column->quiz_id && isset($quizScores[$column->quiz_id][$studentNumber])) {
            $score = $quizScores[$column->quiz_id][$studentNumber];
        } else {
            $score = $studentScores[$column->column_name] ?? null;
        }
        
        if ($score !== null) {
            $sheet->setCellValue($currentCol . $row, $score);
            $total += floatval($score);
        }
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

private function populateQAScoreWithTotal($sheet, $row, $qaColumns, $studentScores, $quizScores, $studentNumber, &$total)
{
    if ($qaColumns->isEmpty()) return;
    
    $totalScore = 0;
    foreach ($qaColumns as $column) {
        $score = null;
        
        // Check if this is an online quiz column
        if ($column->quiz_id && isset($quizScores[$column->quiz_id][$studentNumber])) {
            $score = $quizScores[$column->quiz_id][$studentNumber];
        } else {
            $score = $studentScores[$column->column_name] ?? null;
        }
        
        if ($score !== null) {
            $totalScore += floatval($score);
        }
    }
    
    if ($totalScore > 0) {
        // QA is always in column AF
        $sheet->setCellValue('AF' . $row, $totalScore);
        $total = $totalScore;
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
        
        // Get active semester
        $semester = DB::table('semesters')->where('status', 'active')->first();
        if (!$semester) {
            return response()->json(['success' => false, 'message' => 'No active semester'], 400);
        }

        // Get both quarters
        $quarters = DB::table('quarters')
            ->where('semester_id', $semester->id)
            ->orderBy('order_number')
            ->get();

        if ($quarters->count() < 2) {
            return response()->json(['success' => false, 'message' => 'Semester must have 2 quarters'], 400);
        }

        $q1 = $quarters[0];
        $q2 = $quarters[1];

        // Get students for this section
        $students = $this->getEnrolledStudentsBySection($classId, $sectionId);

        $finalGrades = [];

    foreach ($students as $student) {
        $q1Data = $this->calculateQuarterGrade($student->student_number, $class, $q1->id);
        $q2Data = $this->calculateQuarterGrade($student->student_number, $class, $q2->id);

        $q1Grade = $q1Data['quarterly_grade']; // Already transmuted
        $q2Grade = $q2Data['quarterly_grade']; // Already transmuted

        // Calculate semester average from transmuted grades
        $semesterAverage = ($q1Grade + $q2Grade) / 2;
        
        // Transmute the semester average
        $finalGrade = GradeTransmutation::transmute($semesterAverage);
        
        // Get remarks based on transmuted final grade
        $remarks = GradeTransmutation::getRemarks($finalGrade);

        $finalGrades[] = [
            'student_number' => $student->student_number,
            'full_name' => $student->full_name,
            'gender' => $student->gender,
            'q1_grade' => $q1Grade,
            'q2_grade' => $q2Grade,
            'semester_average' => number_format($semesterAverage, 2),
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
 * Calculate quarter grade for a student
 */
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

    // Get quiz scores
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
    $quarterlyGrade = $initialGrade;

    $initialGrade = $wwWeighted + $ptWeighted + $qaWeighted;
    $transmutedGrade = GradeTransmutation::transmute($initialGrade);
    $quarterlyGrade = $transmutedGrade; 
    return [
        'ww_weighted' => $wwWeighted,
        'pt_weighted' => $ptWeighted,
        'qa_weighted' => $qaWeighted,
        'initial_grade' => $initialGrade,
        'quarterly_grade' => $quarterlyGrade
    ];
}

/**
 * Get quiz scores for a specific student
 */
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

/**
 * Get enrolled students for specific section
 */
private function getEnrolledStudentsBySection($classId, $sectionId)
{
    $class = DB::table('classes')->where('id', $classId)->first();

    // Regular students
    $regular = DB::table('students as s')
        ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
        ->where('scm.class_id', $classId)
        ->where('s.section_id', $sectionId)
        ->where('s.student_type', 'regular')
        ->select(
            's.student_number',
            's.gender',
            's.section_id',  // ← ADD THIS LINE
            DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
        );

    // Irregular students
    $irregular = DB::table('students as s')
        ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
        ->where('scm.class_code', $class->class_code)
        ->where('s.section_id', $sectionId)
        ->where('scm.enrollment_status', 'enrolled')
        ->where('s.student_type', 'irregular')
        ->select(
            's.student_number',
            's.gender',
            's.section_id',  // ← ADD THIS LINE
            DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
        );

    return $regular->union($irregular)
        ->orderBy('gender')
        ->orderBy('full_name')
        ->get();
}

/**
 * Update the generateFilename method to use semester instead of quarter
 */
private function generateFilename($class, $section, $semester)
{
    $classCode = str_replace(' ', '_', $class->class_code);
    $sectionName = $section ? str_replace(' ', '_', $section->name) : 'NoSection';
    $semesterCode = str_replace(' ', '_', $semester->code);
    $timestamp = now()->format('Ymd_His');
    
    return "{$classCode}_{$sectionName}_{$semesterCode}_{$timestamp}.xlsx";
}

    /**
     * Submit final grades for the semester
     */
    public function submitFinalGrades(Request $request, $classId)
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

            $validated = $request->validate([
                'grades' => 'required|array',
                'grades.*.student_number' => 'required|exists:students,student_number',
                'grades.*.q1_grade' => 'required|numeric|min:0|max:100',
                'grades.*.q2_grade' => 'required|numeric|min:0|max:100',
                'grades.*.final_grade' => 'required|integer|min:0|max:100',
                'grades.*.remarks' => 'required|in:PASSED,FAILED,INC,DRP,W',
                'semester_id' => 'required|exists:semesters,id',
                'section_id' => 'required|exists:sections,id'
            ]);

            $class = DB::table('classes')->where('id', $classId)->first();
            $semesterId = $validated['semester_id'];

            DB::beginTransaction();

            foreach ($validated['grades'] as $gradeData) {
                // Check if student belongs to this section
                $student = DB::table('students')
                    ->where('student_number', $gradeData['student_number'])
                    ->first();

                if (!$student) continue;

                // Verify enrollment
                $isEnrolled = false;
                
                if ($student->student_type === 'regular') {
                    $isEnrolled = DB::table('section_class_matrix')
                        ->where('section_id', $validated['section_id'])
                        ->where('class_id', $classId)
                        ->where('semester_id', $semesterId)
                        ->exists();
                } else {
                    $isEnrolled = DB::table('student_class_matrix')
                        ->where('student_number', $gradeData['student_number'])
                        ->where('class_code', $class->class_code)
                        ->where('semester_id', $semesterId)
                        ->where('enrollment_status', 'enrolled')
                        ->exists();
                }

                if (!$isEnrolled) {
                    continue;
                }

                // Store both semester average and transmuted final grade
                DB::table('grades_final')->updateOrInsert(
                    [
                        'student_number' => $gradeData['student_number'],
                        'class_code' => $class->class_code,
                        'semester_id' => $semesterId
                    ],
                    [
                        'q1_grade' => $gradeData['q1_grade'],
                        'q2_grade' => $gradeData['q2_grade'],
                        'semester_average' => $gradeData['semester_average'], // Add this
                        'final_grade' => $gradeData['final_grade'], // This is transmuted
                        'remarks' => $gradeData['remarks'],
                        'computed_by' => $teacher->id,
                        'computed_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Final grades submitted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to submit final grades', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit final grades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if final grades are already submitted
     */
    public function checkFinalGradesStatus($classId, Request $request)
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

            $semesterId = $request->input('semester_id');
            $sectionId = $request->input('section_id');
            
            if (!$semesterId || !$sectionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester ID and Section ID required'
                ], 400);
            }

            $class = DB::table('classes')->where('id', $classId)->first();

            // Get students for this section
            $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
            
            // Check submitted grades
            $submittedGrades = DB::table('grades_final')
                ->where('class_code', $class->class_code)
                ->where('semester_id', $semesterId)
                ->whereIn('student_number', $students->pluck('student_number'))
                ->get()
                ->keyBy('student_number');

            $isSubmitted = $submittedGrades->count() > 0;
            $submittedCount = $submittedGrades->count();
            $totalStudents = $students->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_submitted' => $isSubmitted,
                    'submitted_count' => $submittedCount,
                    'total_students' => $totalStudents,
                    'submitted_grades' => $submittedGrades
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to check final grades status', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * Import grades from Excel file
 */
public function importGrades(Request $request, $classId)
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

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'quarter_id' => 'required|exists:quarters,id',
            'component_type' => 'required|in:WW,PT,QA',
            'column_number' => 'required|integer|min:1',
            'section_id' => 'required|exists:sections,id'
        ]);

        $file = $request->file('file');
        $quarterId = $validated['quarter_id'];
        $componentType = $validated['component_type'];
        $columnNumber = $validated['column_number'];
        $sectionId = $validated['section_id'];

        // Validate column number based on component type
        if ($componentType === 'WW' && $columnNumber > self::MAX_WW_COLUMNS) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid WW column number. Maximum is ' . self::MAX_WW_COLUMNS
            ], 400);
        }
        
        if ($componentType === 'PT' && $columnNumber > self::MAX_PT_COLUMNS) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid PT column number. Maximum is ' . self::MAX_PT_COLUMNS
            ], 400);
        }
        
        if ($componentType === 'QA' && $columnNumber > self::MAX_QA_COLUMNS) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QA column number. Maximum is ' . self::MAX_QA_COLUMNS
            ], 400);
        }

        // Load the Excel file
        $spreadsheet = IOFactory::load($file->getPathname());
        
        // Determine which sheet to read based on quarter
        $quarter = DB::table('quarters')->where('id', $quarterId)->first();
        $sheetName = $quarter->order_number == 1 ? '1ST' : '2ND';
        
        try {
            $sheet = $spreadsheet->getSheetByName($sheetName);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Sheet '{$sheetName}' not found in the uploaded file"
            ], 400);
        }

        $class = DB::table('classes')->where('id', $classId)->first();
        
        // Get the column name
        if ($componentType === 'QA') {
            $columnName = 'QA';
        } else {
            $columnName = $componentType . $columnNumber;
        }
        
        // Get the gradebook column
        $gradebookColumn = DB::table('gradebook_columns')
            ->where('class_code', $class->class_code)
            ->where('quarter_id', $quarterId)
            ->where('component_type', $componentType)
            ->where('column_name', $columnName)
            ->first();

        if (!$gradebookColumn) {
            return response()->json([
                'success' => false,
                'message' => "Column {$columnName} not found in gradebook"
            ], 404);
        }

        // Map column letters based on component type and column number
        $excelColumn = $this->getExcelColumnLetter($componentType, $columnNumber);
        
        // Get students enrolled in this section for this class
        $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
        $studentMap = $students->keyBy('student_number');

        DB::beginTransaction();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Read male students (rows 13-62)
        for ($row = 13; $row <= 62; $row++) {
            $studentNumber = $sheet->getCell('A' . $row)->getValue();
            $score = $sheet->getCell($excelColumn . $row)->getValue();
            
            $result = $this->processImportRow(
                $studentNumber, 
                $score, 
                $studentMap, 
                $gradebookColumn, 
                $row
            );
            
            if ($result['success']) {
                $imported++;
            } else {
                $skipped++;
                if ($result['error']) {
                    $errors[] = $result['error'];
                }
            }
        }

        // Read female students (rows 64-113)
        for ($row = 64; $row <= 113; $row++) {
            $studentNumber = $sheet->getCell('A' . $row)->getValue();
            $score = $sheet->getCell($excelColumn . $row)->getValue();
            
            $result = $this->processImportRow(
                $studentNumber, 
                $score, 
                $studentMap, 
                $gradebookColumn, 
                $row
            );
            
            if ($result['success']) {
                $imported++;
            } else {
                $skipped++;
                if ($result['error']) {
                    $errors[] = $result['error'];
                }
            }
        }

        DB::commit();

        $message = "Import completed: {$imported} scores imported";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (Exception $e) {
        DB::rollBack();
        
        \Log::error('Failed to import grades', [
            'class_id' => $classId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to import grades: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Process a single import row
 */
private function processImportRow($studentNumber, $score, $studentMap, $gradebookColumn, $rowNumber)
{
    // Skip if no student number
    if (empty($studentNumber)) {
        return ['success' => false, 'error' => null];
    }

    // Check if student exists in section
    if (!$studentMap->has($studentNumber)) {
        return [
            'success' => false,
            'error' => "Row {$rowNumber}: Student {$studentNumber} not enrolled in selected section"
        ];
    }

    // Skip if no score (empty cells are okay - they won't update)
    if ($score === null || $score === '') {
        return ['success' => false, 'error' => null];
    }

    // Validate score
    $score = floatval($score);
    if ($score < 0) {
        return [
            'success' => false,
            'error' => "Row {$rowNumber}: Invalid score {$score} for student {$studentNumber}"
        ];
    }

    if ($score > $gradebookColumn->max_points) {
        return [
            'success' => false,
            'error' => "Row {$rowNumber}: Score {$score} exceeds max points ({$gradebookColumn->max_points}) for student {$studentNumber}"
        ];
    }

    // Insert or update the score
    DB::table('gradebook_scores')->updateOrInsert(
        [
            'column_id' => $gradebookColumn->id,
            'student_number' => $studentNumber
        ],
        [
            'score' => $score,
            'source' => 'imported',
            'updated_at' => now()
        ]
    );

    return ['success' => true, 'error' => null];
}

/**
 * Get Excel column letter based on component type and number
 */
private function getExcelColumnLetter($componentType, $columnNumber)
{
    if ($componentType === 'WW') {
        // WW columns: F, G, H, I, J, K, L, M, N, O (columns 6-15)
        $columnIndex = 5 + $columnNumber; // F=6, G=7, etc.
    } elseif ($componentType === 'PT') {
        // PT columns: S, T, U, V, W, X, Y, Z, AA, AB (columns 19-28)
        $columnIndex = 18 + $columnNumber; // S=19, T=20, etc.
    } else { // QA
        // QA column: AF (column 32)
        $columnIndex = 32;
    }
    
    return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
}

/**
 * Import grades when enabling a column
 */
public function importColumnGrades(Request $request, $classId, $columnId)
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

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'section_id' => 'required|exists:sections,id'
        ]);

        $file = $request->file('file');
        $sectionId = $validated['section_id'];

        // Get column info
        $column = DB::table('gradebook_columns')->where('id', $columnId)->first();
        
        if (!$column) {
            return response()->json([
                'success' => false,
                'message' => 'Column not found'
            ], 404);
        }

        // Get quarter to determine sheet name
        $quarter = DB::table('quarters')->where('id', $column->quarter_id)->first();
        $sheetName = $quarter->order_number == 1 ? '1ST' : '2ND';

        // Load Excel file
        $spreadsheet = IOFactory::load($file->getPathname());
        
        try {
            $sheet = $spreadsheet->getSheetByName($sheetName);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Sheet '{$sheetName}' not found in the uploaded file"
            ], 400);
        }

        // Parse column name to get number (e.g., "WW5" -> 5)
        preg_match('/(\d+)$/', $column->column_name, $matches);
        $columnNumber = isset($matches[1]) ? (int)$matches[1] : 1;

        // Get Excel column letter
        $excelColumn = $this->getExcelColumnLetter($column->component_type, $columnNumber);

        // Get students for this section
        $class = DB::table('classes')->where('id', $classId)->first();
        $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
        $studentMap = $students->keyBy('student_number');

        DB::beginTransaction();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Read male students (rows 13-62)
        for ($row = 13; $row <= 62; $row++) {
            $studentNumber = $sheet->getCell('A' . $row)->getValue();
            $score = $sheet->getCell($excelColumn . $row)->getValue();
            
            $result = $this->processImportRow(
                $studentNumber, 
                $score, 
                $studentMap, 
                $column, 
                $row
            );
            
            if ($result['success']) {
                $imported++;
            } else {
                $skipped++;
                if ($result['error']) {
                    $errors[] = $result['error'];
                }
            }
        }

        // Read female students (rows 64-113)
        for ($row = 64; $row <= 113; $row++) {
            $studentNumber = $sheet->getCell('A' . $row)->getValue();
            $score = $sheet->getCell($excelColumn . $row)->getValue();
            
            $result = $this->processImportRow(
                $studentNumber, 
                $score, 
                $studentMap, 
                $column, 
                $row
            );
            
            if ($result['success']) {
                $imported++;
            } else {
                $skipped++;
                if ($result['error']) {
                    $errors[] = $result['error'];
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (Exception $e) {
        DB::rollBack();
        
        \Log::error('Failed to import column grades', [
            'class_id' => $classId,
            'column_id' => $columnId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to import grades: ' . $e->getMessage()
        ], 500);
    }
}
}
<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GradebookImportExportController extends MainController
{
    use AuditLogger;

    const MAX_WW_COLUMNS = 10;
    const MAX_PT_COLUMNS = 10;
    const MAX_QA_COLUMNS = 1;

    /**
     * Export gradebook with embedded validation metadata
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

            $validated = $request->validate([
                'section_id' => 'required|exists:sections,id'
            ]);

            $sectionId = $validated['section_id'];
            
            $class = DB::table('classes')->where('id', $classId)->first();
            $section = DB::table('sections')->where('id', $sectionId)->first();
            
            if (!$section) {
                return response()->json(['success' => false, 'message' => 'Section not found'], 404);
            }
            
            $activeSemester = DB::table('semesters')->where('status', 'active')->first();
            if (!$activeSemester) {
                return response()->json(['success' => false, 'message' => 'No active semester found'], 404);
            }

            $schoolYear = DB::table('school_years')
                ->where('id', $activeSemester->school_year_id)
                ->first();
            
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
            
            $q1 = $quarters->where('order_number', 1)->first();
            $q2 = $quarters->where('order_number', 2)->first();
            
            if (!$q1 || !$q2) {
                return response()->json(['success' => false, 'message' => 'Q1 and Q2 quarters not found'], 404);
            }
            
            $templatePath = storage_path('app/templates/SHS-E-Class-Record.xlsx');
            
            if (!file_exists($templatePath)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Template file not found. Please contact administrator.'
                ], 404);
            }

            $spreadsheet = IOFactory::load($templatePath);
            
            // Populate sheets with data
            $this->populateInputDataSheet($spreadsheet, $class, $section, $activeSemester, $teacher, $q1, $sectionId);
            $this->populateGradeSheet($spreadsheet, '1ST', $classId, $class, $q1->id, $sectionId);
            $this->populateGradeSheet($spreadsheet, '2ND', $classId, $class, $q2->id, $sectionId);
            
            // Embed validation metadata
            $this->embedValidationMetadata($spreadsheet, $class, $section, $activeSemester);
            
            $filename = $this->generateFilename($class, $section, $activeSemester);
            $exportPath = storage_path('app/exports/' . $filename);
            
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }
            
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($exportPath);

            // Count students for audit log
            $studentCount = $this->getEnrolledStudentsBySection($classId, $sectionId)->count();

            // Audit log — exported
            $this->logAudit(
                'exported',
                'gradebook',
                (string)$classId,
                "Exported gradebook for class '{$class->class_code} - {$class->class_name}', section '{$section->name}' ({$activeSemester->code})",
                null,
                [
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'section_id' => $sectionId,
                    'section_name' => $section->name,
                    'semester_id' => $activeSemester->id,
                    'semester_code' => $activeSemester->code,
                    'school_year' => $schoolYear ? $schoolYear->code : null,
                    'student_count' => $studentCount,
                    'filename' => $filename,
                    'quarters_included' => [$q1->code, $q2->code]
                ],
                'teacher',
                $teacher->email
            );
            
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
     * Embed validation metadata into Excel
     */
    private function embedValidationMetadata($spreadsheet, $class, $section, $semester)
    {
        $sheet = $spreadsheet->getSheetByName('INPUT DATA');
        
        if (!$sheet) {
            throw new Exception('INPUT DATA sheet not found');
        }

        // Get school year
        $schoolYear = DB::table('school_years')
            ->where('id', $semester->school_year_id)
            ->first();

        // Metadata values - just the 4 core identifiers
        $classCode = $class->class_code;
        $sectionId = strval($section->id);
        $semesterCode = $semester->code;
        $schoolYearCode = $schoolYear ? $schoolYear->code : '';
        
        // Write to hidden cells (rows 120-124)
        $sheet->setCellValue('A120', 'MetaData');
        $sheet->setCellValue('A121', $classCode);
        $sheet->setCellValue('A122', $sectionId);
        $sheet->setCellValue('A123', $semesterCode);
        $sheet->setCellValue('A124', $schoolYearCode);
        
        // Hide metadata rows
        for ($row = 120; $row <= 125; $row++) {
            $sheet->getRowDimension($row)->setVisible(false);
        }
        
        \Log::info('Embedded validation metadata', [
            'class_code' => $classCode,
            'section_id' => $sectionId,
            'semester_code' => $semesterCode,
            'school_year' => $schoolYearCode
        ]);
    }

    /**
     * Validate Excel file metadata before import
     */
    private function validateExcelMetadata($spreadsheet, $classId, $sectionId)
    {
        try {
            $sheet = $spreadsheet->getSheetByName('INPUT DATA');
            
            if (!$sheet) {
                return [
                    'valid' => false,
                    'message' => 'Invalid Excel file: INPUT DATA sheet not found'
                ];
            }

            // Extract metadata from hidden cells
            $excelClassCode = $sheet->getCell('A121')->getValue();
            $excelSectionId = $sheet->getCell('A122')->getValue();
            $excelSemesterCode = $sheet->getCell('A123')->getValue();
            $excelSchoolYear = $sheet->getCell('A124')->getValue();
            
            // Check if metadata exists
            if (empty($excelClassCode) || empty($excelSectionId) || empty($excelSemesterCode) || empty($excelSchoolYear)) {
                return [
                    'valid' => false,
                    'message' => 'This Excel file does not contain validation metadata. Please export a new file from the system.'
                ];
            }

            // Get current context from database
            $class = DB::table('classes')->where('id', $classId)->first();
            $section = DB::table('sections')->where('id', $sectionId)->first();
            $activeSemester = DB::table('semesters')->where('status', 'active')->first();
            
            if (!$class || !$section || !$activeSemester) {
                return [
                    'valid' => false,
                    'message' => 'Unable to verify current class/section/semester'
                ];
            }

            $currentSchoolYear = DB::table('school_years')
                ->where('id', $activeSemester->school_year_id)
                ->first();

            // Validate class code match
            if ($excelClassCode !== $class->class_code) {
                return [
                    'valid' => false,
                    'message' => "Class mismatch: This file was exported for '{$excelClassCode}' but you're trying to import to '{$class->class_code}'"
                ];
            }

            // Validate section ID match
            if (strval($excelSectionId) !== strval($sectionId)) {
                $excelSectionName = DB::table('sections')->where('id', $excelSectionId)->value('name') ?? 'Unknown';
                
                return [
                    'valid' => false,
                    'message' => "Section mismatch: This file was exported for section '{$excelSectionName}' but you selected '{$section->name}'"
                ];
            }

            // Validate semester match
            if ($excelSemesterCode !== $activeSemester->code) {
                return [
                    'valid' => false,
                    'message' => "Semester mismatch: This file was exported for '{$excelSemesterCode}' but the active semester is '{$activeSemester->code}'"
                ];
            }

            // Validate school year match
            if ($currentSchoolYear && $excelSchoolYear !== $currentSchoolYear->code) {
                return [
                    'valid' => false,
                    'message' => "School year mismatch: This file was exported for '{$excelSchoolYear}' but the current school year is '{$currentSchoolYear->code}'"
                ];
            }

            // All validations passed
            \Log::info('Excel metadata validation passed', [
                'class_code' => $excelClassCode,
                'section_id' => $excelSectionId,
                'semester_code' => $excelSemesterCode,
                'school_year' => $excelSchoolYear
            ]);

            return [
                'valid' => true,
                'message' => 'Validation successful',
                'metadata' => [
                    'class_code' => $excelClassCode,
                    'section_id' => $excelSectionId,
                    'semester_code' => $excelSemesterCode,
                    'school_year' => $excelSchoolYear
                ]
            ];

        } catch (Exception $e) {
            \Log::error('Metadata validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'valid' => false,
                'message' => 'Unable to validate file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import grades with validation and audit logging
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

            // Validate column number
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

            $spreadsheet = IOFactory::load($file->getPathname());
            
            // Validate metadata before proceeding
            $validationResult = $this->validateExcelMetadata($spreadsheet, $classId, $sectionId);
            
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 400);
            }
            
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
            $section = DB::table('sections')->where('id', $sectionId)->first();
            
            $columnName = $componentType === 'QA' ? 'QA' : $componentType . $columnNumber;
            
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

            $excelColumn = $this->getExcelColumnLetter($componentType, $columnNumber);
            
            $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
            $studentMap = $students->keyBy('student_number');

            DB::beginTransaction();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Male students (rows 13-62)
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

            // Female students (rows 64-113)
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

            // Audit log — imported scores
            $this->logAudit(
                'imported',
                'gradebook_scores',
                (string)$gradebookColumn->id,
                "Imported {$imported} score(s) for column '{$columnName}' in class '{$class->class_code} - {$class->class_name}', section '{$section->name}' ({$quarter->name})",
                null,
                [
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'section_id' => $sectionId,
                    'section_name' => $section->name,
                    'quarter_id' => $quarterId,
                    'quarter_name' => $quarter->name,
                    'column_id' => $gradebookColumn->id,
                    'column_name' => $columnName,
                    'component_type' => $componentType,
                    'max_points' => $gradebookColumn->max_points,
                    'imported_count' => $imported,
                    'skipped_count' => $skipped,
                    'error_count' => count($errors),
                    'filename' => $file->getClientOriginalName(),
                    'file_metadata' => $validationResult['metadata']
                ],
                'teacher',
                $teacher->email
            );

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
                    'errors' => $errors,
                    'file_metadata' => $validationResult['metadata']
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
     * Import column grades with validation and audit logging
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

            $column = DB::table('gradebook_columns')->where('id', $columnId)->first();
            
            if (!$column) {
                return response()->json([
                    'success' => false,
                    'message' => 'Column not found'
                ], 404);
            }

            $spreadsheet = IOFactory::load($file->getPathname());
            
            // Validate metadata
            $validationResult = $this->validateExcelMetadata($spreadsheet, $classId, $sectionId);
            
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 400);
            }

            $quarter = DB::table('quarters')->where('id', $column->quarter_id)->first();
            $sheetName = $quarter->order_number == 1 ? '1ST' : '2ND';

            try {
                $sheet = $spreadsheet->getSheetByName($sheetName);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => "Sheet '{$sheetName}' not found in the uploaded file"
                ], 400);
            }

            preg_match('/(\d+)$/', $column->column_name, $matches);
            $columnNumber = isset($matches[1]) ? (int)$matches[1] : 1;

            $excelColumn = $this->getExcelColumnLetter($column->component_type, $columnNumber);

            $class = DB::table('classes')->where('id', $classId)->first();
            $section = DB::table('sections')->where('id', $sectionId)->first();
            $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
            $studentMap = $students->keyBy('student_number');

            DB::beginTransaction();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Male students (rows 13-62)
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

            // Female students (rows 64-113)
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

            // Audit log — imported column scores
            $this->logAudit(
                'imported',
                'gradebook_columns',
                (string)$columnId,
                "Imported {$imported} score(s) for column '{$column->column_name}' in class '{$class->class_code} - {$class->class_name}', section '{$section->name}' ({$quarter->name})",
                null,
                [
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'section_id' => $sectionId,
                    'section_name' => $section->name,
                    'quarter_id' => $column->quarter_id,
                    'quarter_name' => $quarter->name,
                    'column_id' => $columnId,
                    'column_name' => $column->column_name,
                    'component_type' => $column->component_type,
                    'max_points' => $column->max_points,
                    'source_type' => $column->source_type,
                    'imported_count' => $imported,
                    'skipped_count' => $skipped,
                    'error_count' => count($errors),
                    'filename' => $file->getClientOriginalName(),
                    'file_metadata' => $validationResult['metadata']
                ],
                'teacher',
                $teacher->email
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'file_metadata' => $validationResult['metadata']
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

    // Private helper methods remain the same...
    // (populateInputDataSheet, populateGradeSheet, etc.)

    private function populateInputDataSheet($spreadsheet, $class, $section, $semester, $teacher, $quarter, $sectionId)
    {
        $sheet = $spreadsheet->getSheetByName('INPUT DATA');
        
        if (!$sheet) {
            throw new Exception('INPUT DATA sheet not found in template');
        }

        $schoolYear = DB::table('school_years')
            ->where('id', $semester->school_year_id)
            ->first();

        $sheet->setCellValue('K7', $section->name ?? '');
        $sheet->setCellValue('S7', $teacher->first_name . ' ' . $teacher->last_name);
        $sheet->setCellValue('S8', $quarter->code === 'Q1' ? '1ST' : '2ND');
        $sheet->setCellValue('AE7', $class->class_name);
        $sheet->setCellValue('AE8', $class->class_code);
        $sheet->setCellValue('AG5', $schoolYear ? $schoolYear->code : '');

        $maleStudents = $this->getEnrolledStudentsByGenderAndSection($class->id, 'Male', $sectionId);
        $femaleStudents = $this->getEnrolledStudentsByGenderAndSection($class->id, 'Female', $sectionId);
        
        // Male students
        $row = 13;
        foreach ($maleStudents as $student) {
            $sheet->setCellValue('A' . $row, $student->student_number);
            $sheet->setCellValue('B' . $row, $student->full_name);
            $row++;
            if ($row > 62) break;
        }
        
        // Female students
        $row = 64;
        foreach ($femaleStudents as $student) {
            $sheet->setCellValue('A' . $row, $student->student_number);
            $sheet->setCellValue('B' . $row, $student->full_name);
            $row++;
            if ($row > 113) break;
        }
    }

    private function populateGradeSheet($spreadsheet, $sheetName, $classId, $class, $quarterId, $sectionId)
    {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        
        if (!$sheet) {
            throw new Exception($sheetName . ' sheet not found in template');
        }

        $quarter = DB::table('quarters')->where('id', $quarterId)->first();
        $section = DB::table('sections')->where('id', $sectionId)->first();
        $teacher = Auth::guard('teacher')->user();
        
        $semester = DB::table('semesters')->where('id', $quarter->semester_id)->first();
        $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

        $sheet->setCellValue('K7', $section->name ?? '');
        $sheet->setCellValue('S7', $teacher->first_name . ' ' . $teacher->last_name);
        $sheet->setCellValue('S8', $quarter->order_number == 1 ? '1ST' : '2ND');
        $sheet->setCellValue('AE7', $class->class_name);
        $sheet->setCellValue('AG5', $schoolYear ? $schoolYear->code : '');
        
        $sheet->setCellValue('F9', "Written Work ({$class->ww_perc}%)");
        $sheet->setCellValue('S9', "Performance Task ({$class->pt_perc}%)");
        $sheet->setCellValue('AF9', "Quarterly Assessment ({$class->qa_perce}%)");

        $columns = DB::table('gradebook_columns')
            ->where('class_code', $class->class_code)
            ->where('quarter_id', $quarterId)
            ->where('is_active', true)
            ->orderBy('component_type')
            ->orderBy('order_number')
            ->get()
            ->groupBy('component_type');

        $wwColumns = $columns->get('WW', collect());
        $ptColumns = $columns->get('PT', collect());
        $qaColumns = $columns->get('QA', collect());

        $wwTotalHPS = $wwColumns->sum('max_points');
        $ptTotalHPS = $ptColumns->sum('max_points');
        $qaTotalHPS = $qaColumns->sum('max_points');

        $this->populateComponentColumns($sheet, $wwColumns, 'F', 11, 10);
        $this->populateComponentColumns($sheet, $ptColumns, 'S', 11, 10);
        if ($qaColumns->isNotEmpty()) {
            $sheet->setCellValue('AF11', $qaTotalHPS);
        }

        $sheet->setCellValue('P11', $wwTotalHPS);
        $sheet->setCellValueExplicit('R11', $class->ww_perc / 100, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        
        $sheet->setCellValue('AC11', $ptTotalHPS);
        $sheet->setCellValueExplicit('AE11', $class->pt_perc / 100, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        
        $sheet->setCellValue('AF11', $qaTotalHPS);
        $sheet->setCellValueExplicit('AH11', $class->qa_perce / 100, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

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

            $sheet->setCellValue('A' . $row, $student->student_number);
            $sheet->setCellValue('B' . $row, $student->full_name);

            $studentScores = $scores->get($student->student_number, []);
            
            $wwTotal = 0;
            $this->populateStudentScoresWithTotal($sheet, $row, $wwColumns, $studentScores, $quizScores, $student->student_number, 'F', $wwTotal);
            
            $ptTotal = 0;
            $this->populateStudentScoresWithTotal($sheet, $row, $ptColumns, $studentScores, $quizScores, $student->student_number, 'S', $ptTotal);
            
            $qaTotal = 0;
            $this->populateQAScoreWithTotal($sheet, $row, $qaColumns, $studentScores, $quizScores, $student->student_number, $qaTotal);

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
            preg_match('/(\d+)$/', $column->column_name, $matches);
            $columnNumber = isset($matches[1]) ? (int)$matches[1] : 1;
            
            if ($columnNumber > $maxCols) continue;
            
            $excelColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) + ($columnNumber - 1);
            $currentCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($excelColumnIndex);
            
            $sheet->setCellValue($currentCol . $row, $column->max_points);
        }
    }

    private function populateStudentScoresWithTotal($sheet, $row, $columns, $studentScores, $quizScores, $studentNumber, $startCol, &$total)
    {
        foreach ($columns as $column) {
            preg_match('/(\d+)$/', $column->column_name, $matches);
            $columnNumber = isset($matches[1]) ? (int)$matches[1] : 1;
            
            $excelColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol) + ($columnNumber - 1);
            $currentCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($excelColumnIndex);
            
            $score = null;
            
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

    private function populateQAScoreWithTotal($sheet, $row, $qaColumns, $studentScores, $quizScores, $studentNumber, &$total)
    {
        if ($qaColumns->isEmpty()) return;
        
        $totalScore = 0;
        foreach ($qaColumns as $column) {
            $score = null;
            
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

    private function processImportRow($studentNumber, $score, $studentMap, $gradebookColumn, $rowNumber)
    {
        if (empty($studentNumber)) {
            return ['success' => false, 'error' => null];
        }

        if (!$studentMap->has($studentNumber)) {
            return [
                'success' => false,
                'error' => "Row {$rowNumber}: Student {$studentNumber} not enrolled in selected section"
            ];
        }

        if ($score === null || $score === '') {
            return ['success' => false, 'error' => null];
        }

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

    private function getExcelColumnLetter($componentType, $columnNumber)
    {
        if ($componentType === 'WW') {
            $columnIndex = 5 + $columnNumber;
        } elseif ($componentType === 'PT') {
            $columnIndex = 18 + $columnNumber;
        } else { // QA
            $columnIndex = 32;
        }
        
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
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

    private function generateFilename($class, $section, $semester)
    {
        $classCode = str_replace(' ', '_', $class->class_code);
        $sectionName = $section ? str_replace(' ', '_', $section->name) : 'NoSection';
        $semesterCode = str_replace(' ', '_', $semester->code);
        $timestamp = now()->format('Ymd_His');
        
        return "{$classCode}_{$sectionName}_{$semesterCode}_{$timestamp}.xlsx";
    }
}
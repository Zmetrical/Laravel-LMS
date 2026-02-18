<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User_Management\Section;
use App\Models\User_Management\Student;
use App\Traits\AuditLogger;
use Exception;

class Section_Management extends MainController
{
    use AuditLogger;

    /**
     * Show the section assignment page
     */
    public function assign_section(Request $request)
    {
        // Get current active semester
        $currentSemester = DB::table('semesters')
            ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
            ->where('semesters.status', 'active')
            ->select(
                'semesters.id',
                'semesters.name as semester_name',
                'semesters.code as semester_code',
                'school_years.code as year_code'
            )
            ->first();

        // Get all strands for target selection
        $strands = DB::table('strands')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // Get all levels for target selection
        $levels = DB::table('levels')
            ->orderBy('id')
            ->get();

        $data = [
            'scripts' => [
                'user_management/assign_section.js',
            ],
            'currentSemester' => $currentSemester,
            'strands' => $strands,
            'levels' => $levels,
        ];

        return view('admin.user_management.assign_section', $data);
    }

    /**
     * Load all students from current active semester
     */
    public function load_students(Request $request)
    {
        try {
            // Get current active semester
            $currentSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$currentSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            // Get all students enrolled in the current semester
            $students = DB::table('student_semester_enrollment as sse')
                ->join('students as s', 'sse.student_number', '=', 's.student_number')
                ->leftJoin('sections as sec', 'sse.section_id', '=', 'sec.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->where('sse.semester_id', $currentSemester->id)
                ->where('sse.enrollment_status', 'enrolled')
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.student_type',
                    's.section_id as current_section_id',
                    'sec.name as current_section',
                    'sec.code as current_section_code',
                    'sec.strand_id',
                    'sec.level_id',
                    'lvl.name as current_level',
                    'str.code as current_strand',
                    'str.name as current_strand_name'
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            return response()->json([
                'success' => true,
                'students' => $students,
                'count' => count($students),
                'semester' => $currentSemester
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load students', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options (sections and strands) for current semester
     */
    public function get_filter_options(Request $request)
    {
        try {
            // Get current active semester
            $currentSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$currentSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            // Get ALL active sections with their strand and level info
            $sections = DB::table('sections as sec')
                ->join('strands as str', 'sec.strand_id', '=', 'str.id')
                ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('sec.status', 1)
                ->where('sec.is_active', 1)
                ->select(
                    'sec.id',
                    'sec.code',
                    'sec.name',
                    'sec.strand_id',
                    'sec.level_id',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->orderBy('sec.code')
                ->get();

            return response()->json([
                'success' => true,
                'sections' => $sections
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get filter options', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available target sections for a specific strand and level
     */
    public function get_target_sections(Request $request)
    {
        $request->validate([
            'strand_id' => 'required|exists:strands,id',
            'level_id' => 'required|exists:levels,id'
        ]);

        try {
            $strandId = $request->strand_id;
            $levelId = $request->level_id;

            // Get current active semester
            $currentSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$currentSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            // Get sections for the selected strand and level with capacity info
            $sections = $this->getSectionsWithCapacity($strandId, $levelId, $currentSemester->id);

            return response()->json([
                'success' => true,
                'sections' => $sections
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get target sections', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to get sections with capacity information
     */
    private function getSectionsWithCapacity($strandId, $levelId, $semesterId)
    {
        $sections = DB::table('sections')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->where('sections.strand_id', $strandId)
            ->where('sections.level_id', $levelId)
            ->where('sections.status', 1)
            ->select(
                'sections.id',
                'sections.code',
                'sections.name',
                'sections.capacity',
                'sections.level_id',
                'levels.name as level_name'
            )
            ->orderBy('sections.name')
            ->get();

        // Add enrolled count for each section
        foreach ($sections as $section) {
            $section->enrolled_count = $this->getSectionEnrolledCount($section->id, $semesterId);
        }

        return $sections;
    }

    /**
     * Get section capacity and enrolled count
     */
    public function get_section_capacity(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id'
        ]);

        try {
            $sectionId = $request->section_id;

            // Get current active semester
            $currentSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$currentSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            // Get section capacity
            $section = DB::table('sections')
                ->where('id', $sectionId)
                ->first(['capacity']);

            if (!$section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found'
                ], 404);
            }

            
            // Get enrolled count
            $enrolledCount = $this->getSectionEnrolledCount($sectionId, $currentSemester->id);

            return response()->json([
                'success' => true,
                'capacity' => (int) $section->capacity,
                'enrolled_count' => $enrolledCount
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get section capacity', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to get enrolled count for a section in current semester
     */
    private function getSectionEnrolledCount($sectionId, $semesterId)
    {
        return DB::table('student_semester_enrollment')
            ->where('section_id', $sectionId)
            ->where('semester_id', $semesterId)
            ->where('enrollment_status', 'enrolled')
            ->count();
    }

    /**
     * Assign students to new section (within current semester)
     */
    public function assign_students(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'students' => 'required|array|min:1',
            'students.*.student_number' => 'required|exists:students,student_number',
            'students.*.new_section_id' => 'required|exists:sections,id',
            'students.*.student_type' => 'required|in:regular,irregular',
        ]);

        try {
            DB::beginTransaction();

            // Get current active semester
            $currentSemester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.status', 'active')
                ->select(
                    'semesters.id',
                    'semesters.name as semester_name',
                    'school_years.code as sy_code'
                )
                ->first();

            if (!$currentSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 404);
            }

            $assignedCount = 0;
            $errors = [];
            $auditRecords = [];

            foreach ($request->students as $studentData) {
                try {
                    $studentNumber = $studentData['student_number'];
                    $newSectionId = $studentData['new_section_id'];
                    $studentType = $studentData['student_type'];

                    $student = Student::where('student_number', $studentNumber)->first();
                    
                    if (!$student) {
                        $errors[] = "Student {$studentNumber} not found";
                        continue;
                    }

                    // Get section names for audit
                    $oldSection = DB::table('sections')->where('id', $student->section_id)->first();
                    $newSection = DB::table('sections')->where('id', $newSectionId)->first();

                    // Validate strand compatibility
                    $studentSection = DB::table('sections')->find($student->section_id);
                    $targetSection = DB::table('sections')->find($newSectionId);

                    if ($studentSection && $targetSection && $studentSection->strand_id != $targetSection->strand_id) {
                        $errors[] = "Student {$studentNumber} cannot be assigned to a different strand";
                        continue;
                    }

                    // Store old values for audit
                    $oldValues = [
                        'section_id' => $student->section_id,
                        'section_code' => $oldSection ? $oldSection->code : null,
                        'section_name' => $oldSection ? $oldSection->name : null,
                        'student_type' => $student->student_type,
                    ];

                    // 1. Update student's section and type
                    $student->update([
                        'section_id' => $newSectionId,
                        'student_type' => $studentType,
                        'updated_at' => now()
                    ]);

                    // 2. Update semester enrollment record (same semester, just changing section)
                    DB::table('student_semester_enrollment')
                        ->where('student_number', $studentNumber)
                        ->where('semester_id', $currentSemester->id)
                        ->update([
                            'section_id' => $newSectionId,
                            'updated_at' => now()
                        ]);

                    // 3. Handle class enrollments based on student type
                    if ($studentType === 'regular') {
                        // REGULAR: Remove individual class enrollments (they follow section)
                        $sectionClasses = DB::table('section_class_matrix')
                            ->where('section_id', $newSectionId)
                            ->where('semester_id', $currentSemester->id)
                            ->get(['class_id']);

                        foreach ($sectionClasses as $sectionClass) {
                            $classCode = DB::table('classes')
                                ->where('id', $sectionClass->class_id)
                                ->value('class_code');

                            DB::table('student_class_matrix')
                                ->where('student_number', $studentNumber)
                                ->where('class_code', $classCode)
                                ->where('semester_id', $currentSemester->id)
                                ->delete();
                        }

                    } else {
                        // IRREGULAR: Enroll in all section classes individually
                        $sectionClasses = DB::table('section_class_matrix')
                            ->where('section_id', $newSectionId)
                            ->where('semester_id', $currentSemester->id)
                            ->get(['class_id']);

                        foreach ($sectionClasses as $sectionClass) {
                            $classCode = DB::table('classes')
                                ->where('id', $sectionClass->class_id)
                                ->value('class_code');

                            $exists = DB::table('student_class_matrix')
                                ->where('student_number', $studentNumber)
                                ->where('class_code', $classCode)
                                ->where('semester_id', $currentSemester->id)
                                ->exists();

                            if (!$exists) {
                                DB::table('student_class_matrix')->insert([
                                    'student_number' => $studentNumber,
                                    'class_code' => $classCode,
                                    'semester_id' => $currentSemester->id,
                                    'enrollment_status' => 'enrolled',
                                    'updated_at' => now()
                                ]);
                            }
                        }
                    }

                    // Store audit record
                    $auditRecords[] = [
                        'student_number' => $studentNumber,
                        'student_name' => trim($student->first_name . ' ' . $student->last_name),
                        'old_values' => $oldValues,
                        'new_values' => [
                            'section_id' => $newSectionId,
                            'section_code' => $newSection ? $newSection->code : null,
                            'section_name' => $newSection ? $newSection->name : null,
                            'student_type' => $studentType,
                        ]
                    ];

                    $assignedCount++;

                } catch (Exception $e) {
                    $errors[] = "Failed to assign student {$studentNumber}: " . $e->getMessage();
                    \Log::error("Failed to assign student {$studentNumber}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log audit for batch assignment
            if (!empty($auditRecords)) {
                $this->logAudit(
                    'assigned',
                    'students',
                    null,
                    "Bulk assigned {$assignedCount} student(s) to sections for {$currentSemester->semester_name} {$currentSemester->sy_code}",
                    null,
                    [
                        'semester_id' => $currentSemester->id,
                        'semester_name' => $currentSemester->semester_name,
                        'school_year' => $currentSemester->sy_code,
                        'total_students' => $assignedCount,
                        'assignments' => $auditRecords
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assignedCount} student(s) assigned successfully!",
                'assigned_count' => $assignedCount,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to assign students', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
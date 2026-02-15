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
        $sections = Section::where('status', 1)->get();
        
        // Get semesters - ordered chronologically (newest first)
        $semesters = DB::table('semesters')
            ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
            ->select(
                'semesters.id',
                'semesters.name as semester_name',
                'semesters.code as semester_code',
                'school_years.code as year_code',
                'school_years.year_start',
                'semesters.status'
            )
            ->orderBy('school_years.year_start', 'desc')
            ->orderBy('semesters.code', 'desc') // 2nd semester before 1st semester
            ->get();

        // Get all strands for target selection
        $strands = DB::table('strands')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $data = [
            'scripts' => [
                'user_management/assign_section.js',
            ],
            'sections' => $sections,
            'semesters' => $semesters,
            'strands' => $strands,
        ];

        return view('admin.user_management.assign_section', $data);
    }

    /**
     * Load all students from a specific semester
     */
    public function load_students_by_semester(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            $semesterId = $request->semester_id;

            // Get all students enrolled in this semester
            $students = DB::table('student_semester_enrollment as sse')
                ->join('students as s', 'sse.student_number', '=', 's.student_number')
                ->leftJoin('sections as sec', 'sse.section_id', '=', 'sec.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->where('sse.semester_id', $semesterId)
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
                'count' => count($students)
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load students by semester', [
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
     * Get sections for a specific semester (for filter dropdown)
     */
    public function get_sections_by_semester(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            $semesterId = $request->semester_id;

            // Get all sections that have students enrolled in this semester
            $sections = DB::table('student_semester_enrollment as sse')
                ->join('sections as sec', 'sse.section_id', '=', 'sec.id')
                ->where('sse.semester_id', $semesterId)
                ->where('sse.enrollment_status', 'enrolled')
                ->select(
                    'sec.id',
                    'sec.code',
                    'sec.name'
                )
                ->distinct()
                ->orderBy('sec.code')
                ->get();

            return response()->json([
                'success' => true,
                'sections' => $sections
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get sections by semester', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available target sections for a specific strand
     * Grouped by level (current and next level)
     */
    public function get_target_sections(Request $request)
    {
        $request->validate([
            'strand_id' => 'required|exists:strands,id',
            'current_level_id' => 'required|exists:levels,id',
            'semester_id' => 'nullable|exists:semesters,id'
        ]);

        try {
            $strandId = $request->strand_id;
            $currentLevelId = $request->current_level_id;
            $semesterId = $request->semester_id;

            // Get current level info
            $currentLevel = DB::table('levels')->find($currentLevelId);

            // Determine next level (assuming level IDs are sequential)
            $nextLevel = DB::table('levels')
                ->where('id', '>', $currentLevelId)
                ->orderBy('id', 'asc')
                ->first();

            // Get sections for current level with capacity info
            $currentLevelSections = $this->getSectionsWithCapacity($strandId, $currentLevelId, $semesterId);

            // Get sections for next level (if exists)
            $nextLevelSections = [];
            if ($nextLevel) {
                $nextLevelSections = $this->getSectionsWithCapacity($strandId, $nextLevel->id, $semesterId);
            }

            return response()->json([
                'success' => true,
                'current_level' => [
                    'id' => $currentLevel->id,
                    'name' => $currentLevel->name,
                    'sections' => $currentLevelSections
                ],
                'next_level' => $nextLevel ? [
                    'id' => $nextLevel->id,
                    'name' => $nextLevel->name,
                    'sections' => $nextLevelSections
                ] : null
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
     * Get section capacity and enrolled count for a specific semester
     */
    public function get_section_capacity(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'semester_id' => 'nullable|exists:semesters,id'
        ]);

        try {
            $sectionId = $request->section_id;
            $semesterId = $request->semester_id;

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
            $enrolledCount = $this->getSectionEnrolledCount($sectionId, $semesterId);

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
     * Helper function to get enrolled count for a section in a specific semester
     */
    private function getSectionEnrolledCount($sectionId, $semesterId)
    {
        if (!$semesterId) {
            // No semester filter - count all students in section
            return DB::table('students')
                ->where('section_id', $sectionId)
                ->count();
        }

        // Count students enrolled in this section for this semester
        return DB::table('student_semester_enrollment')
            ->where('section_id', $sectionId)
            ->where('semester_id', $semesterId)
            ->where('enrollment_status', 'enrolled')
            ->count();
    }

    /**
     * Assign students to new section and enroll in target semester
     */
    public function assign_students(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'section_id' => 'required|exists:sections,id',
            'students' => 'required|array|min:1',
            'students.*.student_number' => 'required|exists:students,student_number',
            'students.*.new_section_id' => 'required|exists:sections,id',
            'students.*.student_type' => 'required|in:regular,irregular',
        ]);

        try {
            DB::beginTransaction();

            $semesterId = $request->semester_id;
            $assignedCount = 0;
            $errors = [];
            $auditRecords = [];

            // Get semester info for audit
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $semesterId)
                ->select('semesters.name as semester_name', 'school_years.code as sy_code')
                ->first();

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

                    // 2. Handle semester enrollment record
                    $existingEnrollment = DB::table('student_semester_enrollment')
                        ->where('student_number', $studentNumber)
                        ->where('semester_id', $semesterId)
                        ->first();

                    if ($existingEnrollment) {
                        // UPDATE: Same semester, just changing section
                        DB::table('student_semester_enrollment')
                            ->where('id', $existingEnrollment->id)
                            ->update([
                                'section_id' => $newSectionId,
                                'enrollment_status' => 'enrolled',
                                'updated_at' => now()
                            ]);
                    } else {
                        // INSERT: New semester enrollment
                        
                        // Mark previous enrollments as completed
                        DB::table('student_semester_enrollment')
                            ->where('student_number', $studentNumber)
                            ->where('enrollment_status', 'enrolled')
                            ->update([
                                'enrollment_status' => 'completed',
                                'updated_at' => now()
                            ]);

                        // Create new enrollment for target semester
                        DB::table('student_semester_enrollment')->insert([
                            'student_number' => $studentNumber,
                            'semester_id' => $semesterId,
                            'section_id' => $newSectionId,
                            'enrollment_status' => 'enrolled',
                            'enrollment_date' => now()->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    // 3. Handle class enrollments based on student type
                    if ($studentType === 'regular') {
                        // REGULAR: Remove individual class enrollments (they follow section)
                        $sectionClasses = DB::table('section_class_matrix')
                            ->where('section_id', $newSectionId)
                            ->where('semester_id', $semesterId)
                            ->get(['class_id']);

                        foreach ($sectionClasses as $sectionClass) {
                            $classCode = DB::table('classes')
                                ->where('id', $sectionClass->class_id)
                                ->value('class_code');

                            DB::table('student_class_matrix')
                                ->where('student_number', $studentNumber)
                                ->where('class_code', $classCode)
                                ->where('semester_id', $semesterId)
                                ->delete();
                        }

                    } else {
                        // IRREGULAR: Enroll in all section classes individually
                        $sectionClasses = DB::table('section_class_matrix')
                            ->where('section_id', $newSectionId)
                            ->where('semester_id', $semesterId)
                            ->get(['class_id']);

                        foreach ($sectionClasses as $sectionClass) {
                            $classCode = DB::table('classes')
                                ->where('id', $sectionClass->class_id)
                                ->value('class_code');

                            $exists = DB::table('student_class_matrix')
                                ->where('student_number', $studentNumber)
                                ->where('class_code', $classCode)
                                ->where('semester_id', $semesterId)
                                ->exists();

                            if (!$exists) {
                                DB::table('student_class_matrix')->insert([
                                    'student_number' => $studentNumber,
                                    'class_code' => $classCode,
                                    'semester_id' => $semesterId,
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
                            'semester_id' => $semesterId,
                            'semester_name' => $semester->semester_name ?? null,
                            'school_year' => $semester->sy_code ?? null,
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
                    "Bulk assigned {$assignedCount} student(s) to sections for {$semester->semester_name} {$semester->sy_code}",
                    null,
                    [
                        'semester_id' => $semesterId,
                        'semester_name' => $semester->semester_name ?? null,
                        'school_year' => $semester->sy_code ?? null,
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
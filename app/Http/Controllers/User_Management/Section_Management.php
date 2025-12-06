<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User_Management\Strand;
use App\Models\User_Management\Section;
use App\Models\User_Management\Level;
use App\Models\User_Management\Student;
use Exception;

class Section_Management extends MainController
{
    /**
     * Show the section assignment page
     */
    public function assign_section(Request $request)
    {
        $strands = Strand::where('status', 1)->get();
        $levels = Level::all();
        $sections = Section::where('status', 1)->get();
        
        // Get semesters
        $semesters = DB::table('semesters')
            ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
            ->select(
                'semesters.id',
                'semesters.name as semester_name',
                'semesters.code as semester_code',
                'school_years.code as year_code',
                'semesters.status'
            )
            ->orderBy('semesters.id', 'desc')
            ->get();

        $data = [
            'scripts' => [
                'user_management/assign_section.js',
            ],
            'strands' => $strands,
            'levels' => $levels,
            'sections' => $sections,
            'semesters' => $semesters,
        ];

        return view('admin.user_management.assign_section', $data);
    }

    /**
     * Load students from a source section
     * Includes both regular students (via section) and irregular students (via individual enrollment)
     */
    public function load_students_from_section(Request $request)
    {
        $request->validate([
            'source_section_id' => 'required|exists:sections,id',
            'source_semester_id' => 'nullable|exists:semesters,id'
        ]);

        try {
            $sourceSectionId = $request->source_section_id;
            $sourceSemesterId = $request->source_semester_id;

            // Get all students from this section
            $baseQuery = DB::table('students')
                ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
                ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
                ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
                ->where('students.section_id', $sourceSectionId);

            $students = $baseQuery->select(
                    'students.id',
                    'students.student_number',
                    'students.first_name',
                    'students.middle_name',
                    'students.last_name',
                    'students.student_type',
                    'students.section_id as current_section_id',
                    'sections.name as current_section',
                    'sections.code as current_section_code',
                    'levels.name as current_level',
                    'strands.code as current_strand'
                )
                ->orderBy('students.last_name')
                ->orderBy('students.first_name')
                ->get();

            return response()->json([
                'success' => true,
                'students' => $students,
                'count' => count($students)
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load students from section', [
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
     * Assign students to new section and enroll in semester
     * Handles both regular students (section-based) and irregular students (individual enrollment)
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

            foreach ($request->students as $studentData) {
                try {
                    $studentNumber = $studentData['student_number'];
                    $newSectionId = $studentData['new_section_id'];
                    $studentType = $studentData['student_type'];

                    // Get student
                    $student = Student::where('student_number', $studentNumber)->first();
                    
                    if (!$student) {
                        $errors[] = "Student {$studentNumber} not found";
                        continue;
                    }

                    // Update student's section and type
                    $student->update([
                        'section_id' => $newSectionId,
                        'student_type' => $studentType,
                        'updated_at' => now()
                    ]);

                    // Enroll student based on their type
                    if ($studentType === 'regular') {
                        // REGULAR STUDENTS: Enrolled via section_class_matrix
                        // Get all classes for the section in this semester
                        $sectionClasses = DB::table('section_class_matrix')
                            ->where('section_id', $newSectionId)
                            ->where('semester_id', $semesterId)
                            ->get(['class_id']);

                        foreach ($sectionClasses as $sectionClass) {
                            $classCode = DB::table('classes')
                                ->where('id', $sectionClass->class_id)
                                ->value('class_code');

                            // Remove any existing individual enrollments for this student/class/semester
                            DB::table('student_class_matrix')
                                ->where('student_number', $studentNumber)
                                ->where('class_code', $classCode)
                                ->where('semester_id', $semesterId)
                                ->delete();
                        }

                    } else {
                        // IRREGULAR STUDENTS: Enrolled individually via student_class_matrix
                        // Get all classes for the section in this semester
                        $sectionClasses = DB::table('section_class_matrix')
                            ->where('section_id', $newSectionId)
                            ->where('semester_id', $semesterId)
                            ->get(['class_id']);

                        foreach ($sectionClasses as $sectionClass) {
                            $classCode = DB::table('classes')
                                ->where('id', $sectionClass->class_id)
                                ->value('class_code');

                            // Check if already enrolled
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

                    $assignedCount++;

                } catch (Exception $e) {
                    $errors[] = "Failed to assign student {$studentNumber}: " . $e->getMessage();
                    \Log::error("Failed to assign student {$studentNumber}", [
                        'error' => $e->getMessage()
                    ]);
                }
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

    /**
     * Get sections filtered by strand and level
     */
    public function get_sections(Request $request)
    {
        $query = Section::where('status', 1);

        if ($request->strand_id) {
            $query->where('strand_id', $request->strand_id);
        }
        if ($request->level_id) {
            $query->where('level_id', $request->level_id);
        }

        $sections = $query->select('id', 'code', 'name')->get();

        return response()->json($sections);
    }
}
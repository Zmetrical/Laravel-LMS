<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User_Management\Section;
use App\Models\User_Management\Student;
use Exception;

class Section_Management extends MainController
{
    /**
     * Show the section assignment page
     */
    public function assign_section(Request $request)
    {
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
            'sections' => $sections,
            'semesters' => $semesters,
        ];

        return view('admin.user_management.assign_section', $data);
    }

    /**
     * Search sections by name or code
     */
    public function search_sections(Request $request)
    {
        $query = Section::where('status', 1);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $sections = $query->select('id', 'code', 'name')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($sections);
    }

    /**
     * Search students by number or name
     */
    public function search_students(Request $request)
    {
        // Accept both GET and POST
        $search = $request->input('search');
        
        if (!$search || strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'students' => [],
                'count' => 0
            ]);
        }

        try {

            $students = DB::table('students')
                ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
                ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
                ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
                ->where(function($query) use ($search) {
                    $query->where('students.student_number', 'LIKE', "%{$search}%")
                          ->orWhere('students.first_name', 'LIKE', "%{$search}%")
                          ->orWhere('students.last_name', 'LIKE', "%{$search}%")
                          ->orWhere(DB::raw("CONCAT(students.first_name, ' ', students.last_name)"), 'LIKE', "%{$search}%")
                          ->orWhere(DB::raw("CONCAT(students.last_name, ' ', students.first_name)"), 'LIKE', "%{$search}%");
                })
                ->select(
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
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'students' => $students,
                'count' => count($students)
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to search students', [
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
     * Load students from a source section
     * Filters by semester if provided:
     * - Regular students: Must have section enrolled in that semester (via section_class_matrix)
     * - Irregular students: Must be enrolled individually in that semester (via student_class_matrix)
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

            if ($sourceSemesterId) {
                // FILTER BY SEMESTER - Check actual enrollment records
                
                // Get regular students: section was active in that semester
                $regularStudents = DB::table('students')
                    ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
                    ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
                    ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
                    ->whereExists(function($query) use ($sourceSectionId, $sourceSemesterId) {
                        $query->select(DB::raw(1))
                            ->from('section_class_matrix')
                            ->where('section_class_matrix.section_id', $sourceSectionId)
                            ->where('section_class_matrix.semester_id', $sourceSemesterId);
                    })
                    ->where('students.section_id', $sourceSectionId)
                    ->where('students.student_type', 'regular')
                    ->select(
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
                    ->get();

                // Get irregular students: individually enrolled in that semester
                $irregularStudents = DB::table('students')
                    ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
                    ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
                    ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
                    ->whereExists(function($query) use ($sourceSemesterId) {
                        $query->select(DB::raw(1))
                            ->from('student_class_matrix')
                            ->whereColumn('student_class_matrix.student_number', 'students.student_number')
                            ->where('student_class_matrix.semester_id', $sourceSemesterId)
                            ->where('student_class_matrix.enrollment_status', 'enrolled');
                    })
                    ->where('students.section_id', $sourceSectionId)
                    ->where('students.student_type', 'irregular')
                    ->select(
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
                    ->get();

                // Merge both collections
                $students = $regularStudents->merge($irregularStudents)
                    ->sortBy([
                        ['last_name', 'asc'],
                        ['first_name', 'asc']
                    ])
                    ->values();

            } else {
                // NO SEMESTER FILTER - Get all students in section
                $students = DB::table('students')
                    ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
                    ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
                    ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
                    ->where('students.section_id', $sourceSectionId)
                    ->select(
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
            }

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

                    $student = Student::where('student_number', $studentNumber)->first();
                    
                    if (!$student) {
                        $errors[] = "Student {$studentNumber} not found";
                        continue;
                    }

                    $student->update([
                        'section_id' => $newSectionId,
                        'student_type' => $studentType,
                        'updated_at' => now()
                    ]);

                    if ($studentType === 'regular') {
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
}
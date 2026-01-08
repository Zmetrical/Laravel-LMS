<?php

namespace App\Http\Controllers\Enrollment_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

use App\Models\Enroll_Management\Section;
use App\Models\Enroll_Management\Classes;
use Illuminate\Support\Facades\DB;
use Exception;

class Enroll_Management extends MainController
{

    public function enroll_class()
    {
        $data = [
            'scripts' => ['enroll_management/teacher_enroll_class.js'],
        ];
        return view('admin.enroll_management.teacher_enroll_class', $data);
    }

    /**
     * Get sections data with filtering (AJAX)
     */
    public function getSectionsData(Request $request)
    {
        try {
            $query = Section::with(['strand', 'level', 'students'])
                ->active();

            // Apply filters
            if ($request->filled('grade')) {
                $query->byLevel($request->grade);
            }

            if ($request->filled('strand')) {
                $query->byStrand($request->strand);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $sections = $query->get();

            // Format data for response
            $formattedSections = $sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'code' => $section->code,
                    'name' => $section->name,
                    'grade' => $section->level->name,
                    'level_id' => $section->level_id,
                    'strand' => $section->strand->name,
                    'strand_code' => $section->strand->code,
                    'strand_id' => $section->strand_id,
                    'student_count' => $section->students->count(),
                    'class_count' => $section->classes->count(),
                    'full_name' => $section->full_name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedSections
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get section details including enrolled classes (AJAX)
     */
    public function getDetails($id)
    {
        try {
            $section = Section::with(['strand', 'level', 'students', 'classes'])
                ->findOrFail($id);

            // Format classes data
            $classes = $section->classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'code' => $class->class_code,
                    'name' => $class->class_name,
                ];
            });

            $data = [
                'id' => $section->id,
                'code' => $section->code,
                'name' => $section->name,
                'grade' => $section->level->name,
                'strand' => $section->strand->name,
                'strand_code' => $section->strand->code,
                'student_count' => $section->students->count(),
                'full_name' => $section->full_name,
                'classes' => $classes,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching section details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show section-class enrollment management page
     */
    public function sectionClassEnrollment()
    {
        $scripts = ['enroll_management/section_enroll_class.js'];
        return view('admin.enroll_management.section_enroll_class', compact('scripts'));
    }

    /**
     * Get classes enrolled in a specific section
     */
    public function getSectionClasses($id)
    {
        try {
            $section = Section::with(['classes.teachers', 'strand', 'level'])->findOrFail($id);

            $classes = $section->classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'ww_perc' => $class->ww_perc,
                    'pt_perc' => $class->pt_perc,
                    'qa_perce' => $class->qa_perce,
                    'teachers' => $class->teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'name' => "{$teacher->first_name} {$teacher->last_name}"
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'section' => [
                    'id' => $section->id,
                    'code' => $section->code,
                    'name' => $section->name,
                    'full_name' => $section->full_name,
                    'level' => $section->level->name,
                    'strand' => $section->strand->name
                ],
                'classes' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching section classes: ' . $e->getMessage()
            ], 500);
        }
    }

/**
 * Get available classes not yet enrolled in section
 */
public function getAvailableClasses($sectionId)
{
    try {
        $section = Section::findOrFail($sectionId);
        $enrolledClassIds = $section->classes()->pluck('classes.id');

        $availableClasses = Classes::with('teachers')
            ->whereNotIn('id', $enrolledClassIds)
            ->orderBy('class_name')
            ->get()
            ->map(function ($class) {
                $teachers = $class->teachers->map(function ($teacher) {
                    return trim("{$teacher->first_name} {$teacher->last_name}");
                })->filter()->implode(', ');

                return [
                    'id' => $class->id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'teachers' => $teachers ?: 'No teacher assigned',
                ];
            });

        return response()->json([
            'success' => true,
            'classes' => $availableClasses
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching available classes: ' . $e->getMessage()
        ], 500);
    }
}


        public function enrollClass(Request $request, $id)
        {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'semester_id' => 'required|exists:semesters,id'
            ]);

            // Check if already enrolled
            $exists = DB::table('section_class_matrix')
                ->where('section_id', $id)
                ->where('class_id', $request->class_id)
                ->where('semester_id', $request->semester_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class is already enrolled in this section for the selected semester'
                ], 400);
            }

            // Insert into section_class_matrix
            DB::table('section_class_matrix')->insert([
                'section_id' => $id,
                'class_id' => $request->class_id,
                'semester_id' => $request->semester_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Class enrolled successfully'
            ]);
        }

    /**
     * Remove a class from a section
     */
    public function removeClass($sectionId, $classId)
    {
        try {
            $section = Section::findOrFail($sectionId);

            // Check if class is enrolled
            if (!$section->classes()->where('class_id', $classId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class is not enrolled in this section'
                ], 404);
            }

            $section->classes()->detach($classId);

            return response()->json([
                'success' => true,
                'message' => 'Class removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing class: ' . $e->getMessage()
            ], 500);
        }
    }



    // ---------------------------------------------------------------------------
    //  Student Enroll
    // ---------------------------------------------------------------------------

    /**
     * Display irregular students page
     */
    public function studentIrregEnrollment()
    {
        $data = [
            'scripts' => ['enroll_management/student_irreg_list.js'],
        ];

        return view('admin.enroll_management.student_irreg_list', $data);
    }

    public function studentClassEnrollment($studentId)
    {
        $student = DB::table('students')->find($studentId);

        if (!$student) {
            return redirect()->route('admin.enroll_management.student_irreg_list')
                ->with('error', 'Student not found');
        }

        $data = [
            'studentId' => $studentId,
            'scripts' => ['enroll_management/student_enroll_class.js'],
        ];
        return view('admin.enroll_management.student_enroll_class', $data);
    }
    /**
     * Get student information
     */
    public function getStudentInfo($studentId)
    {
        try {
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->where('s.id', $studentId)
                ->select(
                    's.*',
                    'sec.name as section_name',
                    'str.name as strand_name',
                    'l.name as level_name'
                )
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $student
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get irregular students data
     */
    public function getStudentsData()
    {
        try {
            $students = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->leftJoin('student_class_matrix as scm', function ($join) {
                    $join->on(DB::raw('s.student_number COLLATE utf8mb4_general_ci'), '=', DB::raw('scm.student_number COLLATE utf8mb4_general_ci'));
                })
                ->where('s.student_type', 'irregular') // Filter only irregular students
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.section_id',
                    'sec.name as section_name',
                    'str.id as strand_id',
                    'str.name as strand_name',
                    'l.id as level_id',
                    'l.name as level_name',
                    DB::raw('COUNT(DISTINCT scm.class_code) as class_count')
                )
                ->groupBy(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.section_id',
                    'sec.name',
                    'str.id',
                    'str.name',
                    'l.id',
                    'l.name'
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's classes (enrolled and available)
     */
    public function getStudentClasses($studentId)
    {
        try {
            $student = DB::table('students')->find($studentId);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // Get enrolled classes
            $enrolled = DB::table('student_class_matrix as scm')
                ->join('classes as c', function ($join) {
                    $join->on(DB::raw('scm.class_code COLLATE utf8mb4_general_ci'), '=', DB::raw('c.class_code COLLATE utf8mb4_general_ci'));
                })
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('scm.student_number', $student->student_number)
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name")
                )
                ->distinct()
                ->get();

            // Get enrolled class IDs
            $enrolledClassIds = $enrolled->pluck('id')->toArray();

            // Get available classes (all classes not enrolled)
            $availableQuery = DB::table('classes as c')
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name")
                );

            if (!empty($enrolledClassIds)) {
                $availableQuery->whereNotIn('c.id', $enrolledClassIds);
            }

            $available = $availableQuery->orderBy('c.class_code')->get();

            return response()->json([
                'success' => true,
                'enrolled' => $enrolled,
                'available' => $available
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enroll student to classes
     */
    public function enrollStudentClass(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:classes,id',
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            DB::beginTransaction();

            $student = DB::table('students')->find($request->student_id);

            foreach ($request->class_ids as $classId) {
                $class = DB::table('classes')->find($classId);

                // Check if already enrolled
                $exists = DB::table('student_class_matrix')
                    ->where('student_number', $student->student_number)
                    ->where('class_code', $class->class_code)
                    ->where('semester_id', $request->semester_id)
                    ->exists();

                if (!$exists) {
                    DB::table('student_class_matrix')->insert([
                        'student_number' => $student->student_number,
                        'class_code' => $class->class_code,
                        'semester_id' => $request->semester_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unenroll student from a class
     */
    public function removeStudentClass(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id'
        ]);

        try {
            DB::beginTransaction();

            $student = DB::table('students')->find($request->student_id);
            $class = DB::table('classes')->find($request->class_id);

            DB::table('student_class_matrix')
                ->where('student_number', $student->student_number)
                ->where('class_code', $class->class_code)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class removed successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Unenrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Teacher Enroll
    // ---------------------------------------------------------------------------

    public function classes_enrollment()
    {
        $data = [
            'scripts' => ['enroll_management/teacher_enroll_class.js'],
        ];

        return view('admin.enroll_management.teacher_enroll_class', $data);
    }
    /**
     * Get all classes with section count and teacher info
     */
    public function getClassesList()
    {
        try {
            $classes = DB::table('classes as c')
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->leftJoin('section_class_matrix as scm', 'c.id', '=', 'scm.class_id')
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name"),
                    DB::raw('COUNT(DISTINCT scm.section_id) as section_count')
                )
                ->groupBy('c.id', 'c.class_code', 'c.class_name', 't.first_name', 't.last_name')
                ->orderBy('c.class_code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get class details (teacher and enrolled sections)
     */
    public function getClassDetails($classId)
    {
        try {
            // Get assigned teacher
            $teacher = DB::table('teacher_class_matrix as tcm')
                ->join('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('tcm.class_id', $classId)
                ->select('t.*')
                ->first();

            // Get enrolled sections with student count
            $sections = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->leftJoin('students as s', 'sec.id', '=', 's.section_id')
                ->where('scm.class_id', $classId)
                ->select(
                    'sec.id',
                    'sec.name',
                    'sec.code',
                    DB::raw('COUNT(s.id) as student_count')
                )
                ->groupBy('sec.id', 'sec.name', 'sec.code')
                ->orderBy('sec.name')
                ->get();

            return response()->json([
                'success' => true,
                'teacher' => $teacher,
                'sections' => $sections
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load class details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getClassStudents($classId)
    {
        try {
            $class = DB::table('classes')->find($classId);

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            // Get students from sections enrolled in this class (Regular students)
            $regularStudents = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->join('students as s', 'sec.id', '=', 's.section_id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->where('scm.class_id', $classId)
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.last_name',
                    's.student_type',
                    's.section_id',
                    'sec.name as section_name',
                    'str.name as strand_name',
                    'str.code as strand_code',
                    'l.name as level_name'
                )
                ->get();

            // Get irregular students directly enrolled in this class
            $irregularStudents = DB::table('student_class_matrix as scm')
                ->join('students as s', function ($join) {
                    $join->on(
                        DB::raw('scm.student_number COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('s.student_number COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->join('classes as c', function ($join) {
                    $join->on(
                        DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->where('c.id', $classId)
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.last_name',
                    's.student_type',
                    's.section_id',
                    'sec.name as section_name',
                    'str.name as strand_name',
                    'str.code as strand_code',

                    'l.name as level_name'
                )
                ->get();

            // Merge both collections and remove duplicates
            $allStudents = $regularStudents->merge($irregularStudents)
                ->unique('id')
                ->sortBy('last_name')
                ->values();

            // Get sections for filter
            $sections = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->where('scm.class_id', $classId)
                ->select('sec.id', 'sec.name')
                ->orderBy('sec.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $allStudents,
                'sections' => $sections
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all teachers
     */
    public function getTeachersList()
    {
        try {
            $teachers = DB::table('teachers')
                ->where('status', 1)
                ->select('id', 'first_name', 'last_name', 'email', 'phone')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load teachers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign teacher to class
     */
    public function assignTeacher(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'required|exists:teachers,id'
        ]);

        try {
            DB::beginTransaction();

            // Remove existing teacher assignment
            DB::table('teacher_class_matrix')
                ->where('class_id', $request->class_id)
                ->delete();

            // Assign new teacher
            DB::table('teacher_class_matrix')->insert([
                'teacher_id' => $request->teacher_id,
                'class_id' => $request->class_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher assigned successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove teacher from class
     */
    public function removeTeacher(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id'
        ]);

        try {
            DB::table('teacher_class_matrix')
                ->where('class_id', $request->class_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Teacher removed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove teacher: ' . $e->getMessage()
            ], 500);
        }
    }
}

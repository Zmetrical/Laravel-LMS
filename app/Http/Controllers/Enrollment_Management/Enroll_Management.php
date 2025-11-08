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
            'scripts' => ['enroll_management/enroll_class.js'],
        ];
        return view('admin.enroll_management.enroll_class', $data);
    }

    public function enroll_section()
    {

        $data = [
            'scripts' => ['enroll_management/enroll_section.js'],
        ];

        return view('admin.enroll_management.enroll_section', $data);
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


    /**
     * Enroll a class to a section
     */
    public function enrollClass(Request $request, $id)
    {
        try {
            $section = Section::findOrFail($id);

            $validated = $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            // Check if already enrolled
            if ($section->classes()->where('class_id', $validated['class_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class is already enrolled in this section'
                ], 400);
            }

            $section->classes()->attach($validated['class_id']);

            return response()->json([
                'success' => true,
                'message' => 'Class enrolled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error enrolling class: ' . $e->getMessage()
            ], 500);
        }
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
    public function enroll_student()
    {

        $data = [
            'scripts' => ['enroll_management/enroll_student.js'],
        ];

        return view('admin.enroll_management.enroll_student', $data);
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
                ->leftJoin('student_class_matrix as scm', function($join) {
                    $join->on(DB::raw('s.student_number COLLATE utf8mb4_general_ci'), '=', DB::raw('scm.student_number COLLATE utf8mb4_general_ci'));
                })
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
                ->join('classes as c', function($join) {
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
            'class_ids.*' => 'exists:classes,id'
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
                    ->exists();

                if (!$exists) {
                    DB::table('student_class_matrix')->insert([
                        'student_number' => $student->student_number,
                        'class_code' => $class->class_code
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
     * removeClass student from a class
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
}

<?php

namespace App\Http\Controllers\Enrollment_Management;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use App\Models\Enroll_Management\Section;
use App\Models\Enroll_Management\Classes;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Exception;

class Enroll_Management extends MainController
{
    use AuditLogger;

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

    /**
     * Enroll class to section
     */
    public function enrollClass(Request $request, $id)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            DB::beginTransaction();

            // Check if already enrolled
            $exists = DB::table('section_class_matrix')
                ->where('section_id', $id)
                ->where('class_id', $request->class_id)
                ->where('semester_id', $request->semester_id)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Class is already enrolled in this section for the selected semester'
                ], 400);
            }

            // Get related data for logging
            $section = DB::table('sections')->find($id);
            $class = DB::table('classes')->find($request->class_id);
            $semester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->where('s.id', $request->semester_id)
                ->select('s.*', 'sy.code as sy_code')
                ->first();

            // Insert into section_class_matrix
            DB::table('section_class_matrix')->insert([
                'section_id' => $id,
                'class_id' => $request->class_id,
                'semester_id' => $request->semester_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get the inserted record ID
            $matrixId = DB::getPdo()->lastInsertId();

            // Log the enrollment
            $this->logAudit(
                'enrolled',
                'section_class_matrix',
                (string)$matrixId,
                "Enrolled class '{$class->class_name}' to section '{$section->name}' for {$semester->name} ({$semester->sy_code})",
                null,
                [
                    'section_id' => $id,
                    'section_code' => $section->code,
                    'section_name' => $section->name,
                    'class_id' => $request->class_id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $request->semester_id,
                    'semester_name' => $semester->name,
                    'school_year' => $semester->sy_code,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class enrolled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a class from a section
     */
    public function removeClass($sectionId, $classId)
    {
        try {
            DB::beginTransaction();

            $section = Section::findOrFail($sectionId);

            // Check if class is enrolled
            if (!$section->classes()->where('class_id', $classId)->exists()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Class is not enrolled in this section'
                ], 404);
            }

            // Get class and semester info before deletion
            $class = DB::table('classes')->find($classId);
            $matrixRecord = DB::table('section_class_matrix')
                ->where('section_id', $sectionId)
                ->where('class_id', $classId)
                ->first();

            $semester = null;
            if ($matrixRecord && $matrixRecord->semester_id) {
                $semester = DB::table('semesters as s')
                    ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                    ->where('s.id', $matrixRecord->semester_id)
                    ->select('s.*', 'sy.code as sy_code')
                    ->first();
            }

            $section->classes()->detach($classId);

            // Log the removal
            $this->logAudit(
                'removed',
                'section_class_matrix',
                $matrixRecord ? (string)$matrixRecord->id : null,
                "Removed class '{$class->class_name}' from section '{$section->name}'" . 
                ($semester ? " for {$semester->name} ({$semester->sy_code})" : ''),
                [
                    'section_id' => $sectionId,
                    'section_code' => $section->code,
                    'section_name' => $section->name,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $matrixRecord->semester_id ?? null,
                    'semester_name' => $semester->name ?? null,
                    'school_year' => $semester->sy_code ?? null,
                ],
                null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
                ->where('s.student_type', 'irregular')
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
            $semester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->where('s.id', $request->semester_id)
                ->select('s.*', 'sy.code as sy_code')
                ->first();

            $enrolledClasses = [];
            $skippedClasses = [];

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

                    $enrolledClasses[] = [
                        'class_code' => $class->class_code,
                        'class_name' => $class->class_name,
                    ];
                } else {
                    $skippedClasses[] = $class->class_name;
                }
            }

            // Log the enrollment if any classes were enrolled
            if (!empty($enrolledClasses)) {
                $this->logAudit(
                    'enrolled',
                    'student_class_matrix',
                    $student->student_number,
                    "Enrolled student '{$student->first_name} {$student->last_name}' ({$student->student_number}) to " . 
                    count($enrolledClasses) . " class(es) for {$semester->name} ({$semester->sy_code})",
                    null,
                    [
                        'student_id' => $request->student_id,
                        'student_number' => $student->student_number,
                        'student_name' => "{$student->first_name} {$student->last_name}",
                        'semester_id' => $request->semester_id,
                        'semester_name' => $semester->name,
                        'school_year' => $semester->sy_code,
                        'enrolled_classes' => $enrolledClasses,
                        'total_enrolled' => count($enrolledClasses),
                    ]
                );
            }

            DB::commit();

            $message = 'Student enrolled successfully';
            if (!empty($skippedClasses)) {
                $message .= '. Already enrolled in: ' . implode(', ', $skippedClasses);
            }

            return response()->json([
                'success' => true,
                'message' => $message
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

            // Get enrollment info before deletion
            $enrollment = DB::table('student_class_matrix as scm')
                ->join('semesters as s', 'scm.semester_id', '=', 's.id')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->where('scm.student_number', $student->student_number)
                ->where('scm.class_code', $class->class_code)
                ->select('scm.*', 's.name as semester_name', 'sy.code as sy_code')
                ->first();

            DB::table('student_class_matrix')
                ->where('student_number', $student->student_number)
                ->where('class_code', $class->class_code)
                ->delete();

            // Log the removal
            $this->logAudit(
                'unenrolled',
                'student_class_matrix',
                $student->student_number,
                "Unenrolled student '{$student->first_name} {$student->last_name}' ({$student->student_number}) from '{$class->class_name}'" .
                ($enrollment ? " for {$enrollment->semester_name} ({$enrollment->sy_code})" : ''),
                [
                    'student_id' => $request->student_id,
                    'student_number' => $student->student_number,
                    'student_name' => "{$student->first_name} {$student->last_name}",
                    'class_id' => $request->class_id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $enrollment->semester_id ?? null,
                    'semester_name' => $enrollment->semester_name ?? null,
                    'school_year' => $enrollment->sy_code ?? null,
                ],
                null
            );

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

    // /**
    //  * Get all classes with section count and teacher info
    //  */
    // public function getClassesList()
    // {
    //     try {
    //         $activeSemester = $this->getActiveSemester();
            
    //         if (!$activeSemester) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No active semester found'
    //             ], 500);
    //         }

    //         $classes = DB::table('classes as c')
    //             ->leftJoin('teacher_class_matrix as tcm', function($join) use ($activeSemester) {
    //                 $join->on('c.id', '=', 'tcm.class_id')
    //                      ->where('tcm.semester_id', '=', $activeSemester->semester_id);
    //             })
    //             ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
    //             ->leftJoin('section_class_matrix as scm', function($join) use ($activeSemester) {
    //                 $join->on('c.id', '=', 'scm.class_id')
    //                      ->where('scm.semester_id', '=', $activeSemester->semester_id);
    //             })
    //             ->select(
    //                 'c.id',
    //                 'c.class_code',
    //                 'c.class_name',
    //                 DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name"),
    //                 DB::raw('COUNT(DISTINCT scm.section_id) as section_count')
    //             )
    //             ->groupBy('c.id', 'c.class_code', 'c.class_name', 't.first_name', 't.last_name')
    //             ->orderBy('c.class_code')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $classes
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to load classes: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getClassesList()
    {
        try {
            $activeSemester = $this->getActiveSemester();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 500);
            }

            // Get all classes with their assigned teacher for this semester
            $classes = DB::table('classes as c')
                ->leftJoin('teacher_class_matrix as tcm', function($join) use ($activeSemester) {
                    $join->on('c.id', '=', 'tcm.class_id')
                         ->where('tcm.semester_id', '=', $activeSemester->semester_id);
                })
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name")
                )
                ->orderBy('c.class_code')
                ->get();

foreach ($classes as $class) {
    // 1. Count Unique Sections (Regular + Irregular)
    $regularSectionIds = DB::table('section_class_matrix')
        ->where('class_id', $class->id)
        ->where('semester_id', $activeSemester->semester_id)
        ->pluck('section_id')
        ->toArray();

    $irregSectionIds = DB::table('student_class_matrix as stcm')
        ->join('students as s', 'stcm.student_number', '=', 's.student_number')
        ->where('stcm.class_code', $class->class_code)
        ->where('stcm.semester_id', $activeSemester->semester_id)
        ->where('stcm.enrollment_status', 'enrolled')
        ->whereNotNull('s.section_id')
        ->pluck('s.section_id')
        ->toArray();

    $class->section_count = count(array_unique(array_merge($regularSectionIds, $irregSectionIds)));

    // 2. Count Total Unique Students (Merge Regular + Irregular) — unchanged
    $sectionStudentNumbers = DB::table('section_class_matrix as scm')
        ->join('students as s', 'scm.section_id', '=', 's.section_id')
        ->where('scm.class_id', $class->id)
        ->where('scm.semester_id', $activeSemester->semester_id)
        ->pluck('s.student_number')
        ->toArray();

    $matrixStudentNumbers = DB::table('student_class_matrix as stcm')
        ->where('stcm.class_code', $class->class_code)
        ->where('stcm.semester_id', $activeSemester->semester_id)
        ->where('stcm.enrollment_status', 'enrolled')
        ->pluck('stcm.student_number')
        ->toArray();

    $allStudents = array_merge($sectionStudentNumbers, $matrixStudentNumbers);
    $class->student_count = count(array_unique($allStudents));
}

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
    // public function getClassDetails($classId)
    // {
    //     try {
    //         $activeSemester = $this->getActiveSemester();
            
    //         if (!$activeSemester) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No active semester found'
    //             ], 500);
    //         }

    //         // Get assigned teacher for current semester
    //         $teacher = DB::table('teacher_class_matrix as tcm')
    //             ->join('teachers as t', 'tcm.teacher_id', '=', 't.id')
    //             ->where('tcm.class_id', $classId)
    //             ->where('tcm.semester_id', $activeSemester->semester_id)
    //             ->select('t.*', 'tcm.created_at as assigned_at')
    //             ->first();

    //         // Get enrolled sections with student count
    //         $sections = DB::table('section_class_matrix as scm')
    //             ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
    //             ->leftJoin('students as s', 'sec.id', '=', 's.section_id')
    //             ->where('scm.class_id', $classId)
    //             ->where('scm.semester_id', $activeSemester->semester_id)
    //             ->select(
    //                 'sec.id',
    //                 'sec.name',
    //                 'sec.code',
    //                 DB::raw('COUNT(s.id) as student_count')
    //             )
    //             ->groupBy('sec.id', 'sec.name', 'sec.code')
    //             ->orderBy('sec.name')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'teacher' => $teacher,
    //             'sections' => $sections
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to load class details: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
public function getClassDetails($classId)
{
    try {
        $activeSemester = $this->getActiveSemester();

        if (!$activeSemester) {
            return response()->json([
                'success' => false,
                'message' => 'No active semester found'
            ], 500);
        }

        $class = DB::table('classes')->find($classId);

        // Assigned teacher
        $teacher = DB::table('teacher_class_matrix as tcm')
            ->join('teachers as t', 'tcm.teacher_id', '=', 't.id')
            ->where('tcm.class_id', $classId)
            ->where('tcm.semester_id', $activeSemester->semester_id)
            ->select('t.*', 'tcm.created_at as assigned_at')
            ->first();

        // Sections from section_class_matrix (regular students)
        $regularSections = DB::table('section_class_matrix as scm')
            ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
            ->leftJoin('students as s', 'sec.id', '=', 's.section_id')
            ->where('scm.class_id', $classId)
            ->where('scm.semester_id', $activeSemester->semester_id)
            ->select('sec.id', 'sec.name', 'sec.code', DB::raw('COUNT(s.id) as student_count'))
            ->groupBy('sec.id', 'sec.name', 'sec.code')
            ->get();

        // Sections from irregular students enrolled via student_class_matrix
        $irregSections = DB::table('student_class_matrix as stcm')
            ->join('students as s', 'stcm.student_number', '=', 's.student_number')
            ->join('sections as sec', 's.section_id', '=', 'sec.id')
            ->where('stcm.class_code', $class->class_code)
            ->where('stcm.semester_id', $activeSemester->semester_id)
            ->where('stcm.enrollment_status', 'enrolled')
            ->whereNotNull('s.section_id')
            ->select('sec.id', 'sec.name', 'sec.code', DB::raw('COUNT(s.id) as student_count'))
            ->groupBy('sec.id', 'sec.name', 'sec.code')
            ->get();

        // Merge, deduplicate by section id, sum student counts for sections in both
        $merged = $regularSections->keyBy('id');

        foreach ($irregSections as $sec) {
            if ($merged->has($sec->id)) {
                // Section already in regular list — add irregular count
                $merged[$sec->id]->student_count += $sec->student_count;
            } else {
                $merged->put($sec->id, $sec);
            }
        }

        $sections = $merged->sortBy('name')->values();

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
    // public function getClassStudents($classId)
    // {
    //     try {
    //         $class = DB::table('classes')->find($classId);

    //         if (!$class) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Class not found'
    //             ], 404);
    //         }

    //         // Get students from sections enrolled in this class (Regular students)
    //         $regularStudents = DB::table('section_class_matrix as scm')
    //             ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
    //             ->join('students as s', 'sec.id', '=', 's.section_id')
    //             ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
    //             ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
    //             ->where('scm.class_id', $classId)
    //             ->select(
    //                 's.id',
    //                 's.student_number',
    //                 's.first_name',
    //                 's.last_name',
    //                 's.student_type',
    //                 's.section_id',
    //                 'sec.name as section_name',
    //                 'str.name as strand_name',
    //                 'str.code as strand_code',
    //                 'l.name as level_name'
    //             )
    //             ->get();

    //         // Get irregular students directly enrolled in this class
    //         $irregularStudents = DB::table('student_class_matrix as scm')
    //             ->join('students as s', function ($join) {
    //                 $join->on(
    //                     DB::raw('scm.student_number COLLATE utf8mb4_unicode_ci'),
    //                     '=',
    //                     DB::raw('s.student_number COLLATE utf8mb4_unicode_ci')
    //                 );
    //             })
    //             ->join('classes as c', function ($join) {
    //                 $join->on(
    //                     DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
    //                     '=',
    //                     DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
    //                 );
    //             })
    //             ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
    //             ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
    //             ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
    //             ->where('c.id', $classId)
    //             ->select(
    //                 's.id',
    //                 's.student_number',
    //                 's.first_name',
    //                 's.last_name',
    //                 's.student_type',
    //                 's.section_id',
    //                 'sec.name as section_name',
    //                 'str.name as strand_name',
    //                 'str.code as strand_code',
    //                 'l.name as level_name'
    //             )
    //             ->get();

    //         // Merge both collections and remove duplicates
    //         $allStudents = $regularStudents->merge($irregularStudents)
    //             ->unique('id')
    //             ->sortBy('last_name')
    //             ->values();

    //         // Get sections for filter
    //         $sections = DB::table('section_class_matrix as scm')
    //             ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
    //             ->where('scm.class_id', $classId)
    //             ->select('sec.id', 'sec.name')
    //             ->orderBy('sec.name')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $allStudents,
    //             'sections' => $sections
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to load students: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getClassStudents($classId)
    {
        try {
            $class = DB::table('classes')->where('id', $classId)->first();

            if (!$class) {
                return response()->json(['success' => false, 'message' => 'Class not found'], 404);
            }

            // Get Active Semester
            $activeSemester = $this->getActiveSemester();
            $semesterId = $activeSemester ? $activeSemester->semester_id : null;

            // 1. Get students from sections enrolled in this class (Regulars)
            // Added scm.semester_id check
            $regularStudents = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->join('students as s', 'sec.id', '=', 's.section_id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->where('scm.class_id', $classId)
                ->where('scm.semester_id', $semesterId) 
                ->where('s.student_type', 'regular')

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
                    'l.name as level_name',
                    DB::raw("'section' as enrollment_source")
                )
                ->get();

            // 2. Get irregular students directly enrolled in this class
            // Added scm.semester_id check AND enrollment_status check
$irregularStudents = DB::table('student_class_matrix as scm')
    ->join('students as s', 'scm.student_number', '=', 's.student_number')
    ->where('scm.class_code', $class->class_code)
    ->where('scm.semester_id', $semesterId)
    ->where('scm.enrollment_status', 'enrolled')
    ->select(
        's.id',
        's.student_number',
        's.first_name',
        's.last_name',
        's.student_type',
        DB::raw('NULL as section_id'),
        DB::raw('NULL as section_name'),
        DB::raw('NULL as strand_name'),
        DB::raw('NULL as strand_code'),
        DB::raw('NULL as level_name'),
        DB::raw("'direct' as enrollment_source")
    )
    ->get();

            // 3. Merge both collections and remove duplicates
            // We prioritize Direct enrollment if a student appears in both (rare, but prevents duplicates)
            $allStudents = $irregularStudents->merge($regularStudents)
                ->unique('student_number') 
                ->sortBy('last_name')
                ->values();

            // 4. Get sections for filter dropdown
            $sections = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->where('scm.class_id', $classId)
                ->where('scm.semester_id', $semesterId)
                ->select('sec.id', 'sec.name')
                ->orderBy('sec.name')
                ->distinct()
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

        $activeSemester = $this->getActiveSemester();
        
        if (!$activeSemester) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No active semester found'
            ], 400);
        }

        // Get class and teacher info
        $class = DB::table('classes')->find($request->class_id);
        $teacher = DB::table('teachers')->find($request->teacher_id);

        // Check if there's an existing assignment
        $existingAssignment = DB::table('teacher_class_matrix')
            ->where('class_id', $request->class_id)
            ->where('semester_id', $activeSemester->semester_id)
            ->first();

        $oldTeacher = null;
        if ($existingAssignment) {
            $oldTeacher = DB::table('teachers')->find($existingAssignment->teacher_id);
        }

        // Remove existing teacher assignment for this class and semester
        DB::table('teacher_class_matrix')
            ->where('class_id', $request->class_id)
            ->where('semester_id', $activeSemester->semester_id)
            ->delete();

        // Assign new teacher
        DB::table('teacher_class_matrix')->insert([
            'teacher_id' => $request->teacher_id,
            'class_id' => $request->class_id,
            'semester_id' => $activeSemester->semester_id,
            'school_year_id' => $activeSemester->school_year->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ✅ CREATE INITIAL TEACHER STATUS TRAIL
        $this->ensureTeacherActiveTrail($request->teacher_id, $activeSemester->school_year->id);

        // Log the assignment
        $description = $oldTeacher 
            ? "Reassigned class '{$class->class_name}' from {$oldTeacher->first_name} {$oldTeacher->last_name} to {$teacher->first_name} {$teacher->last_name} for {$activeSemester->semester_name} ({$activeSemester->school_year->code})"
            : "Assigned teacher {$teacher->first_name} {$teacher->last_name} to class '{$class->class_name}' for {$activeSemester->semester_name} ({$activeSemester->school_year->code})";

        $this->logAudit(
            $oldTeacher ? 'reassigned' : 'assigned',
            'teacher_class_matrix',
            (string)$request->class_id,
            $description,
            $oldTeacher ? [
                'teacher_id' => $oldTeacher->id,
                'teacher_name' => "{$oldTeacher->first_name} {$oldTeacher->last_name}",
                'teacher_email' => $oldTeacher->email,
            ] : null,
            [
                'teacher_id' => $request->teacher_id,
                'teacher_name' => "{$teacher->first_name} {$teacher->last_name}",
                'teacher_email' => $teacher->email,
                'class_id' => $request->class_id,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'semester_id' => $activeSemester->semester_id,
                'semester_name' => $activeSemester->semester_name,
                'school_year_id' => $activeSemester->school_year->id,
                'school_year' => $activeSemester->school_year->code,
            ]
        );

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

private function ensureTeacherActiveTrail($teacherId, $schoolYearId)
{
    $existing = DB::table('teacher_school_year_status')
        ->where('teacher_id', $teacherId)
        ->where('school_year_id', $schoolYearId)
        ->first();
    
    // Only create initial trail if none exists
    if (!$existing) {
        DB::table('teacher_school_year_status')->insert([
            'teacher_id' => $teacherId,
            'school_year_id' => $schoolYearId,
            'status' => 'active',
            'reactivated_by' => Auth::guard('admin')->id(),
            'reactivated_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        \Log::info('Created initial teacher status trail', [
            'teacher_id' => $teacherId,
            'school_year_id' => $schoolYearId,
            'trigger' => 'class_assignment'
        ]);
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
            DB::beginTransaction();

            $activeSemester = $this->getActiveSemester();
            
            if (!$activeSemester) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            // Get assignment info before deletion
            $assignment = DB::table('teacher_class_matrix as tcm')
                ->join('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->join('classes as c', 'tcm.class_id', '=', 'c.id')
                ->where('tcm.class_id', $request->class_id)
                ->where('tcm.semester_id', $activeSemester->semester_id)
                ->select('t.*', 'c.class_code', 'c.class_name')
                ->first();

            DB::table('teacher_class_matrix')
                ->where('class_id', $request->class_id)
                ->where('semester_id', $activeSemester->semester_id)
                ->delete();

            // Log the removal
            if ($assignment) {
                $this->logAudit(
                    'removed',
                    'teacher_class_matrix',
                    (string)$request->class_id,
                    "Removed teacher {$assignment->first_name} {$assignment->last_name} from class '{$assignment->class_name}' for {$activeSemester->semester_name} ({$activeSemester->school_year->code})",
                    [
                        'teacher_id' => $assignment->id,
                        'teacher_name' => "{$assignment->first_name} {$assignment->last_name}",
                        'teacher_email' => $assignment->email,
                        'class_id' => $request->class_id,
                        'class_code' => $assignment->class_code,
                        'class_name' => $assignment->class_name,
                        'semester_id' => $activeSemester->semester_id,
                        'semester_name' => $activeSemester->semester_name,
                        'school_year' => $activeSemester->school_year->code,
                    ],
                    null
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Teacher removed successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active semester with school year info
     */
    private function getActiveSemester()
    {
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                'sy.id as school_year_id',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->first();

        if (!$activeSemester) {
            return null;
        }

        return (object)[
            'semester_id' => $activeSemester->semester_id,
            'semester_name' => $activeSemester->semester_name,
            'semester_code' => $activeSemester->semester_code,
            'school_year' => (object)[
                'id' => $activeSemester->school_year_id,
                'code' => $activeSemester->school_year_code,
                'year_start' => $activeSemester->year_start,
                'year_end' => $activeSemester->year_end
            ]
        ];
    }
}
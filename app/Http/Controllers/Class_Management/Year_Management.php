<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class Year_Management extends MainController
{
    public function list_schoolyear()
    {
        $data = [
            'scripts' => ['class_management/list_schoolyear.js'],
        ];

        return view('admin.class_management.list_schoolyear', $data);
    }
    /**
     * Get school years data (AJAX)
     */
    public function getSchoolYearsData()
    {
        try {
            $schoolYears = DB::table('school_years')
                ->select(
                    'id',
                    'year_start',
                    'year_end',
                    'code',
                    'status',
                    'created_at',
                    'updated_at'
                )
                ->orderBy('year_start', 'desc')
                ->get();

            // Get semester count for each school year
            foreach ($schoolYears as $sy) {
                $sy->semesters_count = DB::table('semesters')
                    ->where('school_year_id', $sy->id)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => $schoolYears
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get school years data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load school years data.'
            ], 500);
        }
    }

    /**
     * Create school year
     */
    public function createSchoolYear(Request $request)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
        ]);

        try {
            // Validate year difference is exactly 1
            if ($request->year_end - $request->year_start != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year must span exactly one year (e.g., 2024-2025).'
                ], 422);
            }

            // Generate code
            $code = $request->year_start . '-' . $request->year_end;

            // Check if already exists
            $exists = DB::table('school_years')->where('code', $code)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year already exists.'
                ], 422);
            }

            DB::beginTransaction();

            $schoolYearId = DB::table('school_years')->insertGetId([
                'year_start' => $request->year_start,
                'year_end' => $request->year_end,
                'code' => $code,
                'status' => 'upcoming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('School year created successfully', [
                'school_year_id' => $schoolYearId,
                'code' => $code,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year created successfully!',
                'school_year_id' => $schoolYearId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create school year', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create school year: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update school year
     */
    public function updateSchoolYear(Request $request, $id)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
            'status' => 'required|in:active,completed,upcoming',
        ]);

        try {
            $schoolYear = DB::table('school_years')->where('id', $id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            // Validate year difference
            if ($request->year_end - $request->year_start != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year must span exactly one year.'
                ], 422);
            }

            $code = $request->year_start . '-' . $request->year_end;

            // Check if code exists (excluding current)
            $exists = DB::table('school_years')
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year already exists.'
                ], 422);
            }

            // If setting to active, deactivate others
            if ($request->status === 'active') {
                DB::table('school_years')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

            DB::beginTransaction();

            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'year_start' => $request->year_start,
                    'year_end' => $request->year_end,
                    'code' => $code,
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            \Log::info('School year updated successfully', [
                'school_year_id' => $id,
                'code' => $code,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year updated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update school year', [
                'school_year_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update school year: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set active school year
     */
    public function setActiveSchoolYear($id)
    {
        try {
            $schoolYear = DB::table('school_years')->where('id', $id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            DB::beginTransaction();

            // Deactivate all
            DB::table('school_years')->update(['status' => 'upcoming']);

            // Activate selected
            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year activated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to activate school year', [
                'school_year_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate school year.'
            ], 500);
        }
    }

    /**
     * List semester management page
     */
    public function list_semester()
    {
        $data = [
            'scripts' => ['class_management/list_semester.js'],
        ];

        return view('admin.class_management.list_semester', $data);
    }

    /**
     * Get semesters data (AJAX)
     */
    public function getSemestersData(Request $request)
    {
        try {
            $query = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->select(
                    'semesters.id',
                    'semesters.school_year_id',
                    'semesters.name',
                    'semesters.code',
                    'semesters.start_date',
                    'semesters.end_date',
                    'semesters.status',
                    'semesters.created_at',
                    'semesters.updated_at',
                    'school_years.code as school_year_code',
                    'school_years.year_start',
                    'school_years.year_end'
                );

            // Filter by school year if provided
            if ($request->has('school_year_id') && $request->school_year_id != '') {
                $query->where('semesters.school_year_id', $request->school_year_id);
            }

            $semesters = $query->orderBy('school_years.year_start', 'desc')
                ->orderBy('semesters.start_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $semesters
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get semesters data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load semesters data.'
            ], 500);
        }
    }

    /**
     * Create semester
     */
    public function createSemester(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            // Check if code already exists for this school year
            $exists = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->where('code', $request->code)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester code already exists for this school year.'
                ], 422);
            }

            DB::beginTransaction();

            $semesterId = DB::table('semesters')->insertGetId([
                'school_year_id' => $request->school_year_id,
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'upcoming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Semester created successfully', [
                'semester_id' => $semesterId,
                'code' => $request->code,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester created successfully!',
                'semester_id' => $semesterId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create semester', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create semester: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update semester
     */
    public function updateSemester(Request $request, $id)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,completed,upcoming',
        ]);

        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            // Check if code exists (excluding current)
            $exists = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->where('code', $request->code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester code already exists for this school year.'
                ], 422);
            }

            // If setting to active, deactivate others
            if ($request->status === 'active') {
                DB::table('semesters')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'school_year_id' => $request->school_year_id,
                    'name' => $request->name,
                    'code' => strtoupper($request->code),
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            \Log::info('Semester updated successfully', [
                'semester_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester updated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update semester: ' . $e->getMessage()
            ], 500);
        }
    }



/**
 * Get classes enrolled in a specific semester
 */
public function getSemesterClasses($id)
{
    try {
        $semester = DB::table('semesters')->find($id);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Semester not found'
            ], 404);
        }

        // Get classes from section_class_matrix with section-enrolled students
        $sectionClasses = DB::table('section_class_matrix as secm')
            ->join('classes as c', 'secm.class_id', '=', 'c.id')
            ->join('students as s', 's.section_id', '=', 'secm.section_id')
            ->where('secm.semester_id', $id)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name',
                DB::raw('COUNT(DISTINCT s.student_number) as student_count')
            )
            ->groupBy('c.id', 'c.class_code', 'c.class_name')
            ->get();

        // Get classes from student_class_matrix (individual enrollments)
        $individualClasses = DB::table('student_class_matrix as scm')
            ->join('classes as c', function ($join) {
                $join->on(
                    DB::raw('scm.class_code COLLATE utf8mb4_general_ci'),
                    '=',
                    DB::raw('c.class_code COLLATE utf8mb4_general_ci')
                );
            })
            ->where('scm.semester_id', $id)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name',
                DB::raw('COUNT(DISTINCT scm.student_number) as student_count')
            )
            ->groupBy('c.id', 'c.class_code', 'c.class_name')
            ->get();

        // Merge and sum student counts
        $classesMap = [];
        
        foreach ($sectionClasses as $class) {
            $classesMap[$class->id] = [
                'id' => $class->id,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'student_count' => $class->student_count
            ];
        }
        
        foreach ($individualClasses as $class) {
            if (isset($classesMap[$class->id])) {
                $classesMap[$class->id]['student_count'] += $class->student_count;
            } else {
                $classesMap[$class->id] = [
                    'id' => $class->id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'student_count' => $class->student_count
                ];
            }
        }

        // Convert to collection and sort
        $classes = collect(array_values($classesMap))->sortBy('class_code')->values();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    } catch (Exception $e) {
        \Log::error('Failed to load semester classes', [
            'semester_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load semester classes: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get enrollment history for a specific class in a semester
 */
public function getEnrollmentHistory($semesterId, $classCode)
{
    try {
        // Get the class_id from class_code
        $class = DB::table('classes')
            ->where('class_code', $classCode)
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
        }

        // Get sections that have this class in the semester through section_class_matrix
        $sectionsWithClass = DB::table('section_class_matrix as secm')
            ->where('secm.class_id', $class->id)
            ->where('secm.semester_id', $semesterId)
            ->pluck('secm.section_id')
            ->toArray();

        // Get students enrolled through their sections
        $sectionStudents = collect();
        if (!empty($sectionsWithClass)) {
            $sectionStudents = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->leftJoin('grades_final as gf', function ($join) use ($classCode, $semesterId) {
                    $join->on(
                        DB::raw('s.student_number COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('gf.student_number COLLATE utf8mb4_general_ci')
                    )
                    ->where('gf.class_code', $classCode)
                    ->where('gf.semester_id', $semesterId);
                })
                ->whereIn('s.section_id', $sectionsWithClass)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.student_type',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'lvl.name as level_name',
                    DB::raw("'enrolled' as enrollment_status"),
                    'gf.final_grade',
                    'gf.remarks',
                    'gf.is_locked',
                    DB::raw("'Section' as enrollment_source")
                )
                ->get();
        }

        // Get students enrolled individually through student_class_matrix
        $individualStudents = DB::table('student_class_matrix as scm')
            ->join('students as s', function ($join) {
                $join->on(
                    DB::raw('scm.student_number COLLATE utf8mb4_general_ci'),
                    '=',
                    DB::raw('s.student_number COLLATE utf8mb4_general_ci')
                );
            })
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->leftJoin('grades_final as gf', function ($join) use ($classCode, $semesterId) {
                $join->on(
                    DB::raw('s.student_number COLLATE utf8mb4_general_ci'),
                    '=',
                    DB::raw('gf.student_number COLLATE utf8mb4_general_ci')
                )
                ->where('gf.class_code', $classCode)
                ->where('gf.semester_id', $semesterId);
            })
            ->where('scm.semester_id', $semesterId)
            ->where('scm.class_code', $classCode)
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name',
                'scm.enrollment_status',
                'gf.final_grade',
                'gf.remarks',
                'gf.is_locked',
                DB::raw("'Individual' as enrollment_source")
            )
            ->get();

        // Merge both collections and remove duplicates
        $allStudents = $individualStudents->concat($sectionStudents)
            ->unique('student_number')
            ->values();

        // Add computed full name
        $allStudents = $allStudents->map(function ($student) {
            $student->full_name = trim($student->first_name . ' ' . 
                                      ($student->middle_name ? substr($student->middle_name, 0, 1) . '. ' : '') . 
                                      $student->last_name);
            return $student;
        });

        // Sort by last name, first name
        $allStudents = $allStudents->sortBy([
            ['last_name', 'asc'],
            ['first_name', 'asc']
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $allStudents,
            'summary' => [
                'total_enrolled' => $allStudents->count(),
                'section_enrolled' => $allStudents->where('enrollment_source', 'Section')->count(),
                'individual_enrolled' => $allStudents->where('enrollment_source', 'Individual')->count(),
                'with_grades' => $allStudents->whereNotNull('final_grade')->count(),
                'locked_grades' => $allStudents->where('is_locked', 1)->count()
            ]
        ]);
    } catch (Exception $e) {
        \Log::error('Failed to load enrollment history', [
            'semester_id' => $semesterId,
            'class_code' => $classCode,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load enrollment history: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Set active semester
     */
    public function setActiveSemester($id)
    {
        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            DB::beginTransaction();

            // Deactivate all
            DB::table('semesters')->update(['status' => 'upcoming']);

            // Activate selected
            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            // Also set the parent school year as active
            DB::table('school_years')->update(['status' => 'upcoming']);
            DB::table('school_years')
                ->where('id', $semester->school_year_id)
                ->update(['status' => 'active']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester activated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to activate semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate semester.'
            ], 500);
        }
    }
}

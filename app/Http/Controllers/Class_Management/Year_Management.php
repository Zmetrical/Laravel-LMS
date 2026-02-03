<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Exception;
use Illuminate\Support\Facades\DB;

class Year_Management extends MainController
{
    use AuditLogger;

    // Maximum semesters per school year
    const MAX_SEMESTERS_PER_YEAR = 3;

    public function list_schoolyear()
    {
        $data = [
            'scripts' => ['class_management/list_schoolyear.js'],
            'max_semesters' => self::MAX_SEMESTERS_PER_YEAR,
        ];

        return view('admin.class_management.list_schoolyear', $data);
    }

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

    public function createSchoolYear(Request $request)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
        ]);

        try {
            if ($request->year_end - $request->year_start != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year must span exactly one year (e.g., 2024-2025).'
                ], 422);
            }

            $code = $request->year_start . '-' . $request->year_end;

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

            // Log audit
            $this->logAudit(
                'created',
                'school_years',
                (string)$schoolYearId,
                "Created school year '{$code}'",
                null,
                [
                    'year_start' => $request->year_start,
                    'year_end' => $request->year_end,
                    'code' => $code,
                    'status' => 'upcoming',
                ]
            );

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

            if ($request->year_end - $request->year_start != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year must span exactly one year.'
                ], 422);
            }

            $code = $request->year_start . '-' . $request->year_end;

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

            if ($request->status === 'active') {
                DB::table('school_years')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

            // Prepare old values for audit
            $oldValues = [
                'year_start' => $schoolYear->year_start,
                'year_end' => $schoolYear->year_end,
                'code' => $schoolYear->code,
                'status' => $schoolYear->status,
            ];

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

            // Log audit
            $this->logAudit(
                'updated',
                'school_years',
                (string)$id,
                "Updated school year from '{$schoolYear->code}' to '{$code}'",
                $oldValues,
                [
                    'year_start' => $request->year_start,
                    'year_end' => $request->year_end,
                    'code' => $code,
                    'status' => $request->status,
                ]
            );

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

            DB::table('school_years')->update(['status' => 'upcoming']);

            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            // Log audit
            $this->logAudit(
                'activated',
                'school_years',
                (string)$id,
                "Activated school year '{$schoolYear->code}'",
                ['status' => $schoolYear->status],
                ['status' => 'active']
            );

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

    public function list_semester()
    {
        $data = [
            'scripts' => ['class_management/list_semester.js'],
        ];

        return view('admin.class_management.list_semester', $data);
    }

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

    public function createSemester(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            // Get school year info for audit
            $schoolYear = DB::table('school_years')->where('id', $request->school_year_id)->first();

            // Check current semester count
            $currentCount = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->count();

            if ($currentCount >= self::MAX_SEMESTERS_PER_YEAR) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum of " . self::MAX_SEMESTERS_PER_YEAR . " semesters per school year reached."
                ], 422);
            }

            // Auto-generate semester order (next number)
            $nextOrder = $currentCount + 1;

            // Generate name based on order
            $semesterNames = [
                1 => '1st Semester',
                2 => '2nd Semester',
                3 => '3rd Semester'
            ];

            // Generate code based on order
            $semesterCodes = [
                1 => 'SEM1',
                2 => 'SEM2',
                3 => 'SEM3'
            ];

            $name = $semesterNames[$nextOrder];
            $code = $semesterCodes[$nextOrder];

            DB::beginTransaction();

            $semesterId = DB::table('semesters')->insertGetId([
                'school_year_id' => $request->school_year_id,
                'name' => $name,
                'code' => $code,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'upcoming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log audit
            $this->logAudit(
                'created',
                'semesters',
                (string)$semesterId,
                "Created {$name} for school year {$schoolYear->code}",
                null,
                [
                    'school_year_id' => $request->school_year_id,
                    'school_year_code' => $schoolYear->code,
                    'name' => $name,
                    'code' => $code,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => 'upcoming',
                ]
            );

            \Log::info('Semester created successfully', [
                'semester_id' => $semesterId,
                'code' => $code,
                'order' => $nextOrder,
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

    public function updateSemester(Request $request, $id)
    {
        $validated = $request->validate([
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

            // Get school year info for audit
            $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

            if ($request->status === 'active') {
                DB::table('semesters')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

            // Prepare old values for audit
            $oldValues = [
                'start_date' => $semester->start_date,
                'end_date' => $semester->end_date,
                'status' => $semester->status,
            ];

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            // Log audit
            $this->logAudit(
                'updated',
                'semesters',
                (string)$id,
                "Updated {$semester->name} for school year {$schoolYear->code}",
                $oldValues,
                [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status,
                ]
            );

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

            // Get classes from section_class_matrix with teacher info
            $sectionClasses = DB::table('section_class_matrix as secm')
                ->join('classes as c', 'secm.class_id', '=', 'c.id')
                ->join('students as s', 's.section_id', '=', 'secm.section_id')
                ->leftJoin('teacher_class_matrix as tcm', function($join) use ($id) {
                    $join->on('tcm.class_id', '=', 'c.id')
                         ->where('tcm.semester_id', '=', $id);
                })
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('secm.semester_id', $id)
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw('COUNT(DISTINCT s.student_number) as student_count'),
                    DB::raw('GROUP_CONCAT(DISTINCT CONCAT(t.first_name, " ", t.last_name) SEPARATOR ", ") as teachers')
                )
                ->groupBy('c.id', 'c.class_code', 'c.class_name')
                ->get();

            // Get classes from student_class_matrix with teacher info
            $individualClasses = DB::table('student_class_matrix as scm')
                ->join('classes as c', function ($join) {
                    $join->on(
                        DB::raw('scm.class_code COLLATE utf8mb4_general_ci'),
                        '=',
                        DB::raw('c.class_code COLLATE utf8mb4_general_ci')
                    );
                })
                ->leftJoin('teacher_class_matrix as tcm', function($join) use ($id) {
                    $join->on('tcm.class_id', '=', 'c.id')
                         ->where('tcm.semester_id', '=', $id);
                })
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('scm.semester_id', $id)
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw('COUNT(DISTINCT scm.student_number) as student_count'),
                    DB::raw('GROUP_CONCAT(DISTINCT CONCAT(t.first_name, " ", t.last_name) SEPARATOR ", ") as teachers')
                )
                ->groupBy('c.id', 'c.class_code', 'c.class_name')
                ->get();

            // Merge classes
            $classesMap = [];
            
            foreach ($sectionClasses as $class) {
                $classesMap[$class->id] = [
                    'id' => $class->id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'student_count' => $class->student_count,
                    'teachers' => $class->teachers
                ];
            }
            
            foreach ($individualClasses as $class) {
                if (isset($classesMap[$class->id])) {
                    $classesMap[$class->id]['student_count'] += $class->student_count;
                    // Merge teachers if different
                    if ($class->teachers && $classesMap[$class->id]['teachers'] !== $class->teachers) {
                        $existingTeachers = explode(', ', $classesMap[$class->id]['teachers'] ?? '');
                        $newTeachers = explode(', ', $class->teachers);
                        $allTeachers = array_unique(array_merge($existingTeachers, $newTeachers));
                        $classesMap[$class->id]['teachers'] = implode(', ', array_filter($allTeachers));
                    }
                } else {
                    $classesMap[$class->id] = [
                        'id' => $class->id,
                        'class_code' => $class->class_code,
                        'class_name' => $class->class_name,
                        'student_count' => $class->student_count,
                        'teachers' => $class->teachers
                    ];
                }
            }

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

    public function getEnrollmentHistory($semesterId, $classCode)
    {
        try {
            $class = DB::table('classes')
                ->where('class_code', $classCode)
                ->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            // Get quarters for this semester
            $quarters = DB::table('quarters')
                ->where('semester_id', $semesterId)
                ->orderBy('order_number')
                ->get();

            // Get sections that have this class
            $sectionsWithClass = DB::table('section_class_matrix as secm')
                ->where('secm.class_id', $class->id)
                ->where('secm.semester_id', $semesterId)
                ->pluck('secm.section_id')
                ->toArray();

            // Get students from sections (regular students)
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
                        'gf.q1_grade',
                        'gf.q2_grade',
                        'gf.final_grade',
                        'gf.remarks',
                        DB::raw("'Section' as enrollment_source")
                    )
                    ->get();
            }

            // Get individually enrolled students (irregular students)
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
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks',
                    DB::raw("'Individual' as enrollment_source")
                )
                ->get();

            // Combine and remove duplicates
            $allStudents = $individualStudents->concat($sectionStudents)
                ->unique('student_number')
                ->values();

            // Get quarter grades for all students
            $studentNumbers = $allStudents->pluck('student_number')->toArray();
            
            $quarterGrades = DB::table('quarter_grades as qg')
                ->join('quarters as q', 'qg.quarter_id', '=', 'q.id')
                ->whereIn('qg.student_number', $studentNumbers)
                ->where('qg.class_code', $classCode)
                ->where('q.semester_id', $semesterId)
                ->select(
                    'qg.student_number',
                    'q.code as quarter_code',
                    'qg.transmuted_grade'
                )
                ->get()
                ->groupBy('student_number');

            // Format student names and add quarter grades
            $allStudents = $allStudents->map(function ($student) use ($quarterGrades) {
                $student->full_name = trim($student->first_name . ' ' . 
                                          ($student->middle_name ? substr($student->middle_name, 0, 1) . '. ' : '') . 
                                          $student->last_name);
                
                // Add quarter grades
                $studentQuarterGrades = $quarterGrades->get($student->student_number, collect());
                $student->q1_transmuted = $studentQuarterGrades->where('quarter_code', '1ST')->first()->transmuted_grade ?? null;
                $student->q2_transmuted = $studentQuarterGrades->where('quarter_code', '2ND')->first()->transmuted_grade ?? null;
                
                return $student;
            });

            // Sort by last name, then first name
            $allStudents = $allStudents->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc']
            ])->values();

            return response()->json([
                'success' => true,
                'data' => $allStudents,
                'quarters' => $quarters,
                'summary' => [
                    'total_enrolled' => $allStudents->count(),
                    'section_enrolled' => $allStudents->where('enrollment_source', 'Section')->count(),
                    'individual_enrolled' => $allStudents->where('enrollment_source', 'Individual')->count(),
                    'with_grades' => $allStudents->whereNotNull('final_grade')->count(),
                    'passed' => $allStudents->where('remarks', 'PASSED')->count(),
                    'failed' => $allStudents->where('remarks', 'FAILED')->count()
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

    public function getQuarters($semesterId)
    {
        try {
            $semester = DB::table('semesters')->where('id', $semesterId)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            $quarters = DB::table('quarters')
                ->where('semester_id', $semesterId)
                ->orderBy('order_number', 'asc')
                ->select('id', 'semester_id', 'name', 'code', 'order_number', 'created_at', 'updated_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $quarters,
                'semester' => [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'code' => $semester->code
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get quarters', [
                'semester_id' => $semesterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load quarters: ' . $e->getMessage()
            ], 500);
        }
    }

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

            // Get school year info for audit
            $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

            DB::beginTransaction();

            DB::table('semesters')->update(['status' => 'upcoming']);

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            DB::table('school_years')->update(['status' => 'upcoming']);
            DB::table('school_years')
                ->where('id', $semester->school_year_id)
                ->update(['status' => 'active']);

            // Log audit
            $this->logAudit(
                'activated',
                'semesters',
                (string)$id,
                "Activated {$semester->name} for school year {$schoolYear->code}",
                ['status' => $semester->status],
                ['status' => 'active']
            );

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
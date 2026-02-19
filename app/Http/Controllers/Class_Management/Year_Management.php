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
            'year_start' => 'required|integer|min:2000|max:3000',
            'year_end' => 'required|integer|min:2000|max:3000|gt:year_start',
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
            'year_start' => 'required|integer|min:2000|max:3000',
            'year_end' => 'required|integer|min:2000|max:3000|gt:year_start',
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

            $oldValues = [
                'year_start' => $schoolYear->year_start,
                'year_end' => $schoolYear->year_end,
                'code' => $schoolYear->code,
            ];

            DB::beginTransaction();

            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'year_start' => $request->year_start,
                    'year_end' => $request->year_end,
                    'code' => $code,
                    'updated_at' => now(),
                ]);

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

        // FIXED: Only reset active school years to upcoming, preserve completed status
        DB::table('school_years')
            ->where('status', 'active')
            ->update(['status' => 'upcoming']);

        DB::table('school_years')
            ->where('id', $id)
            ->update([
                'status' => 'active',
                'updated_at' => now()
            ]);

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
            $schoolYear = DB::table('school_years')->where('id', $request->school_year_id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            $syStartDate = "{$schoolYear->year_start}-01-01"; 
            $syEndDate = "{$schoolYear->year_end}-12-31";
            
            if ($request->start_date < $syStartDate || $request->start_date > $syEndDate) {
                return response()->json([
                    'success' => false,
                    'message' => "Semester start date must be within school year {$schoolYear->year_start}-{$schoolYear->year_end} (between {$syStartDate} and {$syEndDate})."
                ], 422);
            }

            if ($request->end_date < $syStartDate || $request->end_date > $syEndDate) {
                return response()->json([
                    'success' => false,
                    'message' => "Semester end date must be within school year {$schoolYear->year_start}-{$schoolYear->year_end} (between {$syStartDate} and {$syEndDate})."
                ], 422);
            }

            // Check for overlapping semesters
            $overlapping = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                          });
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester dates overlap with an existing semester in this school year.'
                ], 422);
            }

            $currentCount = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->count();

            if ($currentCount >= self::MAX_SEMESTERS_PER_YEAR) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum of " . self::MAX_SEMESTERS_PER_YEAR . " semesters per school year reached."
                ], 422);
            }

            $nextOrder = $currentCount + 1;

            $semesterNames = [
                1 => '1st Semester',
                2 => '2nd Semester',
                3 => '3rd Semester'
            ];

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

            // Create quarters for this semester
            $quarters = [
                ['name' => '1st Quarter', 'code' => '1ST', 'order' => 1],
                ['name' => '2nd Quarter', 'code' => '2ND', 'order' => 2]
            ];

            foreach ($quarters as $quarter) {
                DB::table('quarters')->insert([
                    'semester_id' => $semesterId,
                    'name' => $quarter['name'],
                    'code' => $quarter['code'],
                    'order_number' => $quarter['order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->logAudit(
                'created',
                'semesters',
                (string)$semesterId,
                "Created {$name} for school year {$schoolYear->code} with 2 quarters",
                null,
                [
                    'school_year_id' => $request->school_year_id,
                    'school_year_code' => $schoolYear->code,
                    'name' => $name,
                    'code' => $code,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => 'upcoming',
                    'quarters_created' => 2
                ]
            );

            \Log::info('Semester created successfully with quarters', [
                'semester_id' => $semesterId,
                'code' => $code,
                'order' => $nextOrder,
                'quarters_created' => 2
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester created successfully with 2 quarters!',
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
        ]);

        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            // Validate semester dates are within school year bounds
            $syStartDate = "{$schoolYear->year_start}-01-01";
            $syEndDate = "{$schoolYear->year_end}-12-31";
            
            if ($request->start_date < $syStartDate || $request->start_date > $syEndDate) {
                return response()->json([
                    'success' => false,
                    'message' => "Semester start date must be within school year {$schoolYear->year_start}-{$schoolYear->year_end} (between {$syStartDate} and {$syEndDate})."
                ], 422);
            }

            if ($request->end_date < $syStartDate || $request->end_date > $syEndDate) {
                return response()->json([
                    'success' => false,
                    'message' => "Semester end date must be within school year {$schoolYear->year_start}-{$schoolYear->year_end} (between {$syStartDate} and {$syEndDate})."
                ], 422);
            }

            // Check for overlapping semesters (excluding current semester)
            $overlapping = DB::table('semesters')
                ->where('school_year_id', $semester->school_year_id)
                ->where('id', '!=', $id)
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                          });
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester dates overlap with an existing semester in this school year.'
                ], 422);
            }

            $oldValues = [
                'start_date' => $semester->start_date,
                'end_date' => $semester->end_date,
            ];

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'updated_at' => now(),
                ]);

            $this->logAudit(
                'updated',
                'semesters',
                (string)$id,
                "Updated {$semester->name} for school year {$schoolYear->code}",
                $oldValues,
                [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
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

public function getSemesterSections($id)
    {
        try {
            $semester = DB::table('semesters')->find($id);

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found'
                ], 404);
            }

            // 1. Get sections that have CLASSES in this semester
            $classSectionIds = DB::table('section_class_matrix')
                ->where('semester_id', $id)
                ->pluck('section_id')
                ->toArray();

            // 2. Get sections that have STUDENTS enrolled in this semester
            // (This fixes the "Missing Section" bug for historical data)
            $studentSectionIds = DB::table('student_semester_enrollment')
                ->where('semester_id', $id)
                ->whereNotNull('section_id')
                ->where('enrollment_status', 'enrolled')
                ->pluck('section_id')
                ->toArray();

            // 3. Merge both lists to get ALL active sections for this semester
            $sectionIds = array_unique(array_merge($classSectionIds, $studentSectionIds));

            if (empty($sectionIds)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Get section details
            $sections = DB::table('sections as sec')
                ->join('strands as str', 'sec.strand_id', '=', 'str.id')
                ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                // Count current regular students (Optional: could switch to historical count if needed)
                ->leftJoin('students as s', function($join) {
                    $join->on('s.section_id', '=', 'sec.id')
                         ->where('s.student_type', '=', 'regular');
                })
                ->whereIn('sec.id', $sectionIds)
                ->where('sec.status', 1)
                ->select(
                    'sec.id',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'lvl.name as level_name',
                    DB::raw('COUNT(DISTINCT s.student_number) as regular_student_count')
                )
                ->groupBy('sec.id', 'sec.code', 'sec.name', 'str.code', 'lvl.name')
                ->orderBy('sec.name')
                ->get();

            // Get class count for each section
            foreach ($sections as $section) {
                $section->class_count = DB::table('section_class_matrix')
                    ->where('section_id', $section->id)
                    ->where('semester_id', $id)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => $sections
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to load semester sections', [
                'semester_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load semester sections: ' . $e->getMessage()
            ], 500);
        }
    }

public function getSectionEnrollment($semesterId, $sectionId)
{
    try {
        $section = DB::table('sections as sec')
            ->join('strands as str', 'sec.strand_id', '=', 'str.id')
            ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->where('sec.id', $sectionId)
            ->select(
                'sec.id',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'str.name as strand_name',
                'lvl.name as level_name'
            )
            ->first();

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        $quarters = DB::table('quarters')
            ->where('semester_id', $semesterId)
            ->orderBy('order_number')
            ->get();

        $students = DB::table('student_semester_enrollment as sse')
            ->join('students as s', 'sse.student_number', '=', 's.student_number')
            ->where('sse.semester_id', $semesterId)
            ->where('sse.section_id', $sectionId)
            ->where('sse.enrollment_status', 'enrolled')
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type'
            )
            ->get();

        // Regular classes via section_class_matrix only
        $regularClasses = DB::table('section_class_matrix as scm')
            ->join('classes as c', 'scm.class_id', '=', 'c.id')
            ->leftJoin('teacher_class_matrix as tcm', function ($join) use ($semesterId) {
                $join->on('tcm.class_id', '=', 'c.id')
                     ->where('tcm.semester_id', '=', $semesterId);
            })
            ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
            ->where('scm.section_id', $sectionId)
            ->where('scm.semester_id', $semesterId)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name',
                DB::raw('CONCAT(COALESCE(t.first_name, ""), " ", COALESCE(t.last_name, "")) as teacher_name')
            )
            ->get();

        // $classes = section regular classes only (used for dropdown + regular student grades)
        $classes = $regularClasses->values();

        // Irregular students: fetch their classes per-student, NOT merged into $classes
        $irregularStudentNumbers = $students
            ->where('student_type', 'irregular')
            ->pluck('student_number')
            ->toArray();

        $irregularClassesByStudent = [];

        if (!empty($irregularStudentNumbers)) {
            $irregEnrollments = DB::table('student_class_matrix')
                ->whereIn('student_number', $irregularStudentNumbers)
                ->where('semester_id', $semesterId)
                ->where('enrollment_status', 'enrolled')
                ->select('student_number', 'class_code')
                ->get();

            $irregClassCodes = $irregEnrollments->pluck('class_code')->unique()->toArray();

            $irregClassDetails = collect();
            if (!empty($irregClassCodes)) {
                $irregClassDetails = DB::table('classes as c')
                    ->leftJoin('teacher_class_matrix as tcm', function ($join) use ($semesterId) {
                        $join->on('tcm.class_id', '=', 'c.id')
                             ->where('tcm.semester_id', '=', $semesterId);
                    })
                    ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                    ->whereIn('c.class_code', $irregClassCodes)
                    ->select(
                        'c.id',
                        'c.class_code',
                        'c.class_name',
                        DB::raw('CONCAT(COALESCE(t.first_name, ""), " ", COALESCE(t.last_name, "")) as teacher_name')
                    )
                    ->get()
                    ->keyBy('class_code');
            }

            foreach ($irregEnrollments as $enrollment) {
                $detail = $irregClassDetails->get($enrollment->class_code);
                if ($detail) {
                    $irregularClassesByStudent[$enrollment->student_number][] = $detail;
                }
            }
        }

        $studentNumbers = $students->pluck('student_number')->toArray();

        // Include all irreg class codes so grades are fetched for them too
        $irregAllClassCodes = collect(array_values($irregularClassesByStudent))
            ->flatten(1)
            ->pluck('class_code')
            ->unique()
            ->toArray();

        $classCodes = array_unique(array_merge(
            $classes->pluck('class_code')->toArray(),
            $irregAllClassCodes
        ));

        \Log::info('Section Enrollment Debug', [
            'semester_id' => $semesterId,
            'section_id' => $sectionId,
            'students_count' => count($studentNumbers),
            'regular_classes_count' => $classes->count(),
            'irreg_class_codes' => $irregAllClassCodes,
            'student_numbers' => $studentNumbers,
            'class_codes' => $classCodes
        ]);

        $quarterGradesData = [];
        $finalGradesData = [];

        if (!empty($studentNumbers) && !empty($classCodes)) {
            $quarterGrades = DB::table('quarter_grades as qg')
                ->join('quarters as q', 'qg.quarter_id', '=', 'q.id')
                ->whereIn('qg.student_number', $studentNumbers)
                ->whereIn('qg.class_code', $classCodes)
                ->where('q.semester_id', $semesterId)
                ->select(
                    'qg.student_number',
                    'qg.class_code',
                    'q.code as quarter_code',
                    'qg.transmuted_grade',
                    'qg.initial_grade'
                )
                ->get();

            foreach ($quarterGrades as $grade) {
                if (!isset($quarterGradesData[$grade->student_number])) {
                    $quarterGradesData[$grade->student_number] = [];
                }
                if (!isset($quarterGradesData[$grade->student_number][$grade->class_code])) {
                    $quarterGradesData[$grade->student_number][$grade->class_code] = [];
                }
                $quarterGradesData[$grade->student_number][$grade->class_code][$grade->quarter_code] = $grade->transmuted_grade;
            }

            $finalGrades = DB::table('grades_final')
                ->whereIn('student_number', $studentNumbers)
                ->whereIn('class_code', $classCodes)
                ->where('semester_id', $semesterId)
                ->select(
                    'student_number',
                    'class_code',
                    'q1_grade',
                    'q2_grade',
                    'final_grade',
                    'remarks'
                )
                ->get();

            foreach ($finalGrades as $grade) {
                if (!isset($finalGradesData[$grade->student_number])) {
                    $finalGradesData[$grade->student_number] = [];
                }
                $finalGradesData[$grade->student_number][$grade->class_code] = [
                    'q1_grade' => $grade->q1_grade,
                    'q2_grade' => $grade->q2_grade,
                    'final_grade' => $grade->final_grade,
                    'remarks' => $grade->remarks
                ];
            }
        }

        $students = $students->map(function ($student) use ($quarterGradesData, $finalGradesData, $classes, $irregularClassesByStudent) {
            $student->full_name = trim($student->first_name . ' ' .
                                      ($student->middle_name ? substr($student->middle_name, 0, 1) . '. ' : '') .
                                      $student->last_name);

            $studentNum = $student->student_number;
            $isIrreg = $student->student_type === 'irregular';

            // Irregular students use their own enrolled classes, regular use section classes
            $studentClasses = $isIrreg
                ? collect($irregularClassesByStudent[$studentNum] ?? [])
                : $classes;

            $student->class_grades = $studentClasses->map(function ($class) use ($studentNum, $quarterGradesData, $finalGradesData) {
                $classCode = $class->class_code;

                $q1 = null;
                $q2 = null;

                if (isset($quarterGradesData[$studentNum][$classCode])) {
                    $q1 = $quarterGradesData[$studentNum][$classCode]['1ST'] ?? null;
                    $q2 = $quarterGradesData[$studentNum][$classCode]['2ND'] ?? null;
                }

                $finalGrade = null;
                $q1FromFinal = null;
                $q2FromFinal = null;
                $remarks = null;

                if (isset($finalGradesData[$studentNum][$classCode])) {
                    $finalData = $finalGradesData[$studentNum][$classCode];
                    $q1FromFinal = $finalData['q1_grade'];
                    $q2FromFinal = $finalData['q2_grade'];
                    $finalGrade = $finalData['final_grade'];
                    $remarks = $finalData['remarks'];
                }

                $q1 = $q1 ?? $q1FromFinal;
                $q2 = $q2 ?? $q2FromFinal;

                return [
                    'class_code' => $classCode,
                    'class_name' => $class->class_name,
                    'q1' => $q1,
                    'q2' => $q2,
                    'final_grade' => $finalGrade,
                    'remarks' => $remarks
                ];
            })->values()->toArray();

            return $student;
        });

        $students = $students->sortBy([
            ['last_name', 'asc'],
            ['first_name', 'asc']
        ])->values();

        return response()->json([
            'success' => true,
            'section' => $section,
            'classes' => $classes,
            'students' => $students,
            'quarters' => $quarters,
            'summary' => [
                'total_students' => $students->count(),
                'total_classes' => $classes->count()
            ]
        ]);
    } catch (Exception $e) {
        \Log::error('Failed to load section enrollment', [
            'semester_id' => $semesterId,
            'section_id' => $sectionId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load section enrollment: ' . $e->getMessage()
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

        $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

        DB::beginTransaction();

        DB::table('semesters')
            ->where('status', 'active')
            ->update(['status' => 'upcoming']);

        DB::table('semesters')
            ->where('id', $id)
            ->update([
                'status' => 'active',
                'activated_at' => now(),
                'activated_by' => \Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);

        DB::table('school_years')
            ->where('status', 'active')
            ->update(['status' => 'upcoming']);
            
        DB::table('school_years')
            ->where('id', $semester->school_year_id)
            ->update([
                'status' => 'active',
                'activated_at' => now(),
                'activated_by' => \Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);

        // Sync students.section_id from their enrollment in this semester
        $enrollments = DB::table('student_semester_enrollment')
            ->where('semester_id', $id)
            ->where('enrollment_status', 'enrolled')
            ->whereNotNull('section_id')
            ->select('student_number', 'section_id')
            ->get();

        foreach ($enrollments as $enrollment) {
            DB::table('students')
                ->where('student_number', $enrollment->student_number)
                ->update([
                    'section_id' => $enrollment->section_id,
                    'updated_at' => now()
                ]);
        }

        $this->logAudit(
            'activated',
            'semesters',
            (string)$id,
            "Activated {$semester->name} for school year {$schoolYear->code}. Synced section_id for {$enrollments->count()} student(s).",
            ['status' => $semester->status],
            ['status' => 'active', 'students_synced' => $enrollments->count()]
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Semester activated successfully!',
            'students_synced' => $enrollments->count()
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
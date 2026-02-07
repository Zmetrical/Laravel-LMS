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

            $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

            if ($request->status === 'active') {
                DB::table('semesters')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

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

            // Get sections that have classes enrolled in this semester via section_class_matrix
            $sectionIds = DB::table('section_class_matrix')
                ->where('semester_id', $id)
                ->distinct()
                ->pluck('section_id');

            if ($sectionIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Get section details with student count
            $sections = DB::table('sections as sec')
                ->join('strands as str', 'sec.strand_id', '=', 'str.id')
                ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
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

            // Get quarters for this semester
            $quarters = DB::table('quarters')
                ->where('semester_id', $semesterId)
                ->orderBy('order_number')
                ->get();

            // Get classes enrolled for this section
            $classes = DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('teacher_class_matrix as tcm', function($join) use ($semesterId) {
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
                    DB::raw('CONCAT(t.first_name, " ", t.last_name) as teacher_name')
                )
                ->get();

            // Get regular students in this section
            $students = DB::table('students as s')
                ->where('s.section_id', $sectionId)
                ->where('s.student_type', 'regular')
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.student_type'
                )
                ->get();

            // Get grades for all students across all classes
            $studentNumbers = $students->pluck('student_number')->toArray();
            $classCodes = $classes->pluck('class_code')->toArray();

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
                        'qg.transmuted_grade'
                    )
                    ->get()
                    ->groupBy('student_number');

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
                    ->get()
                    ->groupBy('student_number');
            } else {
                $quarterGrades = collect();
                $finalGrades = collect();
            }

            // Format student data with grades
            $students = $students->map(function ($student) use ($quarterGrades, $finalGrades, $classes) {
                $student->full_name = trim($student->first_name . ' ' . 
                                          ($student->middle_name ? substr($student->middle_name, 0, 1) . '. ' : '') . 
                                          $student->last_name);
                
                $studentQuarterGrades = $quarterGrades->get($student->student_number, collect());
                $studentFinalGrades = $finalGrades->get($student->student_number, collect());
                
                // Organize grades by class
                $student->class_grades = $classes->map(function($class) use ($studentQuarterGrades, $studentFinalGrades) {
                    $classQuarterGrades = $studentQuarterGrades->where('class_code', $class->class_code);
                    $classFinalGrade = $studentFinalGrades->where('class_code', $class->class_code)->first();
                    
                    return [
                        'class_code' => $class->class_code,
                        'class_name' => $class->class_name,
                        'q1' => $classQuarterGrades->where('quarter_code', '1ST')->first()->transmuted_grade ?? null,
                        'q2' => $classQuarterGrades->where('quarter_code', '2ND')->first()->transmuted_grade ?? null,
                        'final_grade' => $classFinalGrade->final_grade ?? null,
                        'remarks' => $classFinalGrade->remarks ?? null
                    ];
                });
                
                return $student;
            });

            // Sort by last name, then first name
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
<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Exception;
use Illuminate\Support\Facades\DB;

class Graduation_Management extends MainController
{
    use AuditLogger;

    /**
     * Get Grade 12 level ID from levels table.
     */
    private function getGrade12LevelId(): ?int
    {
        $level = DB::table('levels')
            ->whereRaw('LOWER(name) LIKE ?', ['%grade 12%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%12%'])
            ->orderByRaw('LOWER(name) ASC')
            ->first();

        return $level ? (int) $level->id : null;
    }

    /**
     * Check if all semesters in a school year are completed.
     */
    private function allSemestersCompleted(int $schoolYearId): bool
    {
        $total = DB::table('semesters')->where('school_year_id', $schoolYearId)->count();

        if ($total === 0) return false;

        $completed = DB::table('semesters')
            ->where('school_year_id', $schoolYearId)
            ->where('status', 'completed')
            ->count();

        return $total === $completed;
    }

    /**
     * Main graduation management page for a school year.
     */
    public function show($schoolYearId)
    {
        $schoolYear = DB::table('school_years')->where('id', $schoolYearId)->first();

        if (!$schoolYear) {
            abort(404, 'School year not found.');
        }

        if (!$this->allSemestersCompleted($schoolYearId)) {
            return redirect()->route('admin.schoolyears.index')
                ->with('error', 'All semesters must be completed before managing graduation.');
        }

        $isFinalized = DB::table('graduation_records')
            ->where('school_year_id', $schoolYearId)
            ->where('is_finalized', true)
            ->exists();

        $data = [
            'school_year'  => $schoolYear,
            'is_finalized' => $isFinalized,
            'scripts'      => ['class_management/graduation.js'],
        ];

        return view('admin.class_management.graduation', $data);
    }

    /**
     * Return all Grade 12 students (regular + irregular) with eligibility data.
     */
    public function getStudents(Request $request, $schoolYearId)
    {
        try {
            $schoolYear = DB::table('school_years')->where('id', $schoolYearId)->first();

            if (!$schoolYear) {
                return response()->json(['success' => false, 'message' => 'School year not found.'], 404);
            }

            if (!$this->allSemestersCompleted($schoolYearId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All semesters must be completed before viewing graduation data.'
                ], 422);
            }

            $grade12LevelId = $this->getGrade12LevelId();

            if (!$grade12LevelId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade 12 level is not configured in the levels table.'
                ], 422);
            }

            // Semesters under this school year
            $semesterIds = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->pluck('id')
                ->toArray();

            if (empty($semesterIds)) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'summary' => ['total' => 0, 'eligible' => 0, 'issues' => 0, 'missing' => 0],
                    'school_year' => $schoolYear,
                ]);
            }

            // ── REGULAR STUDENTS ──────────────────────────────────────────────
            $grade12SectionIds = DB::table('sections')
                ->where('level_id', $grade12LevelId)
                ->where('status', 1)
                ->pluck('id')
                ->toArray();

            $activeSectionIds = DB::table('section_class_matrix')
                ->whereIn('section_id', $grade12SectionIds)
                ->whereIn('semester_id', $semesterIds)
                ->distinct()
                ->pluck('section_id')
                ->toArray();

            $regularStudents   = collect();
            $regularClassCodes = [];

            if (!empty($activeSectionIds)) {
                $regularStudents = DB::table('students')
                    ->whereIn('section_id', $activeSectionIds)
                    ->where('student_type', 'regular')
                    ->select('student_number', 'first_name', 'middle_name', 'last_name', 'gender', 'section_id', 'student_type')
                    ->get();

                $sectionClassMap = DB::table('section_class_matrix as scm')
                    ->join('classes as c', 'scm.class_id', '=', 'c.id')
                    ->whereIn('scm.section_id', $activeSectionIds)
                    ->whereIn('scm.semester_id', $semesterIds)
                    ->select('scm.section_id', 'scm.semester_id', 'c.class_code')
                    ->get()
                    ->groupBy('section_id');

                foreach ($regularStudents as $student) {
                    $sectionClasses = $sectionClassMap[$student->section_id] ?? collect();
                    $regularClassCodes[$student->student_number] = $sectionClasses->map(function ($sc) {
                        return ['class_code' => $sc->class_code, 'semester_id' => $sc->semester_id];
                    })->values()->toArray();
                }
            }

            // ── IRREGULAR STUDENTS ────────────────────────────────────────────
            $irregularStudentNumbers = DB::table('student_level_matrix')
                ->where('level_id', $grade12LevelId)
                ->whereIn('semester_id', $semesterIds)
                ->distinct()
                ->pluck('student_number')
                ->toArray();

            $irregularStudents    = collect();
            $irregularEnrollments = collect();

            if (!empty($irregularStudentNumbers)) {
                $irregularStudents = DB::table('students')
                    ->whereIn('student_number', $irregularStudentNumbers)
                    ->where('student_type', 'irregular')
                    ->select('student_number', 'first_name', 'middle_name', 'last_name', 'gender', 'section_id', 'student_type')
                    ->get();

                $irregularEnrollments = DB::table('student_class_matrix')
                    ->whereIn('student_number', $irregularStudentNumbers)
                    ->whereIn('semester_id', $semesterIds)
                    ->where('enrollment_status', '!=', 'dropped')
                    ->select('student_number', 'class_code', 'semester_id')
                    ->get()
                    ->groupBy('student_number');
            }

            // ── MERGE ─────────────────────────────────────────────────────────
            $allStudents = $regularStudents->merge($irregularStudents);

            if ($allStudents->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'summary' => ['total' => 0, 'eligible' => 0, 'issues' => 0, 'missing' => 0],
                    'school_year' => $schoolYear,
                    'note' => 'No Grade 12 students found for this school year.',
                ]);
            }

            $allStudentNumbers = $allStudents->pluck('student_number')->toArray();

            // Section names keyed by section_id
            $allSectionIds = $allStudents->pluck('section_id')->filter()->unique()->values()->toArray();
            $sectionNames  = DB::table('sections')
                ->whereIn('id', $allSectionIds)
                ->pluck('name', 'id');

            // Final grades for all students
            $finalGrades = DB::table('grades_final')
                ->whereIn('student_number', $allStudentNumbers)
                ->whereIn('semester_id', $semesterIds)
                ->select('student_number', 'class_code', 'semester_id', 'final_grade', 'remarks')
                ->get()
                ->groupBy('student_number');

            // Existing graduation records
            $existingRecords = DB::table('graduation_records')
                ->where('school_year_id', $schoolYearId)
                ->whereIn('student_number', $allStudentNumbers)
                ->select('student_number', 'status', 'is_finalized')
                ->get()
                ->keyBy('student_number');

            // ── BUILD RESULT ──────────────────────────────────────────────────
            $result   = [];
            $eligible = 0;
            $issues   = 0;
            $missing  = 0;

            foreach ($allStudents as $student) {
                $sn          = $student->student_number;
                $myGrades    = $finalGrades[$sn] ?? collect();
                $gradeByCode = $myGrades->keyBy('class_code');

                if ($student->student_type === 'regular') {
                    $myClasses = collect($regularClassCodes[$sn] ?? []);
                } else {
                    $myClasses = ($irregularEnrollments[$sn] ?? collect())->map(function ($e) {
                        return ['class_code' => $e->class_code, 'semester_id' => $e->semester_id];
                    });
                }

                $totalSubjects = $myClasses->count();
                $passedCount   = 0;
                $failedCount   = 0;
                $incCount      = 0;
                $missingCount  = 0;
                $classDetails  = [];

                foreach ($myClasses as $class) {
                    $classCode   = is_array($class) ? $class['class_code'] : $class->class_code;
                    $semesterId  = is_array($class) ? $class['semester_id'] : $class->semester_id;
                    $gradeRecord = $gradeByCode[$classCode] ?? null;

                    if (!$gradeRecord) {
                        $missingCount++;
                        $classDetails[] = [
                            'class_code'   => $classCode,
                            'semester_id'  => $semesterId,
                            'final_grade'  => null,
                            'remarks'      => null,
                            'grade_status' => 'missing',
                        ];
                        continue;
                    }

                    $remarks = $gradeRecord->remarks;

                    if ($remarks === 'PASSED') {
                        $passedCount++;
                    } elseif (in_array($remarks, ['FAILED', 'DRP', 'W'])) {
                        $failedCount++;
                    } elseif ($remarks === 'INC') {
                        $incCount++;
                    }

                    $classDetails[] = [
                        'class_code'   => $classCode,
                        'semester_id'  => $semesterId,
                        'final_grade'  => $gradeRecord->final_grade,
                        'remarks'      => $remarks,
                        'grade_status' => $remarks === 'PASSED' ? 'passed' :
                                          ($remarks === 'INC'    ? 'inc'    : 'failed'),
                    ];
                }

                if ($missingCount > 0) {
                    $eligibilityStatus = 'missing';
                    $missing++;
                } elseif ($failedCount > 0 || $incCount > 0) {
                    $eligibilityStatus = 'issues';
                    $issues++;
                } else {
                    $eligibilityStatus = 'eligible';
                    $eligible++;
                }

                $existingRecord = $existingRecords[$sn] ?? null;

                $result[] = [
                    'student_number'     => $sn,
                    'full_name'          => trim(
                        $student->last_name . ', ' .
                        $student->first_name .
                        ($student->middle_name ? ' ' . substr($student->middle_name, 0, 1) . '.' : '')
                    ),
                    'first_name'         => $student->first_name,
                    'last_name'          => $student->last_name,
                    'gender'             => $student->gender,
                    'student_type'       => $student->student_type,
                    'section_id'         => $student->section_id,
                    'section_name'       => $student->section_id ? ($sectionNames[$student->section_id] ?? null) : null,
                    'total_subjects'     => $totalSubjects,
                    'passed_count'       => $passedCount,
                    'failed_count'       => $failedCount,
                    'inc_count'          => $incCount,
                    'missing_count'      => $missingCount,
                    'eligibility_status' => $eligibilityStatus,
                    'class_details'      => $classDetails,
                    'graduation_status'  => $existingRecord ? $existingRecord->status : null,
                    'is_finalized'       => $existingRecord ? (bool) $existingRecord->is_finalized : false,
                ];
            }

            // Sort by last name then first name
            usort($result, function ($a, $b) {
                $last = strcmp($a['last_name'], $b['last_name']);
                return $last !== 0 ? $last : strcmp($a['first_name'], $b['first_name']);
            });

            return response()->json([
                'success' => true,
                'data'    => $result,
                'summary' => [
                    'total'    => count($result),
                    'eligible' => $eligible,
                    'issues'   => $issues,
                    'missing'  => $missing,
                ],
                'school_year'  => $schoolYear,
                'is_finalized' => $existingRecords->where('is_finalized', true)->isNotEmpty(),
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get graduation students', [
                'school_year_id' => $schoolYearId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load graduation data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save/update graduation status for a single student (before finalization).
     */
    public function saveStudentRecord(Request $request, $schoolYearId)
    {
        $validated = $request->validate([
            'student_number' => 'required|string',
            'status'         => 'required|in:graduated,not_graduated',
        ]);

        try {
            $finalized = DB::table('graduation_records')
                ->where('school_year_id', $schoolYearId)
                ->where('is_finalized', true)
                ->exists();

            if ($finalized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Graduation list is already finalized. Changes are not allowed.',
                ], 422);
            }

            $student = DB::table('students')
                ->where('student_number', $validated['student_number'])
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
            }

            $strandId = null;
            $levelId  = null;

            if ($student->student_type === 'regular' && $student->section_id) {
                $section  = DB::table('sections')->where('id', $student->section_id)->first();
                $strandId = $section->strand_id ?? null;
                $levelId  = $section->level_id ?? null;
            } else {
                $semesterIds = DB::table('semesters')
                    ->where('school_year_id', $schoolYearId)
                    ->pluck('id')
                    ->toArray();

                $levelMatrix = DB::table('student_level_matrix')
                    ->where('student_number', $validated['student_number'])
                    ->whereIn('semester_id', $semesterIds)
                    ->orderByDesc('semester_id')
                    ->first();

                $strandId = $levelMatrix->strand_id ?? null;
                $levelId  = $levelMatrix->level_id ?? null;
            }

            DB::table('graduation_records')->updateOrInsert(
                [
                    'school_year_id' => $schoolYearId,
                    'student_number' => $validated['student_number'],
                ],
                [
                    'status'       => $validated['status'],
                    'remarks'      => null,
                    'section_id'   => $student->section_id,
                    'strand_id'    => $strandId,
                    'level_id'     => $levelId,
                    'is_finalized' => false,
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );

            $this->logAudit(
                'updated',
                'graduation_records',
                $validated['student_number'],
                "Set graduation status to '{$validated['status']}' for student {$validated['student_number']} in SY {$schoolYearId}",
                null,
                $validated
            );

            return response()->json(['success' => true, 'message' => 'Record saved.']);

        } catch (Exception $e) {
            \Log::error('Failed to save graduation record', [
                'school_year_id' => $schoolYearId,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save record: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize the entire graduation list — locks all records.
     */
    public function finalize(Request $request, $schoolYearId)
    {
        try {
            $schoolYear = DB::table('school_years')->where('id', $schoolYearId)->first();

            if (!$schoolYear) {
                return response()->json(['success' => false, 'message' => 'School year not found.'], 404);
            }

            if (!$this->allSemestersCompleted($schoolYearId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All semesters must be completed before finalizing.',
                ], 422);
            }

            $alreadyFinalized = DB::table('graduation_records')
                ->where('school_year_id', $schoolYearId)
                ->where('is_finalized', true)
                ->exists();

            if ($alreadyFinalized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Graduation list is already finalized.',
                ], 422);
            }

            $recordCount = DB::table('graduation_records')
                ->where('school_year_id', $schoolYearId)
                ->count();

            if ($recordCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No graduation records found. Please review students before finalizing.',
                ], 422);
            }

            DB::beginTransaction();

            $adminId = auth()->id() ?? session('admin_id');

            DB::table('graduation_records')
                ->where('school_year_id', $schoolYearId)
                ->update([
                    'is_finalized' => true,
                    'finalized_by' => $adminId,
                    'finalized_at' => now(),
                    'updated_at'   => now(),
                ]);

            $this->logAudit(
                'finalized',
                'graduation_records',
                (string) $schoolYearId,
                "Finalized graduation list for school year {$schoolYear->code} ({$recordCount} records)",
                null,
                ['school_year_id' => $schoolYearId, 'record_count' => $recordCount]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Graduation list finalized successfully. {$recordCount} records locked.",
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to finalize graduation', [
                'school_year_id' => $schoolYearId,
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize: ' . $e->getMessage(),
            ], 500);
        }
    }
}
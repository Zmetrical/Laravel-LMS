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

            // Optional: Strict check for semester completion
            // You can comment this out if you want to view data mid-year
            if (!$this->allSemestersCompleted($schoolYearId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All semesters must be completed before viewing graduation data.'
                ], 422);
            }

            $grade12LevelId = $this->getGrade12LevelId();
            if (!$grade12LevelId) {
                return response()->json(['success' => false, 'message' => 'Grade 12 level configuration missing.'], 422);
            }

            // Get Current Semester IDs for this School Year
            $currentSemesterIds = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->pluck('id')
                ->toArray();

            // ── 1. IDENTIFY TARGET STUDENTS ───────────────────────────────────

            // A. Regulars: Students currently in a Grade 12 Section
            $grade12SectionIds = DB::table('sections')
                ->where('level_id', $grade12LevelId)
                ->where('status', 1)
                ->pluck('id')
                ->toArray();

            $regularSNs = collect();
            if (!empty($grade12SectionIds)) {
                $regularSNs = DB::table('students')
                    ->whereIn('section_id', $grade12SectionIds)
                    ->where('student_type', 'regular')
                    ->pluck('student_number');
            }

            // B. Irregulars: Students in Grade 12 Level Matrix
            $irregularSNs = DB::table('student_level_matrix')
                ->where('level_id', $grade12LevelId)
                ->whereIn('semester_id', $currentSemesterIds)
                ->distinct()
                ->pluck('student_number');

            // C. Merge to get unique list of Student Numbers
            $allStudentNumbers = $regularSNs->merge($irregularSNs)->unique()->values()->toArray();

            if (empty($allStudentNumbers)) {
                return response()->json([
                    'success' => true, 'data' => [],
                    'summary' => ['total' => 0, 'eligible' => 0, 'issues' => 0, 'missing' => 0],
                    'school_year' => $schoolYear, 'note' => 'No Grade 12 students found.',
                ]);
            }

            // Fetch Student Basic Info
            $allStudents = DB::table('students')
                ->whereIn('student_number', $allStudentNumbers)
                ->select('student_number', 'first_name', 'middle_name', 'last_name', 'gender', 'section_id', 'student_type')
                ->get();

            // ── 2. PRELOAD DATA SOURCES (BATCH FETCHING) ──────────────────────

            // SOURCE A: Section History (CRITICAL FIX)
            // Get all sections these students have EVER joined (Grade 11, Grade 12, etc.)
            // using 'enrollment_status' column
            $enrollmentHistory = DB::table('student_semester_enrollment')
                ->whereIn('student_number', $allStudentNumbers)
                ->where('enrollment_status', 'enrolled') 
                ->select('student_number', 'section_id')
                ->get()
                ->groupBy('student_number');

            // Collect ALL unique section IDs involved (Current + History) to fetch their subjects
            $allPertinentSectionIds = $allStudents->pluck('section_id') // Current sections
                ->merge($enrollmentHistory->flatten()->pluck('section_id')) // History sections
                ->filter()
                ->unique()
                ->toArray();

            // SOURCE B: Subjects attached to Sections (The Matrix)
            // Map: SectionID => List of ClassCodes
            $sectionSubjectMap = DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->whereIn('scm.section_id', $allPertinentSectionIds)
                ->select('scm.section_id', 'scm.semester_id', 'c.class_code')
                ->get()
                ->groupBy('section_id');

            // SOURCE C: Individual Subject Enrollments (For Irregulars / Back subjects)
            $individualEnrollments = DB::table('student_class_matrix')
                ->whereIn('student_number', $allStudentNumbers)
                ->where('enrollment_status', '!=', 'dropped')
                ->select('student_number', 'class_code', 'semester_id')
                ->get()
                ->groupBy('student_number');

            // SOURCE D: Final Grades (Completed Subjects)
            $finalGrades = DB::table('grades_final')
                ->whereIn('student_number', $allStudentNumbers)
                ->select('student_number', 'class_code', 'semester_id', 'final_grade', 'remarks')
                ->get()
                ->groupBy('student_number');

            // SOURCE E: Metadata Maps (For Display)
            
            // 1. Section Names
            $sectionNames = DB::table('sections')
                ->whereIn('id', $allPertinentSectionIds)
                ->pluck('name', 'id');

            // 2. Class Names (Code => Name)
            // Note: We fetch all classes to ensure we have names for everything found
            $allClassesMap = DB::table('classes')->pluck('class_name', 'class_code');

            // 3. Semester Labels (ID => "2025-2026 First Semester")
            $semesterInfo = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->select('semesters.id', 'semesters.name as sem_name', 'school_years.code as sy_code')
                ->get()
                ->keyBy('id');

            // Existing Graduation Records
            $existingRecords = DB::table('graduation_records')
                ->where('school_year_id', $schoolYearId)
                ->whereIn('student_number', $allStudentNumbers)
                ->get()
                ->keyBy('student_number');

            // ── 3. PROCESS EACH STUDENT ───────────────────────────────────────
            $result   = [];
            $eligible = 0;
            $issues   = 0;
            $missing  = 0;

            foreach ($allStudents as $student) {
                $sn = $student->student_number;

                // 1. Determine ALL SECTIONS this student has been part of
                //    Start with Current Section
                $mySections = collect($student->section_id ? [$student->section_id] : []);
                
                //    Add Historical Sections (Grade 11, etc.)
                if (isset($enrollmentHistory[$sn])) {
                    $mySections = $mySections->merge($enrollmentHistory[$sn]->pluck('section_id'));
                }
                $mySections = $mySections->unique()->filter();

                // 2. Build Required Subject List from those Sections
                $sectionSubjects = collect();
                foreach ($mySections as $secId) {
                    if (isset($sectionSubjectMap[$secId])) {
                        $sectionSubjects = $sectionSubjects->merge($sectionSubjectMap[$secId]);
                    }
                }

                // 3. Get Individual Enrollments & Grades
                $myIndividual = $individualEnrollments[$sn] ?? collect();
                $myGrades     = $finalGrades[$sn] ?? collect();
                $gradeByCode  = $myGrades->keyBy('class_code');

                // 4. MERGE EVERYTHING into one Master Subject List
                $allSubjectCodes = $sectionSubjects->pluck('class_code')
                    ->merge($myIndividual->pluck('class_code'))
                    ->merge($myGrades->pluck('class_code'))
                    ->unique();

                $totalSubjects = 0;
                $passedCount   = 0;
                $failedCount   = 0;
                $incCount      = 0;
                $missingCount  = 0;
                $classDetails  = [];

                foreach ($allSubjectCodes as $classCode) {
                    $totalSubjects++;
                    
                    // Priority for Details: Grade > Individual > Section
                    $gradeRecord = $gradeByCode[$classCode] ?? null;

                    // Determine Semester ID
                    $semesterId = $gradeRecord->semester_id ?? null;
                    if (!$semesterId) {
                        $indiv = $myIndividual->firstWhere('class_code', $classCode);
                        $semesterId = $indiv->semester_id ?? null;
                    }
                    if (!$semesterId) {
                        $secSub = $sectionSubjects->firstWhere('class_code', $classCode);
                        $semesterId = $secSub->semester_id ?? null;
                    }

                    // Format Semester Label
                    $semLabel = '—';
                    if ($semesterId && isset($semesterInfo[$semesterId])) {
                        $s = $semesterInfo[$semesterId];
                        $semLabel = "{$s->sy_code} {$s->sem_name}";
                    }

                    // Get Class Name
                    $className = $allClassesMap[$classCode] ?? $classCode;

                    if (!$gradeRecord) {
                        $missingCount++;
                        $classDetails[] = [
                            'class_code'     => $classCode,
                            'class_name'     => $className,
                            'semester_id'    => $semesterId,
                            'semester_label' => $semLabel,
                            'final_grade'    => null,
                            'remarks'        => null,
                            'grade_status'   => 'missing',
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
                        'class_code'     => $classCode,
                        'class_name'     => $className,
                        'semester_id'    => $semesterId,
                        'semester_label' => $semLabel,
                        'final_grade'    => $gradeRecord->final_grade,
                        'remarks'        => $remarks,
                        'grade_status'   => $remarks === 'PASSED' ? 'passed' :
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

                $rec = $existingRecords[$sn] ?? null;

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
                    'graduation_status'  => $rec ? $rec->status : null,
                    'is_finalized'       => $rec ? (bool) $rec->is_finalized : false,
                ];
            }

            usort($result, function ($a, $b) {
                return strcmp($a['last_name'], $b['last_name']) ?: strcmp($a['first_name'], $b['first_name']);
            });

            return response()->json([
                'success' => true,
                'data'    => $result,
                'summary' => [
                    'total' => count($result), 'eligible' => $eligible, 
                    'issues' => $issues, 'missing' => $missing
                ],
                'school_year'  => $schoolYear,
                'is_finalized' => $existingRecords->where('is_finalized', true)->isNotEmpty(),
            ]);

        } catch (Exception $e) {
            \Log::error('Graduation Data Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
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
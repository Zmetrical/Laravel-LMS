<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Semester_Management extends MainController
{
    use AuditLogger;

    public function archivePage(Request $request)
    {
        $schoolYearId = $request->query('sy');
        
        if (!$schoolYearId) {
            return redirect()->route('admin.schoolyears.index')
                ->with('error', 'Please select a school year first');
        }

        $data = [
            'scripts' => ['class_management/semester_management.js'],
            'school_year_id' => $schoolYearId
        ];

        return view('admin.class_management.semester_management', $data);
    }

    public function verifyAdminAccess(Request $request)
    {
        $validated = $request->validate([
            'admin_password' => 'required|string',
        ]);

        try {
            $adminId = Auth::guard('admin')->id();
            
            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin session not found.'
                ], 401);
            }

            $admin = DB::table('admins')
                ->where('id', $adminId)
                ->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin account not found.'
                ], 404);
            }

            if (!Hash::check($request->admin_password, $admin->admin_password)) {
                $this->logAudit(
                    'failed_verification',
                    'archive_access',
                    null,
                    'Failed archive access verification attempt',
                    null,
                    ['admin_id' => $adminId]
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password.'
                ], 401);
            }

            session(['archive_verified' => true, 'archive_verified_at' => now()]);

            $this->logAudit(
                'verified',
                'archive_access',
                null,
                'Successfully verified archive access',
                null,
                ['admin_id' => $adminId]
            );

            return response()->json([
                'success' => true,
                'message' => 'Access granted.'
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to verify admin access', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed.'
            ], 500);
        }
    }

    public function getArchiveInfo($schoolYearId)
    {
        try {
            $schoolYear = DB::table('school_years')
                ->where('id', $schoolYearId)
                ->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            $semesters = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->orderBy('code')
                ->get();

            $semesterData = [];
            foreach ($semesters as $semester) {
                $enrolledCount = DB::table('student_semester_enrollment')
                    ->where('semester_id', $semester->id)
                    ->where('enrollment_status', 'enrolled')
                    ->count();

                $sectionsCount = DB::table('student_semester_enrollment')
                    ->where('semester_id', $semester->id)
                    ->whereNotNull('section_id')
                    ->distinct('section_id')
                    ->count();

                $gradesCount = DB::table('grades_final')
                    ->where('semester_id', $semester->id)
                    ->count();

                $quarterGradesCount = DB::table('quarter_grades as qg')
                    ->join('quarters as q', 'qg.quarter_id', '=', 'q.id')
                    ->where('q.semester_id', $semester->id)
                    ->count();

                $semesterData[] = [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'code' => $semester->code,
                    'status' => $semester->status,
                    'start_date' => $semester->start_date,
                    'end_date' => $semester->end_date,
                    'enrolled_students' => $enrolledCount,
                    'sections_count' => $sectionsCount,
                    'final_grades' => $gradesCount,
                    'quarter_grades' => $quarterGradesCount
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'school_year' => [
                        'id' => $schoolYear->id,
                        'code' => $schoolYear->code,
                        'year_start' => $schoolYear->year_start,
                        'year_end' => $schoolYear->year_end,
                        'status' => $schoolYear->status
                    ],
                    'semesters' => $semesterData
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get archive info', [
                'school_year_id' => $schoolYearId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load archive information.'
            ], 500);
        }
    }

    public function getSemesterDetails($semesterId)
    {
        try {
            $sections = DB::table('sections as s')
                ->join('student_semester_enrollment as sse', function($join) use ($semesterId) {
                    $join->on('s.id', '=', 'sse.section_id')
                         ->where('sse.semester_id', '=', $semesterId)
                         ->where('sse.enrollment_status', '=', 'enrolled');
                })
                ->join('levels as l', 's.level_id', '=', 'l.id')
                ->join('strands as st', 's.strand_id', '=', 'st.id')
                ->select(
                    's.id',
                    's.name as section_name',
                    's.code as section_code',
                    'l.name as level_name',
                    'st.code as strand_code',
                    DB::raw('COUNT(DISTINCT sse.student_number) as student_count')
                )
                ->groupBy('s.id', 's.name', 's.code', 'l.name', 'st.code')
                ->orderBy('l.name')
                ->orderBy('s.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'sections' => $sections
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get semester details', [
                'semester_id' => $semesterId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load semester details.'
            ], 500);
        }
    }

    public function getSectionStudents($semesterId, $sectionId)
    {
        try {
            $students = DB::table('students as s')
                ->join('student_semester_enrollment as sse', function($join) use ($semesterId, $sectionId) {
                    $join->on('s.student_number', '=', 'sse.student_number')
                         ->where('sse.semester_id', '=', $semesterId)
                         ->where('sse.section_id', '=', $sectionId)
                         ->where('sse.enrollment_status', '=', 'enrolled');
                })
                ->select(
                    's.student_number',
                    DB::raw("CONCAT(s.first_name, ' ', s.last_name) as full_name"),
                    's.gender'
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get section students', [
                'semester_id' => $semesterId,
                'section_id' => $sectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load students.'
            ], 500);
        }
    }

    public function completeSemester($id) 
    {
        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            if ($semester->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active semesters can be completed.'
                ], 422);
            }

            $schoolYear = DB::table('school_years')
                ->where('id', $semester->school_year_id)
                ->first();

            $adminId = Auth::guard('admin')->id();

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => $adminId,
                    'updated_at' => now()
                ]);

            $remainingActive = DB::table('semesters')
                ->where('school_year_id', $semester->school_year_id)
                ->whereIn('status', ['active', 'upcoming'])
                ->count();

            if ($remainingActive === 0) {
                DB::table('school_years')
                    ->where('id', $semester->school_year_id)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'completed_by' => $adminId,
                        'updated_at' => now()
                    ]);
            }

            $this->logAudit(
                'completed',
                'semesters',
                (string)$id,
                "Completed {$semester->name} for school year {$schoolYear->code}",
                ['status' => $semester->status],
                ['status' => 'completed', 'completed_by' => $adminId, 'completed_at' => now()]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester completed successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to complete semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete semester.'
            ], 500);
        }
    }

    public function getPreviousSemester($targetSemesterId)
    {
        try {
            $targetSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->where('s.id', $targetSemesterId)
                ->select(
                    's.id',
                    's.name',
                    's.code',
                    's.status',
                    's.school_year_id',
                    'sy.code as year_code',
                    'sy.year_start',
                    'sy.year_end'
                )
                ->first();

            if (!$targetSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target semester not found.'
                ], 404);
            }

            $sourceSemester = null;

            if ($targetSemester->code === '2nd') {
                $sourceSemester = DB::table('semesters as s')
                    ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                    ->where('s.school_year_id', $targetSemester->school_year_id)
                    ->where('s.code', '1st')
                    ->select(
                        's.id',
                        's.name',
                        's.code',
                        's.status',
                        'sy.code as year_code'
                    )
                    ->first();
            }
            
            if (!$sourceSemester) {
                $sourceSemester = DB::table('semesters as s')
                    ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                    ->where('sy.year_start', '<', $targetSemester->year_start)
                    ->where('s.code', '2nd')
                    ->select(
                        's.id',
                        's.name',
                        's.code',
                        's.status',
                        'sy.code as year_code'
                    )
                    ->orderBy('sy.year_start', 'desc')
                    ->first();
            }
            
            if (!$sourceSemester) {
                $sourceSemester = DB::table('semesters as s')
                    ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                    ->where(function($query) use ($targetSemester) {
                        $query->where('sy.year_start', '<', $targetSemester->year_start)
                              ->orWhere(function($q) use ($targetSemester) {
                                  $q->where('sy.year_start', '=', $targetSemester->year_start)
                                    ->where('s.code', '<', $targetSemester->code);
                              });
                    })
                    ->select(
                        's.id',
                        's.name',
                        's.code',
                        's.status',
                        'sy.code as year_code'
                    )
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('s.code', 'desc')
                    ->first();
            }
            
            if (!$sourceSemester) {
                $sourceSemester = DB::table('semesters as s')
                    ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                    ->where('s.id', $targetSemesterId)
                    ->select(
                        's.id',
                        's.name',
                        's.code',
                        's.status',
                        'sy.code as year_code'
                    )
                    ->first();
            }

            return response()->json([
                'success' => true,
                'source_semester' => $sourceSemester,
                'target_semester' => [
                    'id' => $targetSemester->id,
                    'name' => $targetSemester->name,
                    'code' => $targetSemester->code,
                    'status' => $targetSemester->status,
                    'year_code' => $targetSemester->year_code
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get previous semester', [
                'target_semester_id' => $targetSemesterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to determine source semester.'
            ], 500);
        }
    }

    public function searchSections(Request $request)
    {
        try {
            $search = $request->input('search', '');
            
            $sections = DB::table('sections as s')
                ->join('strands as st', 's.strand_id', '=', 'st.id')
                ->join('levels as l', 's.level_id', '=', 'l.id')
                ->where('s.status', 1)
                ->where(function($query) use ($search) {
                    if ($search) {
                        $query->where('s.name', 'like', "%{$search}%")
                              ->orWhere('s.code', 'like', "%{$search}%")
                              ->orWhere('st.code', 'like', "%{$search}%")
                              ->orWhere('l.name', 'like', "%{$search}%");
                    }
                })
                ->select(
                    's.id',
                    's.name',
                    's.code',
                    'st.code as strand_code',
                    'l.name as level_name'
                )
                ->orderBy('l.name')
                ->orderBy('s.name')
                ->limit(20)
                ->get();

            return response()->json($sections);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to search sections'], 500);
        }
    }

    public function loadStudentsFromSection(Request $request)
    {
        try {
            $sourceSectionId = $request->input('source_section_id');
            $sourceSemesterId = $request->input('source_semester_id');
            $targetSemesterId = $request->input('target_semester_id');

            $students = DB::table('students as s')
                ->join('student_semester_enrollment as sse', 's.student_number', '=', 'sse.student_number')
                ->where('sse.section_id', $sourceSectionId)
                ->where('sse.semester_id', $sourceSemesterId)
                ->where('sse.enrollment_status', 'enrolled')
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.gender',
                    's.student_type'
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            if ($targetSemesterId) {
                $alreadyEnrolled = DB::table('student_semester_enrollment')
                    ->where('semester_id', $targetSemesterId)
                    ->where('enrollment_status', 'enrolled')
                    ->pluck('student_number')
                    ->toArray();

                $students = $students->map(function($student) use ($alreadyEnrolled) {
                    $student->already_enrolled = in_array($student->student_number, $alreadyEnrolled);
                    return $student;
                });
            } else {
                $students = $students->map(function($student) {
                    $student->already_enrolled = false;
                    return $student;
                });
            }

            return response()->json([
                'success' => true,
                'students' => $students,
                'count' => $students->count()
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to load students', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load students.'
            ], 500);
        }
    }

    public function getSectionDetails(Request $request)
    {
        try {
            $sectionId = $request->input('section_id');
            
            $section = DB::table('sections as s')
                ->join('strands as st', 's.strand_id', '=', 'st.id')
                ->join('levels as l', 's.level_id', '=', 'l.id')
                ->where('s.id', $sectionId)
                ->select(
                    's.id',
                    's.strand_id',
                    's.level_id',
                    'st.name as strand_name',
                    'st.code as strand_code',
                    'l.name as level_name'
                )
                ->first();

            return response()->json([
                'success' => true,
                'section' => $section
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get section details.'
            ], 500);
        }
    }

    public function getTargetSections(Request $request)
    {
        try {
            $strandId = $request->input('strand_id');
            $currentLevelId = $request->input('current_level_id');
            $targetSemesterId = $request->input('semester_id');
            $sourceSemesterId = $request->input('source_semester_id');
            $enrollmentAction = $request->input('enrollment_action', 'promote'); // promote, retain, transfer

            $targetSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->where('s.id', $targetSemesterId)
                ->select('s.*', 'sy.year_start', 'sy.year_end', 'sy.code as sy_code')
                ->first();
            
            if (!$targetSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target semester not found.'
                ], 404);
            }

            // Determine if this is a promotion scenario
            $isPromotionScenario = false;
            $targetLevelId = $currentLevelId;
            $targetStrandId = $strandId;

            if ($sourceSemesterId) {
                $sourceSemester = DB::table('semesters as s')
                    ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                    ->where('s.id', $sourceSemesterId)
                    ->select('s.*', 'sy.year_start', 'sy.year_end')
                    ->first();

                if ($sourceSemester) {
                    $lastSemesterOfYear = DB::table('semesters')
                        ->where('school_year_id', $sourceSemester->school_year_id)
                        ->orderBy('code', 'desc')
                        ->first();

                    $isLastSemester = $lastSemesterOfYear->id == $sourceSemesterId;
                    $isDifferentYear = $sourceSemester->school_year_id != $targetSemester->school_year_id;
                    
                    $isPromotionScenario = $isLastSemester && $isDifferentYear;
                }
            }

            // Apply action based on user choice
            if ($enrollmentAction === 'promote' && $isPromotionScenario) {
                $nextLevel = DB::table('levels')
                    ->where('id', '>', $currentLevelId)
                    ->orderBy('id')
                    ->first();
                
                if ($nextLevel) {
                    $targetLevelId = $nextLevel->id;
                }
            } elseif ($enrollmentAction === 'transfer') {
                // Transfer will show all strands - handled by frontend
                $targetStrandId = $request->input('target_strand_id', $strandId);
                $targetLevelId = $request->input('target_level_id', $currentLevelId);
            }
            // else retain = keep same level and strand

            // Get sections
            $sectionsQuery = DB::table('sections as s')
                ->join('levels as l', 's.level_id', '=', 'l.id')
                ->join('strands as st', 's.strand_id', '=', 'st.id')
                ->where('s.status', 1)
                ->where('s.level_id', $targetLevelId);

            if ($enrollmentAction !== 'transfer') {
                $sectionsQuery->where('s.strand_id', $targetStrandId);
            } else {
                $sectionsQuery->where('s.strand_id', $targetStrandId);
            }

            $sections = $sectionsQuery
                ->select(
                    's.id',
                    's.name',
                    's.code',
                    's.capacity',
                    'l.name as level_name',
                    'st.code as strand_code',
                    'st.name as strand_name'
                )
                ->orderBy('s.name')
                ->get();

            foreach ($sections as $section) {
                $enrolledCount = DB::table('student_semester_enrollment')
                    ->where('section_id', $section->id)
                    ->where('semester_id', $targetSemesterId)
                    ->where('enrollment_status', 'enrolled')
                    ->count();

                $section->enrolled_count = $enrolledCount;
            }

            // Get all available strands and levels for transfer option
            $allStrands = DB::table('strands')
                ->where('status', 1)
                ->select('id', 'code', 'name')
                ->orderBy('name')
                ->get();

            $allLevels = DB::table('levels')
                ->select('id', 'name')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'target_level_id' => $targetLevelId,
                'target_strand_id' => $targetStrandId,
                'is_promotion_scenario' => $isPromotionScenario,
                'all_strands' => $allStrands,
                'all_levels' => $allLevels,
                'target_semester_info' => [
                    'id' => $targetSemester->id,
                    'name' => $targetSemester->name,
                    'status' => $targetSemester->status,
                    'school_year' => $targetSemester->sy_code
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get target sections', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load target sections.'
            ], 500);
        }
    }

    public function enrollStudents(Request $request)
    {
        try {
            $semesterId = $request->input('semester_id');
            $sectionId = $request->input('section_id');
            $students = $request->input('students', []);

            if (empty($students)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students to enroll.'
                ], 422);
            }

            $studentNumbers = array_column($students, 'student_number');
            $existingEnrollments = DB::table('student_semester_enrollment')
                ->where('semester_id', $semesterId)
                ->where('enrollment_status', 'enrolled')
                ->whereIn('student_number', $studentNumbers)
                ->pluck('student_number')
                ->toArray();

            $studentsToEnroll = array_filter($students, function($student) use ($existingEnrollments) {
                return !in_array($student['student_number'], $existingEnrollments);
            });

            if (empty($studentsToEnroll)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected students are already enrolled in this semester.',
                    'already_enrolled' => $existingEnrollments
                ], 422);
            }

            DB::beginTransaction();

            $enrolledCount = 0;
            $skippedCount = count($existingEnrollments);
            $errors = [];

            foreach ($studentsToEnroll as $studentData) {
                try {
                    DB::table('students')
                        ->where('student_number', $studentData['student_number'])
                        ->update([
                            'section_id' => $studentData['new_section_id'],
                            'student_type' => $studentData['student_type'],
                            'updated_at' => now()
                        ]);

                    $inserted = DB::table('student_semester_enrollment')->insert([
                        'student_number' => $studentData['student_number'],
                        'semester_id' => $semesterId,
                        'section_id' => $studentData['new_section_id'],
                        'enrollment_status' => 'enrolled',
                        'enrollment_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    if ($inserted) {
                        $enrolledCount++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to enroll {$studentData['student_number']}: " . $e->getMessage();
                }
            }

            if ($enrolledCount > 0) {
                $this->logAudit(
                    'enrolled',
                    'student_enrollment',
                    (string)$semesterId,
                    "Bulk enrolled {$enrolledCount} student(s) to semester" . 
                    ($skippedCount > 0 ? " ({$skippedCount} already enrolled, skipped)" : ""),
                    null,
                    [
                        'semester_id' => $semesterId,
                        'section_id' => $sectionId,
                        'enrolled' => $enrolledCount,
                        'skipped' => $skippedCount,
                        'skipped_students' => $existingEnrollments
                    ]
                );
            }

            DB::commit();

            $message = "{$enrolledCount} student(s) enrolled successfully";
            if ($skippedCount > 0) {
                $message .= ". {$skippedCount} already enrolled (skipped)";
            }

            return response()->json([
                'success' => true,
                'enrolled' => $enrolledCount,
                'skipped' => $skippedCount,
                'skipped_students' => $existingEnrollments,
                'errors' => $errors,
                'message' => $message
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to enroll students', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll students.'
            ], 500);
        }
    }
}
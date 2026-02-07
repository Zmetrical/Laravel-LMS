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

    // Get current school year
    $currentSchoolYear = DB::table('school_years')->where('id', $schoolYearId)->first();
    
    // Get available semesters for SOURCE (completed and active semesters)
    $sourceSemesters = DB::table('semesters as s')
        ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
        ->select(
            's.id',
            's.name as semester_name',
            'sy.code as year_code',
            's.status',
            's.code as semester_code',
            's.school_year_id'
        )
        ->where(function($query) use ($currentSchoolYear) {
            // Current school year's completed/active semesters
            $query->where(function($q) use ($currentSchoolYear) {
                $q->where('s.school_year_id', $currentSchoolYear->id)
                  ->whereIn('s.status', ['active', 'completed']);
            });
            
            // Previous school year's 2nd semester
            $query->orWhere(function($q) use ($currentSchoolYear) {
                $q->where('sy.year_start', $currentSchoolYear->year_start - 1)
                  ->where('s.code', '2nd');
            });
        })
        ->orderBy('sy.year_start', 'desc')
        ->orderBy('s.code', 'desc')
        ->get();

    // Get target semester (upcoming or next active)
    $targetSemester = DB::table('semesters as s')
        ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
        ->select(
            's.id',
            's.name as semester_name',
            'sy.code as year_code',
            's.status',
            's.code as semester_code'
        )
        ->where('s.school_year_id', $schoolYearId)
        ->where(function($query) {
            $query->where('s.status', 'upcoming')
                  ->orWhere('s.status', 'active');
        })
        ->orderByRaw("FIELD(s.status, 'upcoming', 'active')")
        ->orderBy('s.code')
        ->first();

    $data = [
        'scripts' => ['class_management/semester_management.js'],
        'school_year_id' => $schoolYearId,
        'source_semesters' => $sourceSemesters,
        'target_semester' => $targetSemester
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

    public function archiveSemester($id)
    {
        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            if ($semester->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot archive active semester.'
                ], 422);
            }

            $schoolYear = DB::table('school_years')
                ->where('id', $semester->school_year_id)
                ->first();

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            // Check if all semesters are completed
            $remainingActive = DB::table('semesters')
                ->where('school_year_id', $semester->school_year_id)
                ->where('status', '!=', 'completed')
                ->count();

            if ($remainingActive === 0) {
                DB::table('school_years')
                    ->where('id', $semester->school_year_id)
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now()
                    ]);
            }

            $this->logAudit(
                'archived',
                'semesters',
                (string)$id,
                "Archived {$semester->name} for school year {$schoolYear->code}",
                ['status' => $semester->status],
                ['status' => 'completed']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester archived successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to archive semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive semester.'
            ], 500);
        }
    }

    // Quick Enrollment Methods
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

            if (!$sourceSemesterId) {
                // Load all students from section
                $students = DB::table('students as s')
                    ->where('s.section_id', $sourceSectionId)
                    ->where('s.student_type', 'regular')
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
            } else {
                // Load students enrolled in specific semester
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

        \Log::info('Getting target sections', [
            'strand_id' => $strandId,
            'current_level_id' => $currentLevelId,
            'target_semester_id' => $targetSemesterId,
            'source_semester_id' => $sourceSemesterId
        ]);

        // Get target semester details
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

        \Log::info('Target semester', [
            'semester' => $targetSemester,
            'status' => $targetSemester->status
        ]);

        // Determine target level
        $isPromotion = false;
        $targetLevelId = $currentLevelId;

        if ($sourceSemesterId) {
            $sourceSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->where('s.id', $sourceSemesterId)
                ->select('s.*', 'sy.year_start', 'sy.year_end')
                ->first();

            if ($sourceSemester) {
                // Check if source was the LAST semester of its school year
                $lastSemesterOfYear = DB::table('semesters')
                    ->where('school_year_id', $sourceSemester->school_year_id)
                    ->orderBy('code', 'desc')
                    ->first();

                $isLastSemester = $lastSemesterOfYear->id == $sourceSemesterId;

                // If last semester of school year AND moving to different school year
                if ($isLastSemester && $sourceSemester->school_year_id != $targetSemester->school_year_id) {
                    $isPromotion = true;
                    
                    $nextLevel = DB::table('levels')
                        ->where('id', '>', $currentLevelId)
                        ->orderBy('id')
                        ->first();
                    
                    if ($nextLevel) {
                        $targetLevelId = $nextLevel->id;
                    }
                }

                \Log::info('Promotion check', [
                    'is_last_semester' => $isLastSemester,
                    'is_promotion' => $isPromotion,
                    'target_level_id' => $targetLevelId
                ]);
            }
        }

        // Get sections for target level and strand
        $sections = DB::table('sections as s')
            ->join('levels as l', 's.level_id', '=', 'l.id')
            ->where('s.strand_id', $strandId)
            ->where('s.level_id', $targetLevelId)
            ->where('s.status', 1)
            ->select(
                's.id',
                's.name',
                's.code',
                's.capacity',
                'l.name as level_name'
            )
            ->orderBy('s.name')
            ->get();

        // Get current enrollment in target semester
        foreach ($sections as $section) {
            $enrolledCount = DB::table('student_semester_enrollment')
                ->where('section_id', $section->id)
                ->where('semester_id', $targetSemesterId)
                ->where('enrollment_status', 'enrolled')
                ->count();

            $section->enrolled_count = $enrolledCount;

            \Log::info('Section enrollment', [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'target_semester_id' => $targetSemesterId,
                'enrolled_count' => $enrolledCount
            ]);

            // DEBUG: Show who's enrolled
            if ($enrolledCount > 0) {
                $enrolledStudents = DB::table('student_semester_enrollment')
                    ->where('section_id', $section->id)
                    ->where('semester_id', $targetSemesterId)
                    ->where('enrollment_status', 'enrolled')
                    ->get(['student_number', 'enrollment_date', 'created_at']);

                \Log::warning('Students already enrolled in NEW semester!', [
                    'section' => $section->name,
                    'semester_id' => $targetSemesterId,
                    'students' => $enrolledStudents
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'sections' => $sections,
            'target_level_id' => $targetLevelId,
            'is_promotion' => $isPromotion,
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

            DB::beginTransaction();

            $enrolledCount = 0;
            $errors = [];

            foreach ($students as $studentData) {
                try {
                    // Update student record
                    DB::table('students')
                        ->where('student_number', $studentData['student_number'])
                        ->update([
                            'section_id' => $studentData['new_section_id'],
                            'student_type' => $studentData['student_type'],
                            'updated_at' => now()
                        ]);

                    // Create or update semester enrollment
                    DB::table('student_semester_enrollment')->updateOrInsert(
                        [
                            'student_number' => $studentData['student_number'],
                            'semester_id' => $semesterId
                        ],
                        [
                            'section_id' => $studentData['new_section_id'],
                            'enrollment_status' => 'enrolled',
                            'enrollment_date' => now(),
                            'updated_at' => now(),
                            'created_at' => DB::raw('COALESCE(created_at, NOW())')
                        ]
                    );

                    $enrolledCount++;
                } catch (Exception $e) {
                    $errors[] = "Failed to enroll {$studentData['student_number']}: " . $e->getMessage();
                }
            }

            if ($enrolledCount > 0) {
                $this->logAudit(
                    'enrolled',
                    'student_enrollment',
                    (string)$semesterId,
                    "Bulk enrolled {$enrolledCount} student(s) to semester",
                    null,
                    [
                        'semester_id' => $semesterId,
                        'section_id' => $sectionId,
                        'count' => $enrolledCount
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'enrolled' => $enrolledCount,
                'errors' => $errors
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
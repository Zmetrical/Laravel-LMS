<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Archive_Management extends MainController
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

            // Get semesters for this school year
            $semesters = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->orderBy('code')
                ->get();

            $semesterData = [];
            foreach ($semesters as $semester) {
                // Count enrolled students
                $enrolledCount = DB::table('student_semester_enrollment')
                    ->where('semester_id', $semester->id)
                    ->where('enrollment_status', 'enrolled')
                    ->count();

                // Count sections with students
                $sectionsCount = DB::table('student_semester_enrollment')
                    ->where('semester_id', $semester->id)
                    ->whereNotNull('section_id')
                    ->distinct('section_id')
                    ->count();

                // Count final grades
                $gradesCount = DB::table('grades_final')
                    ->where('semester_id', $semester->id)
                    ->count();

                // Count quarter grades
                $quarterGradesCount = DB::table('quarter_grades as qg')
                    ->join('quarters as q', 'qg.quarter_id', '=', 'q.id')
                    ->where('q.semester_id', $semester->id)
                    ->count();

                // Count teachers assigned
                $teachersCount = DB::table('teacher_class_matrix as tcm')
                    ->where('tcm.semester_id', $semester->id)
                    ->distinct('tcm.teacher_id')
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
                    'quarter_grades' => $quarterGradesCount,
                    'teachers_count' => $teachersCount
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
            // Get sections with student counts
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

            // Get teachers with their assigned classes
            $teachers = DB::table('teachers as t')
                ->join('teacher_class_matrix as tcm', 't.id', '=', 'tcm.teacher_id')
                ->join('classes as c', 'tcm.class_id', '=', 'c.id')
                ->where('tcm.semester_id', $semesterId)
                ->where('t.status', 1)
                ->select(
                    't.id',
                    DB::raw("CONCAT(t.first_name, ' ', t.last_name) as teacher_name"),
                    'c.class_code',
                    'c.class_name'
                )
                ->orderBy('teacher_name')
                ->orderBy('c.class_name')
                ->get();

            // Group teachers by teacher_id
            $groupedTeachers = [];
            foreach ($teachers as $teacher) {
                if (!isset($groupedTeachers[$teacher->id])) {
                    $groupedTeachers[$teacher->id] = [
                        'id' => $teacher->id,
                        'name' => $teacher->teacher_name,
                        'classes' => []
                    ];
                }
                $groupedTeachers[$teacher->id]['classes'][] = [
                    'class_code' => $teacher->class_code,
                    'class_name' => $teacher->class_name
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'sections' => $sections,
                    'teachers' => array_values($groupedTeachers)
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

    public function archiveSchoolYear($id)
    {
        try {
            $schoolYear = DB::table('school_years')->where('id', $id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            if ($schoolYear->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot archive active school year. Please activate a new school year first.'
                ], 422);
            }

            DB::beginTransaction();

            // Update school year status
            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            // Update all semesters to completed
            DB::table('semesters')
                ->where('school_year_id', $id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            $this->logAudit(
                'archived',
                'school_years',
                (string)$id,
                "Archived school year '{$schoolYear->code}' and all its semesters",
                ['status' => $schoolYear->status],
                ['status' => 'completed']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year and all semesters archived successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to archive school year', [
                'school_year_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive school year.'
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
}
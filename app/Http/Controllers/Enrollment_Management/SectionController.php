<?php

namespace App\Http\Controllers\Enrollment_Management;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use App\Models\Enroll_Management\Section;
use App\Models\Enroll_Management\Classes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SectionController extends MainController
{
    /**
     * Display section enrollment page
     */
    public function index()
    {
        $activeSemester = $this->getActiveSemester();
        
        $data = [
            'scripts' => ['enroll_management/section_enroll_class.js'],
            'activeSemester' => $activeSemester,
            'activeSemesterDisplay' => $activeSemester 
                ? "S.Y. {$activeSemester->year_start}-{$activeSemester->year_end} | {$activeSemester->semester_name}"
                : 'No Active Semester'
        ];
        
        return view('admin.enroll_management.section_enroll_class', $data);
    }

    /**
     * Get active semester
     */
    private function getActiveSemester()
    {
        return DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->first();
    }

    /**
     * Get sections list with filters and adviser information
     */
    public function getSectionsList()
    {
        try {
            // Get active semester
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            $sections = DB::table('sections as sec')
                ->join('levels as l', 'sec.level_id', '=', 'l.id')
                ->join('strands as st', 'sec.strand_id', '=', 'st.id')
                ->leftJoin('section_adviser_matrix as sam', function($join) use ($activeSemester) {
                    $join->on('sam.section_id', '=', 'sec.id')
                         ->where('sam.semester_id', '=', $activeSemester->id);
                })
                ->leftJoin('teachers as t', 'sam.teacher_id', '=', 't.id')
                ->where('sec.status', 1)
                ->select(
                    'sec.id',
                    'sec.code',
                    'sec.name',
                    'sec.capacity',
                    'sec.level_id',
                    'sec.strand_id',
                    'l.name as level_name',
                    'st.code as strand_code',
                    'st.name as strand_name',
                    DB::raw("TRIM(CONCAT_WS(' ', t.first_name, t.middle_name, t.last_name)) as adviser_name"),
                    // Count classes for current semester only
                    DB::raw("(SELECT COUNT(*) FROM section_class_matrix 
                             WHERE section_id = sec.id 
                             AND semester_id = {$activeSemester->id}) as class_count")
                )
                ->orderBy('l.name')
                ->orderBy('st.code')
                ->orderBy('sec.name')
                ->get();

            $levels = DB::table('levels')
                ->orderBy('name')
                ->get();

            $strands = DB::table('strands')
                ->where('status', 1)
                ->orderBy('code')
                ->get();

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'levels' => $levels,
                'strands' => $strands,
                'semester_id' => $activeSemester->id
            ]);

        } catch (\Exception $e) {
            Log::error('Sections list error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load sections'
            ], 500);
        }
    }

    /**
     * Get section classes and students
     */
    public function getSectionDetails($id)
    {
        try {
            // Get active semester
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            $section = DB::table('sections')->find($id);
            
            if (!$section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found'
                ], 404);
            }

            // Get enrolled classes for this section in current semester
            $classes = DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('teacher_class_matrix as tcm', function($join) use ($activeSemester) {
                    $join->on('tcm.class_id', '=', 'c.id')
                         ->where('tcm.semester_id', '=', $activeSemester->id);
                })
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('scm.section_id', $id)
                ->where('scm.semester_id', $activeSemester->id)
                ->select(
                    'c.id',
                    'c.class_name',
                    DB::raw("GROUP_CONCAT(CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ') as teachers")
                )
                ->groupBy('c.id', 'c.class_name')
                ->get();

            // Get students enrolled in this section for current semester ONLY
            $students = DB::table('students as s')
                ->join('student_semester_enrollment as sse', 's.student_number', '=', 'sse.student_number')
                ->where('sse.section_id', $id)
                ->where('sse.semester_id', $activeSemester->id)
                ->where('sse.enrollment_status', 'enrolled')
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.gender',
                    's.student_type'
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            return response()->json([
                'success' => true,
                'classes' => $classes,
                'students' => $students,
                'semester_id' => $activeSemester->id
            ]);

        } catch (\Exception $e) {
            Log::error('Section details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load section details'
            ], 500);
        }
    }

    /**
     * Get available classes not enrolled in section
     */
    public function getAvailableClasses($sectionId)
    {
        try {
            $activeSemester = $this->getActiveSemester();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            // Get enrolled class IDs for this section in active semester
            $enrolledClassIds = DB::table('section_class_matrix')
                ->where('section_id', $sectionId)
                ->where('semester_id', $activeSemester->semester_id)
                ->pluck('class_id')
                ->toArray();

            // Get available classes
            $query = DB::table('classes as c')
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw("GROUP_CONCAT(DISTINCT CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ') as teacher_names")
                )
                ->groupBy('c.id', 'c.class_code', 'c.class_name');

            if (!empty($enrolledClassIds)) {
                $query->whereNotIn('c.id', $enrolledClassIds);
            }

            $classes = $query->orderBy('c.class_name')->get();

            $formattedClasses = $classes->map(function($class) {
                return [
                    'id' => $class->id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'teachers' => $class->teacher_names ?: 'No teacher assigned'
                ];
            });

            return response()->json([
                'success' => true,
                'classes' => $formattedClasses
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enroll multiple classes to section
     */
    public function enrollClass(Request $request, $id)
    {
        $request->validate([
            'class_ids' => 'required|array|min:1|max:10',
            'class_ids.*' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            $enrolledCount = 0;
            $skippedCount = 0;

            DB::beginTransaction();

            foreach ($request->class_ids as $classId) {
                // Check if already enrolled
                $exists = DB::table('section_class_matrix')
                    ->where('section_id', $id)
                    ->where('class_id', $classId)
                    ->where('semester_id', $request->semester_id)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                // Insert enrollment
                DB::table('section_class_matrix')->insert([
                    'section_id' => $id,
                    'class_id' => $classId,
                    'semester_id' => $request->semester_id
                ]);

                $enrolledCount++;
            }

            DB::commit();

            $message = '';
            if ($enrolledCount > 0) {
                $message = "Successfully enrolled {$enrolledCount} " . 
                          ($enrolledCount === 1 ? 'class' : 'classes');
            }
            if ($skippedCount > 0) {
                $message .= ($message ? '. ' : '') . 
                           "{$skippedCount} " . 
                           ($skippedCount === 1 ? 'class was' : 'classes were') . 
                           " already enrolled";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'enrolled_count' => $enrolledCount,
                'skipped_count' => $skippedCount
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove class from section
     */
    public function removeClass($sectionId, $classId)
    {
        try {
            $activeSemester = $this->getActiveSemester();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            $deleted = DB::table('section_class_matrix')
                ->where('section_id', $sectionId)
                ->where('class_id', $classId)
                ->where('semester_id', $activeSemester->semester_id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found in this section'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Class removed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove class: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get section adviser
     */
    public function getSectionAdviser($sectionId)
    {
        try {
            $activeSemester = $this->getActiveSemester();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            $adviser = DB::table('section_adviser_matrix as sam')
                ->join('teachers as t', 'sam.teacher_id', '=', 't.id')
                ->where('sam.section_id', $sectionId)
                ->where('sam.semester_id', $activeSemester->semester_id)
                ->select(
                    't.id',
                    't.first_name',
                    't.middle_name',
                    't.last_name',
                    't.email',
                    'sam.assigned_date'
                )
                ->first();

            return response()->json([
                'success' => true,
                'adviser' => $adviser
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load adviser: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available teachers for adviser assignment
     */
    public function getAvailableTeachers()
    {
        try {
            $teachers = DB::table('teachers')
                ->where('status', 1)
                ->select(
                    'id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            return response()->json([
                'success' => true,
                'teachers' => $teachers
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load teachers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign adviser to section
     */
public function assignAdviser(Request $request, $sectionId)
{
    $request->validate([
        'teacher_id' => 'required|exists:teachers,id',
        'semester_id' => 'required|exists:semesters,id'
    ]);

    try {
        DB::beginTransaction();

        // Check if teacher exists and is active
        $teacher = DB::table('teachers')
            ->where('id', $request->teacher_id)
            ->where('status', 1)
            ->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found or inactive'
            ], 404);
        }

        // Get school year from semester
        $semester = DB::table('semesters')
            ->where('id', $request->semester_id)
            ->first();

        // Delete existing adviser for this section-semester
        DB::table('section_adviser_matrix')
            ->where('section_id', $sectionId)
            ->where('semester_id', $request->semester_id)
            ->delete();

        // Insert new adviser
        DB::table('section_adviser_matrix')->insert([
            'section_id' => $sectionId,
            'teacher_id' => $request->teacher_id,
            'semester_id' => $request->semester_id,
            'assigned_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ✅ CREATE INITIAL TEACHER STATUS TRAIL
        $this->ensureTeacherActiveTrail($request->teacher_id, $semester->school_year_id);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Adviser assigned successfully'
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to assign adviser: ' . $e->getMessage()
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
            'trigger' => 'adviser_assignment'
        ]);
    }
}

    /**
     * Remove adviser from section
     */
    public function removeAdviser($sectionId)
    {
        try {
            $activeSemester = $this->getActiveSemester();
            
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 400);
            }

            $deleted = DB::table('section_adviser_matrix')
                ->where('section_id', $sectionId)
                ->where('semester_id', $activeSemester->semester_id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'No adviser assigned to this section'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Adviser removed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove adviser: ' . $e->getMessage()
            ], 500);
        }
    }
}
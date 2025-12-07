<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class SectionGrade_Management extends MainController
{
    public function index() 
    {
        // Get active semester
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name',
                's.code',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
            )
            ->first();

        if (!$activeSemester) {
            return view('admin.grade_management.section_grade_view', [
                'scripts' => ['grade_management/section_grade_view.js'],
                'activeSemester' => null,
                'activeSemesterDisplay' => 'No Active Semester'
            ]);
        }

        $data = [
            'scripts' => ['grade_management/section_grade_view.js'],
            'activeSemester' => $activeSemester,
            'activeSemesterDisplay' => $activeSemester->display_name
        ];

        return view('admin.grade_management.section_grade_view', $data);
    }

    /**
     * Get sections with grade submission status for active semester
     */
    public function getSectionsWithGrades()
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
                ], 404);
            }

            // Get all active sections with their details
            // Note: We're getting ALL active sections, not filtering by semester_id
            // because section_class_matrix determines which sections are enrolled in current semester
            $sections = DB::table('sections as sec')
                ->join('strands as str', 'sec.strand_id', '=', 'str.id')
                ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('sec.status', 1)
                ->select(
                    'sec.id',
                    'sec.code',
                    'sec.name',
                    'sec.strand_id',
                    'sec.level_id',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->get();

            // Get student count per section
            $studentCounts = DB::table('students')
                ->select('section_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('section_id')
                ->groupBy('section_id')
                ->pluck('count', 'section_id');

            // Get enrolled classes count per section FOR CURRENT SEMESTER
            $classCounts = DB::table('section_class_matrix as scm')
                ->where('scm.semester_id', $activeSemester->id)
                ->select('scm.section_id', DB::raw('COUNT(DISTINCT scm.class_id) as count'))
                ->groupBy('scm.section_id')
                ->pluck('count', 'section_id');

            // Only show sections that have classes enrolled in current semester
            $sectionsWithClasses = $classCounts->keys()->toArray();
            $sections = $sections->whereIn('id', $sectionsWithClasses);

            // Get grade submission status per section
            $gradeStats = DB::table('section_class_matrix as scm')
                ->join('students as s', 's.section_id', '=', 'scm.section_id')
                ->leftJoin('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('grades_final as gf', function($join) use ($activeSemester) {
                    $join->on('c.class_code', '=', 'gf.class_code')
                         ->on('s.student_number', '=', 'gf.student_number')
                         ->where('gf.semester_id', '=', $activeSemester->id);
                })
                ->where('scm.semester_id', $activeSemester->id)
                ->select(
                    'scm.section_id',
                    DB::raw('COUNT(DISTINCT scm.class_id) as total_classes'),
                    DB::raw('COUNT(DISTINCT CASE WHEN gf.id IS NOT NULL THEN CONCAT(s.student_number, "-", scm.class_id) END) as submitted_grades'),
                    DB::raw('COUNT(DISTINCT CONCAT(s.student_number, "-", scm.class_id)) as expected_grades')
                )
                ->groupBy('scm.section_id')
                ->get()
                ->keyBy('section_id');

            // Enhance sections with counts and stats - convert to array of objects
            $sections = $sections->map(function($section) use ($studentCounts, $classCounts, $gradeStats) {
                $section->student_count = $studentCounts[$section->id] ?? 0;
                $section->class_count = $classCounts[$section->id] ?? 0;
                
                $stats = $gradeStats[$section->id] ?? null;
                $section->total_classes = $stats->total_classes ?? 0;
                $section->expected_grades = $stats->expected_grades ?? 0;
                $section->submitted_grades = $stats->submitted_grades ?? 0;
                $section->submission_percentage = $section->expected_grades > 0 
                    ? round(($section->submitted_grades / $section->expected_grades) * 100, 1)
                    : 0;
                
                return $section;
            })->values(); // Use values() to reset array keys and ensure it's a proper array

            // Get levels and strands for filters
            $levels = DB::table('levels')->select('id', 'name')->get();
            $strands = DB::table('strands')
                ->where('status', 1)
                ->select('id', 'code', 'name')
                ->get();

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'levels' => $levels,
                'strands' => $strands,
                'semester' => $activeSemester
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get sections with grades', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load sections'
            ], 500);
        }
    }

    /**
     * Get section grade details
     */
    public function getSectionGradeDetails($sectionId)
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
                ], 404);
            }

            // Get section details
            $section = DB::table('sections as sec')
                ->join('strands as str', 'sec.strand_id', '=', 'str.id')
                ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('sec.id', $sectionId)
                ->select(
                    'sec.id',
                    'sec.code',
                    'sec.name',
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

            // Get enrolled classes with grade submission status
            $classes = DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('scm.section_id', $sectionId)
                ->where('scm.semester_id', $activeSemester->id)
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name',
                    DB::raw('GROUP_CONCAT(DISTINCT CONCAT(t.first_name, " ", t.last_name) SEPARATOR ", ") as teachers')
                )
                ->groupBy('c.id', 'c.class_code', 'c.class_name')
                ->get();

            // Get students in section
            $students = DB::table('students')
                ->where('section_id', $sectionId)
                ->select(
                    'student_number',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'gender',
                    'student_type'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            // Get grade submission status for each class
            foreach ($classes as $class) {
                $gradeStats = DB::table('students as s')
                    ->join('classes as c', 'c.id', '=', DB::raw($class->id))
                    ->leftJoin('grades_final as gf', function($join) use ($class, $activeSemester) {
                        $join->on('s.student_number', '=', 'gf.student_number')
                             ->where('gf.class_code', '=', $class->class_code)
                             ->where('gf.semester_id', '=', $activeSemester->id);
                    })
                    ->where('s.section_id', $sectionId)
                    ->select(
                        DB::raw('COUNT(DISTINCT s.id) as total_students'),
                        DB::raw('COUNT(DISTINCT gf.id) as submitted_count')
                    )
                    ->first();

                $class->total_students = $gradeStats->total_students ?? 0;
                $class->submitted_count = $gradeStats->submitted_count ?? 0;
                $class->is_complete = $class->total_students > 0 && $class->total_students == $class->submitted_count;
                $class->submission_percentage = $class->total_students > 0
                    ? round(($class->submitted_count / $class->total_students) * 100, 1)
                    : 0;
            }

            return response()->json([
                'success' => true,
                'section' => $section,
                'classes' => $classes,
                'students' => $students
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get section grade details', [
                'section_id' => $sectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load section details'
            ], 500);
        }
    }

    /**
     * Get student grades for a specific class in a section
     */
    public function getClassGrades($sectionId, $classId)
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
                ], 404);
            }

            // Get class details
            $class = DB::table('classes')->where('id', $classId)->first();
            
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            // Get all students in section with their grades
            $grades = DB::table('students as s')
                ->leftJoin('grades_final as gf', function($join) use ($class, $activeSemester) {
                    $join->on('s.student_number', '=', 'gf.student_number')
                         ->where('gf.class_code', '=', $class->class_code)
                         ->where('gf.semester_id', '=', $activeSemester->id);
                })
                ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
                ->where('s.section_id', $sectionId)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.gender',
                    's.student_type',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks',
                    'gf.computed_at',
                    DB::raw('CONCAT(t.first_name, " ", t.last_name) as computed_by_name')
                )
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->get();

            return response()->json([
                'success' => true,
                'class' => $class,
                'grades' => $grades
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to get class grades', [
                'section_id' => $sectionId,
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load grades'
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeTransmutation;

class Grade_Card extends MainController
{
    public function card_grades()
    {
        // Get semesters
        $semesters = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->select(
                's.id',
                's.name',
                's.code',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name"),
                's.status'
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('s.code', 'asc')
            ->get();

        $activeSemester = $semesters->where('status', 'active')->first();

        // Get sections
        $sections = DB::table('sections as sec')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->select(
                'sec.id',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name'
            )
            ->where('sec.status', 1)
            ->orderBy('sec.code')
            ->get();

        // Get grade cards - one record per student per semester
        $gradeCards = DB::table('grades_final as gf')
            ->select(
                'gf.student_number',
                'gf.semester_id',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name',
                DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                DB::raw("COUNT(gf.id) as total_subjects"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'PASSED' THEN 1 ELSE 0 END) as passed_count"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'FAILED' THEN 1 ELSE 0 END) as failed_count"),
                DB::raw("ROUND(AVG(gf.final_grade), 2) as general_average"),
                DB::raw("MAX(gf.computed_at) as last_computed")
            )
            ->join('students as s', 'gf.student_number', '=', 's.student_number')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->groupBy(
                'gf.student_number',
                'gf.semester_id',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'sec.code',
                'sec.name',
                'str.code',
                'lvl.name',
                'semester_display'
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.code', 'asc')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $data = [
            'scripts' => ['grade_management/card_grades.js'],
            'semesters' => $semesters,
            'activeSemester' => $activeSemester,
            'sections' => $sections,
            'gradeCards' => $gradeCards
        ];

        return view('admin.grade_management.card_grades', $data);
    }

    public function getGradeCard(Request $request)
    {
        try {
            $studentNumber = $request->student_number;
            $semesterId = $request->semester_id;

            // Get student info
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_number', $studentNumber)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.student_type',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found.'
                ], 404);
            }

            // Get semester info
            $semester = DB::table('semesters as sem')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->where('sem.id', $semesterId)
                ->select(
                    'sem.id',
                    'sem.name',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as display_name")
                )
                ->first();

            // Get all grades for this student in this semester
            $grades = DB::table('grades_final as gf')
                ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
                ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
                ->where('gf.student_number', $studentNumber)
                ->where('gf.semester_id', $semesterId)
                ->select(
                    'gf.id',
                    'gf.class_code',
                    'c.class_name',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks',
                    'gf.computed_at',
                    DB::raw("CONCAT(t.first_name, ' ', t.last_name) as teacher_name")
                )
                ->orderBy('c.class_code')
                ->get();

            // Calculate statistics
            $totalSubjects = $grades->count();
            $passedCount = $grades->where('remarks', 'PASSED')->count();
            $failedCount = $grades->where('remarks', 'FAILED')->count();
            $generalAverage = $grades->avg('final_grade');

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'semester' => $semester,
                    'grades' => $grades,
                    'statistics' => [
                        'total_subjects' => $totalSubjects,
                        'passed_count' => $passedCount,
                        'failed_count' => $failedCount,
                        'general_average' => round($generalAverage, 2)
                    ]
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get grade card', [
                'student_number' => $studentNumber,
                'semester_id' => $semesterId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade card.'
            ], 500);
        }
    }

    public function viewGradeCardPage($studentNumber, $semesterId)
    {
        try {
            // Get student info
            $student = DB::table('students as s')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->where('s.student_number', $studentNumber)
                ->select(
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.student_type',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name'
                )
                ->first();

            if (!$student) {
                abort(404, 'Student not found');
            }

            // Get semester info
            $semester = DB::table('semesters as sem')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->where('sem.id', $semesterId)
                ->select(
                    'sem.id',
                    'sem.name',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as display_name")
                )
                ->first();

            if (!$semester) {
                abort(404, 'Semester not found');
            }

            // Get all grades for this student in this semester
            $grades = DB::table('grades_final as gf')
                ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
                ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
                ->where('gf.student_number', $studentNumber)
                ->where('gf.semester_id', $semesterId)
                ->select(
                    'gf.id',
                    'gf.class_code',
                    'c.class_name',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks',
                    'gf.computed_at',
                    DB::raw("CONCAT(t.first_name, ' ', t.last_name) as teacher_name")
                )
                ->orderBy('c.class_code')
                ->get();

            // Calculate statistics
            $totalSubjects = $grades->count();
            $passedCount = $grades->where('remarks', 'PASSED')->count();
            $failedCount = $grades->where('remarks', 'FAILED')->count();
            $generalAverage = $grades->avg('final_grade');

            $statistics = [
                'total_subjects' => $totalSubjects,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'general_average' => round($generalAverage, 2)
            ];

            $data = [
                'student' => $student,
                'semester' => $semester,
                'grades' => $grades,
                'statistics' => $statistics
            ];

            return view('admin.grade_management.view_card', $data);
        } catch (Exception $e) {
            \Log::error('Failed to load grade card page', [
                'student_number' => $studentNumber,
                'semester_id' => $semesterId,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Failed to load grade card');
        }
    }
}
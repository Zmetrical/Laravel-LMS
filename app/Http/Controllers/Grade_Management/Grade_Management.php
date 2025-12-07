<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class Grade_Management extends MainController
{
    public function list_grades() 
    {
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

        $classes = DB::table('classes')
            ->select('id', 'class_code', 'class_name')
            ->orderBy('class_code')
            ->get();

        // Get all sections
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

        // Get ALL final grades with student and class info
        $grades = DB::table('grades_final as gf')
            ->join('students as s', 'gf.student_number', '=', 's.student_number')
            ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
            ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->leftJoin('teachers as t', 'gf.computed_by', '=', 't.id')
            ->select(
                'gf.id',
                'gf.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_type',
                'gf.class_code',
                'c.class_name',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name',
                'gf.q1_grade',
                'gf.q2_grade',
                'gf.final_grade',
                'gf.remarks',
                'gf.semester_id',
                DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                'gf.computed_at',
                DB::raw("CONCAT(t.first_name, ' ', t.last_name) as computed_by_name")
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.code', 'asc')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->orderBy('c.class_code')
            ->get();

        $data = [
            'scripts' => ['grade_management/list_grades.js'],
            'semesters' => $semesters,
            'activeSemester' => $activeSemester,
            'classes' => $classes,
            'sections' => $sections,
            'grades' => $grades
        ];

        return view('admin.grade_management.list_grades', $data);
    }

    /**
     * Get grade details for modal view
     */
    public function getGradeDetails($gradeId)
    {
        try {
            $grade = DB::table('grades_final as gf')
                ->join('students as s', 'gf.student_number', '=', 's.student_number')
                ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
                ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
                ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as str', 'sec.strand_id', '=', 'str.id')
                ->leftJoin('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->leftJoin('teachers as comp', 'gf.computed_by', '=', 'comp.id')
                ->where('gf.id', $gradeId)
                ->select(
                    'gf.*',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.student_type',
                    'c.class_name',
                    'c.ww_perc',
                    'c.pt_perc',
                    'c.qa_perce',
                    'sec.code as section_code',
                    'sec.name as section_name',
                    'str.code as strand_code',
                    'str.name as strand_name',
                    'lvl.name as level_name',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                    DB::raw("CONCAT(comp.first_name, ' ', comp.last_name) as computed_by_name")
                )
                ->first();

            if (!$grade) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade record not found.'
                ], 404);
            }

            $grade->full_name = trim($grade->first_name . ' ' . 
                                    ($grade->middle_name ? substr($grade->middle_name, 0, 1) . '. ' : '') . 
                                    $grade->last_name);

            return response()->json([
                'success' => true,
                'data' => $grade
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get grade details', [
                'grade_id' => $gradeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load grade details.'
            ], 500);
        }
    }
}
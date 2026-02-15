<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class Teacher_Card_List extends MainController
{
    public function index()
    {
        $teacher = Auth::guard('teacher')->user();
        
        if (!$teacher) {
            abort(403, 'Unauthorized access');
        }

        // Get active semester
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                'sy.id as school_year_id',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
            )
            ->first();
        
        // If no active semester, get the most recent one
        if (!$activeSemester) {
            $activeSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->orderBy('sy.year_start', 'desc')
                ->orderBy('s.start_date', 'desc')
                ->select(
                    's.id as semester_id',
                    's.name as semester_name',
                    's.code as semester_code',
                    'sy.id as school_year_id',
                    'sy.code as school_year_code',
                    DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
                )
                ->first();
        }
        
        if (!$activeSemester) {
            return view('teacher.teacher_card_list', [
                'scripts' => ['teacher/teacher_card_list.js'],
                'sections' => collect(),
                'gradeCards' => collect(),
                'activeSemester' => null,
                'message' => 'No active semester found.'
            ]);
        }

        // Get sections where teacher is assigned as ADVISER
        $sections = DB::table('section_adviser_matrix as sam')
            ->join('sections as sec', 'sam.section_id', '=', 'sec.id')
            ->join('strands as str', 'sec.strand_id', '=', 'str.id')
            ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->where('sam.teacher_id', $teacher->id)
            ->where('sam.semester_id', $activeSemester->semester_id)
            ->select(
                'sec.id',
                'sec.code as section_code',
                'sec.name as section_name',
                'str.code as strand_code',
                'lvl.name as level_name'
            )
            ->distinct()
            ->orderBy('sec.code')
            ->get();

        // If no sections found, show message
        if ($sections->isEmpty()) {
            return view('teacher.teacher_card_list', [
                'scripts' => ['teacher/teacher_card_list.js'],
                'sections' => collect(),
                'gradeCards' => collect(),
                'activeSemester' => $activeSemester,
                'message' => 'You are not assigned as adviser to any section for the active semester.'
            ]);
        }

        // Get grade cards for students in teacher's advised sections
        $gradeCards = DB::table('grades_final as gf')
            ->join('students as s', 'gf.student_number', '=', 's.student_number')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->join('sections as sec', 's.section_id', '=', 'sec.id')
            ->join('strands as str', 'sec.strand_id', '=', 'str.id')
            ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->leftJoin('guardian_students as gs', 's.student_number', '=', 'gs.student_number')
            ->leftJoin('guardians as g', 'gs.guardian_id', '=', 'g.id')
            ->whereIn('s.section_id', $sections->pluck('id'))
            ->where('gf.semester_id', $activeSemester->semester_id)
            ->where('s.student_type', 'regular')
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
                'g.email as guardian_email',
                'g.email_verified_at',
                DB::raw("CASE 
                    WHEN g.email_verified_at IS NOT NULL THEN 'verified'
                    WHEN g.id IS NOT NULL AND g.email_verified_at IS NULL THEN 'pending'
                    ELSE 'none'
                END as verification_status"),
                DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                DB::raw("COUNT(gf.id) as total_subjects"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'PASSED' THEN 1 ELSE 0 END) as passed_count"),
                DB::raw("SUM(CASE WHEN gf.remarks = 'FAILED' THEN 1 ELSE 0 END) as failed_count"),
                DB::raw("ROUND(AVG(gf.final_grade), 2) as general_average"),
                DB::raw("MAX(gf.computed_at) as last_computed")
            )
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
                'g.email',
                'g.email_verified_at',
                'verification_status',
                'semester_display'
            )
            ->orderBy('sec.code')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        $data = [
            'scripts' => ['teacher/teacher_card_list.js'],
            'sections' => $sections,
            'gradeCards' => $gradeCards,
            'activeSemester' => $activeSemester
        ];

        return view('teacher.teacher_card_list', $data);
    }
}
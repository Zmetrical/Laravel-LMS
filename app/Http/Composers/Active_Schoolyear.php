<?php

namespace App\Http\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class Active_Schoolyear
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Get active semester with school year info
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                's.start_date',
                's.end_date',
                'sy.id as school_year_id',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
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
                    's.start_date',
                    's.end_date',
                    'sy.id as school_year_id',
                    'sy.year_start',
                    'sy.year_end',
                    'sy.code as school_year_code'
                )
                ->first();
        }

        // Get active school year (might be different from semester's school year)
        $activeSchoolYear = DB::table('school_years')
            ->where('status', 'active')
            ->first();

        // Share with all views
        $view->with([
            'activeSemester' => $activeSemester,
            'activeSchoolYear' => $activeSchoolYear,
            'activeSemesterDisplay' => $activeSemester ? 
                "{$activeSemester->school_year_code} - {$activeSemester->semester_name}" : 
                'No Active Semester'
        ]);
    }
}
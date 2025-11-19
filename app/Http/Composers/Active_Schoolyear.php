<?php

namespace App\Http\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class Active_Schoolyear
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view)
    {
        $activeSemester = DB::table('semesters')
            ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
            ->where('semesters.status', 'active')
            ->select(
                'semesters.id',
                'semesters.name as semester_name',
                'semesters.code as semester_code',
                'semesters.status',
                'school_years.code as school_year_code',
                'school_years.year_start',
                'school_years.year_end'
            )
            ->first();

        $view->with('activeSemester', $activeSemester);
    }
}
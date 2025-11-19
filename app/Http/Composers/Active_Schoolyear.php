<?php

namespace App\Http\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                's.status as semester_status',
                'sy.id as school_year_id',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code',
                'sy.status as school_year_status'
            )
            ->first();

        // Debug log
        Log::info('Active Semester Query Result:', ['semester' => $activeSemester]);

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
                    's.status as semester_status',
                    'sy.id as school_year_id',
                    'sy.year_start',
                    'sy.year_end',
                    'sy.code as school_year_code',
                    'sy.status as school_year_status'
                )
                ->first();
            
            Log::info('Fallback Semester Query Result:', ['semester' => $activeSemester]);
        }

        // Get active school year separately
        $activeSchoolYear = DB::table('school_years')
            ->where('status', 'active')
            ->first();

        Log::info('Active School Year Query Result:', ['school_year' => $activeSchoolYear]);

        // Create a formatted object for the view
        $semesterData = null;
        if ($activeSemester) {
            $semesterData = (object)[
                'semester_id' => $activeSemester->semester_id,
                'semester_name' => $activeSemester->semester_name,
                'semester_code' => $activeSemester->semester_code,
                'school_year_code' => $activeSemester->school_year_code,
                'name' => $activeSemester->semester_name,
                'code' => $activeSemester->semester_code,
                'school_year' => (object)[
                    'id' => $activeSemester->school_year_id,
                    'code' => $activeSemester->school_year_code,
                    'year_start' => $activeSemester->year_start,
                    'year_end' => $activeSemester->year_end
                ]
            ];
        }

        // Share with all views
        $view->with([
            'activeSemester' => $semesterData,
            'activeSchoolYear' => $activeSchoolYear,
            'activeSemesterDisplay' => $semesterData ? 
                "{$semesterData->school_year_code} - {$semesterData->semester_name}" : 
                'No Active Semester'
        ]);
    }
}
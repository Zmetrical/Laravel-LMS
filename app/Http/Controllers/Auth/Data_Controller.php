<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MainController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Data_Controller extends MainController
{
    public function student_data($id)
    {

        $student = DB::table(table: 'students')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->join('strands', 'sections.strand_id', '=', 'strands.id')
            ->select(
                'students.*',
                'sections.name as section',
                'levels.name as level',
                'strands.code as strand'
            )
            ->where('students.student_number', '=', $id)
            ->first();


        $data = [
            'student' => $student,
            'mode' => 'view',
            'scripts' => [
                'admin/data.js',
            ],
        ];

        return view('profile.profile_data', $data);
    }
}

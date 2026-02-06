<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User_Management\Teacher;

class TeacherController extends Controller
{
    public function index()
    {
        $data = [
            'scripts' => [
                'teacher/dashboard.js',
            ],
        ];

        return view('teacher.dashboard', $data);
    }

    public function login()
    {
        $data = [
            'scripts' => [
                'teacher/login.js',
            ],
        ];

        return view('admin.login', $data);
    }

    // View teacher profile
    public function show_profile()
    {
        $teacherId = Auth::guard('teacher')->id();
        $teacher = Teacher::findOrFail($teacherId);

        // Get classes with sections
        $classes = DB::table('teacher_class_matrix as tcm')
            ->join('classes as c', 'tcm.class_id', '=', 'c.id')
            ->leftJoin('section_class_matrix as scm', function($join) {
                $join->on('c.id', '=', 'scm.class_id');
            })
            ->leftJoin('sections as s', 'scm.section_id', '=', 's.id')
            ->where('tcm.teacher_id', $teacherId)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name',
                DB::raw('GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ") as sections')
            )
            ->groupBy('c.id', 'c.class_code', 'c.class_name')
            ->get();

        $data = [
            'teacher' => $teacher,
            'classes' => $classes
        ];

        return view('teacher.teacher_profile', $data);
    }
}
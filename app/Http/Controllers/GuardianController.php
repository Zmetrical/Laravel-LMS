<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Exception;

class GuardianController extends Controller
{
    public function index()
    {
        $data = [
            'scripts' => [
                'guardian/dashboard.js',
            ],
        ];

        return view('guardian.dashboard', $data);
    }

    // View specific student's grades
    public function view_student_grades($student_number)
    {
        // Mock student data
        $student = (object)[
            'student_number' => $student_number,
            'full_name' => 'Maria Dela Cruz',
            'section' => 'STEM 11-A',
            'level' => 'Grade 11',
            'profile_image' => null
        ];

        // Mock grades data
        $grades = collect([
            (object)[
                'class_name' => 'General Mathematics',
                'q1_grade' => 88.5,
                'q2_grade' => 90.0,
                'final_grade' => 89.25,
                'remarks' => 'PASSED'
            ],
            (object)[
                'class_name' => 'English for Academic Purposes',
                'q1_grade' => 92.0,
                'q2_grade' => 91.5,
                'final_grade' => 91.75,
                'remarks' => 'PASSED'
            ]
        ]);

        $data = [
            'scripts' => ['guardian/student_grades.js'],
            'student' => $student,
            'grades' => $grades
        ];

        return view('guardian.student_grades', $data);
    }
}
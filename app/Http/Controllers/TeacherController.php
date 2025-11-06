<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {

        $data = [
            'scripts' => [
                'student/dashboard.js',
            ],
        ];

        return view('student.dashboard', $data);
    }

    public function login()
    {

        $data = [
            'scripts' => [
                'student/login.js',
            ],
        ];

        return view('student.login', $data);
    }
}

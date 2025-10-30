<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

class Class_Page extends MainController
{
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

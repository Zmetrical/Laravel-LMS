<?php

namespace App\Http\Controllers\Enrollment_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

class Enroll_Class extends MainController
{
    public function index()
    {

        $data = [
            'scripts' => ['enroll_management/enroll_class.js'],
        ];

        return view('admin.enroll_management.enroll_class', $data);
    }
}

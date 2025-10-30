<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

class Class_List extends MainController
{
    public function student_class_list()
    {

        $data = [
            'scripts' => ['student_class_page/list_class.js'],
        ];

        return view('student.class_management.list_class', $data);
    }
}

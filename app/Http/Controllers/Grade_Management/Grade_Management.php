<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class Grade_Management extends MainController
{
    public function list_grades() 
    {
        $data = [
            'scripts' => ['grade_management/list_grades.js'],
        ];

        return view('admin.grade_management.list_grades', $data);
    }
}

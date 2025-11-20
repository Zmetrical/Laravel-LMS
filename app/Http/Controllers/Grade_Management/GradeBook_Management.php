<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class GradeBook_Management extends MainController
{
    public function list_gradebook() 
    {
        $data = [
            'scripts' => ['grade_management/page_gradebook.js'],
        ];

        return view('teacher.gradebook.page_gradebook', $data);
    }
}

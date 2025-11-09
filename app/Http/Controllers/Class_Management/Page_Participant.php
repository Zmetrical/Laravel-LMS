<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;

class Page_Participant extends MainController
{
    public function teacherIndex($classId)
    {
        // Fetch class info
        $class = DB::table('classes')->where('id', $classId)->first();

        // If not found, you can handle it gracefully
        if (!$class) {
            abort(404, 'Class not found');
        }

        // Return the view
        return view('modules.class.page_participant', [
            'userType' => 'teacher',
            'class' => $class,
        ]);
    
    }
}

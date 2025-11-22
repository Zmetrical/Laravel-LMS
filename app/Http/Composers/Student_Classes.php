<?php

namespace App\Http\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Student_Classes
{
    public function compose(View $view)
    {
        $studentClasses = [];
        
        if (Auth::guard('student')->check()) {
            $student = Auth::guard('student')->user();
            
            // Get active semester
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();
            
            if (!$activeSemester) {
                $view->with('studentClasses', []);
                return;
            }
            
            // Create a unique session key for this student and semester
            $sessionKey = 'student_classes_' . $student->id . '_sem_' . $activeSemester->id;
            
            // Check if classes are already in session
            if (Session::has($sessionKey)) {
                $studentClasses = Session::get($sessionKey);
            } else {
                // Fetch from database only if not in session
                if ($student->student_type === 'regular' && $student->section_id) {
                    // Regular students: Get classes through section FOR CURRENT SEMESTER
                    $studentClasses = DB::table('section_class_matrix as scm')
                        ->join('classes as c', 'scm.class_id', '=', 'c.id')
                        ->where('scm.section_id', $student->section_id)
                        ->where('scm.semester_id', $activeSemester->id) // Filter by active semester
                        ->select('c.id', 'c.class_code', 'c.class_name')
                        ->orderBy('c.class_code')
                        ->get()
                        ->toArray();
                } else {
                    // Irregular students: Get classes from student_class_matrix FOR CURRENT SEMESTER
                    $studentClasses = DB::table('student_class_matrix as scm')
                        ->join('classes as c', function($join) {
                            $join->on(
                                DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                                '=',
                                DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                            );
                        })
                        ->where('scm.student_number', $student->student_number)
                        ->where('scm.semester_id', $activeSemester->id) // Filter by active semester
                        ->where('scm.enrollment_status', 'enrolled')
                        ->select('c.id', 'c.class_code', 'c.class_name')
                        ->orderBy('c.class_code')
                        ->get()
                        ->toArray();
                }
                
                // Store in session (will persist until logout or session expires)
                Session::put($sessionKey, $studentClasses);
            }
        }
        
        $view->with('studentClasses', $studentClasses);
    }
}
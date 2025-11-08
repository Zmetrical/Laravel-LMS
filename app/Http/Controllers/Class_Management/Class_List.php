<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Enroll_Management\Classes;
use App\Models\Enroll_Management\Section;

class Class_List extends MainController
{
    /**
     * Show student class list page
     */
    public function student_class_list()
    {
        $data = [
            'scripts' => ['student/list_class.js'],
            'userType' => 'student',

        ];
        return view('modules.class.list_class', $data);
    }

    /**
     * Get student's enrolled classes
     */
    public function getStudentClasses()
    {
        try {
            $student = Auth::guard('student')->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Get classes based on student type
            if ($student->student_type === 'regular') {
                // Regular students: Get classes through their section
                $classes = $this->getRegularStudentClasses($student);
            } else {
                // Irregular students: Get classes directly from student_class_matrix
                $classes = $this->getIrregularStudentClasses($student);
            }

            return response()->json([
                'success' => true,
                'data' => $classes,
                'student_type' => $student->student_type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get classes for regular students (through section)
     */
    private function getRegularStudentClasses($student)
    {
        if (!$student->section_id) {
            return [];
        }

        return DB::table('section_class_matrix as scm')
            ->join('classes as c', 'scm.class_id', '=', 'c.id')
            ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
            ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
            ->where('scm.section_id', $student->section_id)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name',
                'c.ww_perc',
                'c.pt_perc',
                'c.qa_perce',
                DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name"),
                't.email as teacher_email',
                't.profile_image as teacher_image'
            )
            ->orderBy('c.class_code')
            ->get();
    }

    /**
     * Get classes for irregular students (direct enrollment)
     */
    private function getIrregularStudentClasses($student)
    {
        return DB::table('student_class_matrix as scm')
            ->join('classes as c', function ($join) {
                $join->on(
                    DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                );
            })
            ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
            ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
            ->where('scm.student_number', $student->student_number)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name',
                'c.ww_perc',
                'c.pt_perc',
                'c.qa_perce',
                DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name"),
                't.email as teacher_email',
                't.profile_image as teacher_image'
            )
            ->orderBy('c.class_code')
            ->get();
    }

    /**
     * Get class details for student
     */
    public function getClassDetails($classId)
    {
        try {
            $student = Auth::guard('student')->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Verify student is enrolled in this class
            $isEnrolled = $this->verifyStudentEnrollment($student, $classId);

            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this class'
                ], 403);
            }

            // Get class details
            $class = DB::table('classes as c')
                ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
                ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
                ->where('c.id', $classId)
                ->select(
                    'c.*',
                    DB::raw("CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, '')) as teacher_name"),
                    't.email as teacher_email',
                    't.phone as teacher_phone',
                    't.profile_image as teacher_image'
                )
                ->first();

            // Get enrolled sections count
            $sectionsCount = DB::table('section_class_matrix')
                ->where('class_id', $classId)
                ->count();

            // Get total students in this class
            $studentsCount = $this->getClassStudentsCount($classId);

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'sections_count' => $sectionsCount,
                    'students_count' => $studentsCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load class details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify if student is enrolled in the class
     */
    private function verifyStudentEnrollment($student, $classId)
    {
        if ($student->student_type === 'regular' && $student->section_id) {
            // Check through section
            return DB::table('section_class_matrix')
                ->where('section_id', $student->section_id)
                ->where('class_id', $classId)
                ->exists();
        } else {
            // Check through student_class_matrix
            return DB::table('student_class_matrix as scm')
                ->join('classes as c', function ($join) {
                    $join->on(
                        DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->where('scm.student_number', $student->student_number)
                ->where('c.id', $classId)
                ->exists();
        }
    }

    /**
     * Get total students count in a class
     */
    private function getClassStudentsCount($classId)
    {
        // Regular students through sections
        $regularCount = DB::table('section_class_matrix as scm')
            ->join('students as s', 'scm.section_id', '=', 's.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.student_type', 'regular')
            ->count();

        // Irregular students directly enrolled
        $irregularCount = DB::table('student_class_matrix as scm')
            ->join('classes as c', function ($join) {
                $join->on(
                    DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                );
            })
            ->join('students as s', function ($join) {
                $join->on(
                    DB::raw('scm.student_number COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('s.student_number COLLATE utf8mb4_unicode_ci')
                );
            })
            ->where('c.id', $classId)
            ->where('s.student_type', 'irregular')
            ->count();

        return $regularCount + $irregularCount;
    }





    /**
     * Show teacher class list page
     */
    public function teacher_class_list()
    {
        $data = [
            'userType' => 'teacher',
            'scripts' => ['teacher/list_class.js'],
        ];
        return view('modules.class.list_class', $data);
    }

    /**
     * Get teacher's assigned classes
     */
    public function getTeacherClasses()
    {
        try {
            $teacher = Auth::guard('teacher')->user();

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $classes = DB::table('teacher_class_matrix as tcm')
                ->join('classes as c', 'tcm.class_id', '=', 'c.id')
                ->where('tcm.teacher_id', $teacher->id)
                ->select(
                    'c.id',
                    'c.class_code',
                    'c.class_name'
                )
                ->orderBy('c.class_code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes: ' . $e->getMessage()
            ], 500);
        }
    }
}

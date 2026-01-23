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
    public function student_class_list()
    {
        $data = [
            'scripts' => ['student/list_class.js'],
            'userType' => 'student',
        ];
        return view('modules.class.list_class', $data);
    }

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

            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();

            if (!$activeSemester) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No active semester found',
                    'student_type' => $student->student_type
                ]);
            }

            // Get active quarter for the semester
            $activeQuarter = DB::table('quarters')
                ->where('semester_id', $activeSemester->id)
                ->orderBy('order_number', 'asc')
                ->first();

            if ($student->student_type === 'regular') {
                $classes = $this->getRegularStudentClasses($student, $activeSemester->id);
            } else {
                $classes = $this->getIrregularStudentClasses($student, $activeSemester->id);
            }

            foreach ($classes as $class) {
                $progress = $this->calculateClassProgress($student->student_number, $class->id);
                $class->progress_percentage = $progress['percentage'];
                $class->completed_lectures = $progress['completed'];
                $class->total_lectures = $progress['total'];
                
                // Get next incomplete lecture with quarter info
                $nextLecture = $this->getNextIncompleteLecture($student->student_number, $class->id);
                $class->next_lecture = $nextLecture;
                
                // Get current quarter progress
                if ($activeQuarter) {
                    $quarterProgress = $this->getQuarterProgress($student->student_number, $class->id, $activeQuarter->id);
                    $class->current_quarter = [
                        'id' => $activeQuarter->id,
                        'name' => $activeQuarter->name,
                        'code' => $activeQuarter->code,
                        'progress' => $quarterProgress
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $classes,
                'student_type' => $student->student_type,
                'active_semester' => [
                    'id' => $activeSemester->id,
                    'name' => $activeSemester->name,
                    'code' => $activeSemester->code
                ],
                'active_quarter' => $activeQuarter ? [
                    'id' => $activeQuarter->id,
                    'name' => $activeQuarter->name,
                    'code' => $activeQuarter->code
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getNextIncompleteLecture($studentNumber, $classId)
    {
        $nextLecture = DB::table('lectures')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->leftJoin('quarters', 'lessons.quarter_id', '=', 'quarters.id')
            ->leftJoin('student_lecture_progress as slp', function($join) use ($studentNumber) {
                $join->on('lectures.id', '=', 'slp.lecture_id')
                     ->where('slp.student_number', '=', $studentNumber);
            })
            ->where('lessons.class_id', $classId)
            ->where('lectures.status', 1)
            ->where('lessons.status', 1)
            ->where(function($query) {
                $query->whereNull('slp.is_completed')
                      ->orWhere('slp.is_completed', 0);
            })
            ->select(
                'lectures.id as lecture_id',
                'lessons.id as lesson_id',
                'lessons.title as lesson_title',
                'lectures.title as lecture_title',
                'quarters.id as quarter_id',
                'quarters.name as quarter_name',
                'quarters.code as quarter_code'
            )
            ->orderBy('lessons.order_number', 'asc')
            ->orderBy('lessons.created_at', 'asc')
            ->orderBy('lectures.order_number', 'asc')
            ->orderBy('lectures.created_at', 'asc')
            ->first();
        
        return $nextLecture;
    }

    private function getQuarterProgress($studentNumber, $classId, $quarterId)
    {
        // Total lectures in this quarter
        $totalLectures = DB::table('lectures')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->where('lessons.class_id', $classId)
            ->where('lessons.quarter_id', $quarterId)
            ->where('lectures.status', 1)
            ->where('lessons.status', 1)
            ->count();

        if ($totalLectures === 0) {
            return [
                'percentage' => 0,
                'completed' => 0,
                'total' => 0
            ];
        }

        // Completed lectures in this quarter
        $completedLectures = DB::table('student_lecture_progress as slp')
            ->join('lectures', 'slp.lecture_id', '=', 'lectures.id')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->where('slp.student_number', $studentNumber)
            ->where('lessons.class_id', $classId)
            ->where('lessons.quarter_id', $quarterId)
            ->where('slp.is_completed', 1)
            ->count();

        $percentage = round(($completedLectures / $totalLectures) * 100, 1);

        return [
            'percentage' => $percentage,
            'completed' => $completedLectures,
            'total' => $totalLectures
        ];
    }

    private function calculateClassProgress($studentNumber, $classId)
    {
        $totalLectures = DB::table('lectures')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->where('lessons.class_id', $classId)
            ->where('lectures.status', 1)
            ->where('lessons.status', 1)
            ->count();

        if ($totalLectures === 0) {
            return [
                'percentage' => 0,
                'completed' => 0,
                'total' => 0
            ];
        }

        $completedLectures = DB::table('student_lecture_progress as slp')
            ->join('lectures', 'slp.lecture_id', '=', 'lectures.id')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->where('slp.student_number', $studentNumber)
            ->where('lessons.class_id', $classId)
            ->where('slp.is_completed', 1)
            ->count();

        $percentage = round(($completedLectures / $totalLectures) * 100, 1);

        return [
            'percentage' => $percentage,
            'completed' => $completedLectures,
            'total' => $totalLectures
        ];
    }

    private function getRegularStudentClasses($student, $semesterId)
    {
        if (!$student->section_id) {
            return [];
        }

        return DB::table('section_class_matrix as scm')
            ->join('classes as c', 'scm.class_id', '=', 'c.id')
            ->leftJoin('teacher_class_matrix as tcm', 'c.id', '=', 'tcm.class_id')
            ->leftJoin('teachers as t', 'tcm.teacher_id', '=', 't.id')
            ->where('scm.section_id', $student->section_id)
            ->where('scm.semester_id', $semesterId)
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

    private function getIrregularStudentClasses($student, $semesterId)
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
            ->where('scm.semester_id', $semesterId)
            ->where('scm.enrollment_status', 'enrolled')
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

    public function teacher_class_list()
    {
        $data = [
            'userType' => 'teacher',
            'scripts' => ['teacher/list_class.js'],
        ];
        return view('modules.class.list_class', $data);
    }

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

        // Get active semester
        $activeSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        if (!$activeSemester) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No active semester found'
            ]);
        }

        $classes = DB::table('teacher_class_matrix as tcm')
            ->join('classes as c', 'tcm.class_id', '=', 'c.id')
            ->where('tcm.teacher_id', $teacher->id)
            ->where('tcm.semester_id', $activeSemester->id)
            ->select(
                'c.id',
                'c.class_code',
                'c.class_name'
            )
            ->orderBy('c.class_code')
            ->get();

        // Add counts for each class
        foreach ($classes as $class) {
            // Count sections assigned to this class
            $class->section_count = DB::table('section_class_matrix')
                ->where('class_id', $class->id)
                ->where('semester_id', $activeSemester->id)
                ->distinct()
                ->count('section_id');

            // Count students (both regular and irregular)
            $regularStudents = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->join('students as s', 'sec.id', '=', 's.section_id')
                ->where('scm.class_id', $class->id)
                ->where('scm.semester_id', $activeSemester->id)
                ->where('s.student_type', 'regular')
                ->distinct()
                ->count('s.student_number');

            $irregularStudents = DB::table('student_class_matrix as stcm')
                ->where('stcm.class_code', $class->class_code)
                ->where('stcm.semester_id', $activeSemester->id)
                ->where('stcm.enrollment_status', 'enrolled')
                ->count();

            $class->student_count = $regularStudents + $irregularStudents;
        }

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
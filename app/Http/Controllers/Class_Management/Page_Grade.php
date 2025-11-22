<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Page_Grade extends MainController
{
    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        return view('modules.grade.page_gradebook', [
            'userType' => 'teacher',
            'class' => $class,
            'scripts' => ['class_grade/page_gradebook.js']
        ]);
    }

    public function studentIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        $studentNumber = Auth::guard('student')->user()->student_number;

        return view('modules.grade.page_grade', [
            'userType' => 'student',
            'class' => $class,
            'studentNumber' => $studentNumber,
            'scripts' => ['class_grade/page_grade.js']
        ]);
    }

    public function getStudents($classId)
    {
        $students = DB::table('students as s')
            ->leftJoin('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->leftJoin('section_class_matrix as secm', 's.section_id', '=', 'secm.section_id')
            ->leftJoin('classes as c', function ($join) {
                $join->on('c.class_code', '=', 'scm.class_code')
                     ->orOn('c.id', '=', 'secm.class_id');
            })
            ->where('c.id', $classId)
            ->select('s.id', 's.student_number', 's.first_name', 's.middle_name', 's.last_name', 's.gender')
            ->distinct()
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        return response()->json(['students' => $students]);
    }

    public function getQuizzes($classId)
    {
        $quizzes = DB::table('quizzes as q')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('q.status', 1)
            ->select(
                'q.id',
                'q.title',
                'q.passing_score',
                'l.title as lesson_title'
            )
            ->orderBy('l.order_number')
            ->orderBy('q.id')
            ->get();

        return response()->json(['quizzes' => $quizzes]);
    }

    public function getGrades($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $students = DB::table('students as s')
            ->leftJoin('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->leftJoin('section_class_matrix as secm', 's.section_id', '=', 'secm.section_id')
            ->leftJoin('classes as c', function ($join) {
                $join->on('c.class_code', '=', 'scm.class_code')
                     ->orOn('c.id', '=', 'secm.class_id');
            })
            ->where('c.id', $classId)
            ->select('s.student_number', 's.first_name', 's.middle_name', 's.last_name')
            ->distinct()
            ->orderBy('s.last_name')
            ->get();

        $quizzes = DB::table('quizzes as q')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('q.status', 1)
            ->select('q.id', 'q.title', 'l.title as lesson_title', 'l.order_number')
            ->orderBy('l.order_number')
            ->get();

        $attemptsRaw = DB::table('student_quiz_attempts as sqa')
            ->join('quizzes as q', 'sqa.quiz_id', '=', 'q.id')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('sqa.status', 'graded')
            ->select(
                'sqa.student_number',
                'sqa.quiz_id',
                DB::raw('MAX(sqa.score) as best_score'),
                DB::raw('MAX(sqa.total_points) as total_points')
            )
            ->groupBy('sqa.student_number', 'sqa.quiz_id')
            ->get();

        $attempts = $attemptsRaw->groupBy('student_number');

        $grades = [];
        foreach ($students as $student) {
            $studentGrades = [
                'student_number' => $student->student_number,
                'full_name' => trim($student->last_name . ', ' . $student->first_name . ' ' . $student->middle_name),
                'quizzes' => [],
            ];

            foreach ($quizzes as $quiz) {
                $score = null;
                $total = null;

                if (isset($attempts[$student->student_number])) {
                    $quizAttempt = $attempts[$student->student_number]->firstWhere('quiz_id', $quiz->id);
                    if ($quizAttempt) {
                        $score = $quizAttempt->best_score;
                        $total = $quizAttempt->total_points;
                    }
                }

                $studentGrades['quizzes'][$quiz->id] = [
                    'score' => $score,
                    'total' => $total,
                    'percentage' => ($score !== null && $total > 0) ? round(($score / $total) * 100, 2) : null
                ];
            }

            $grades[] = $studentGrades;
        }

        return response()->json([
            'grades' => $grades,
            'quizzes' => $quizzes,
            'class' => $class
        ]);
    }

public function getStudentGrades($classId, $studentNumber)
{
    // Verify the class exists
    $class = DB::table('classes')->where('id', $classId)->first();
    
    if (!$class) {
        return response()->json(['error' => 'Class not found'], 404);
    }

    // Verify the student is enrolled in this class
    $isEnrolled = DB::table('student_class_matrix')
        ->where('student_number', $studentNumber)
        ->where('class_code', $class->class_code)
        ->exists();

    if (!$isEnrolled) {
        // Check if enrolled through section
        $student = DB::table('students')->where('student_number', $studentNumber)->first();
        
        if ($student && $student->section_id) {
            $isEnrolledThroughSection = DB::table('section_class_matrix')
                ->where('section_id', $student->section_id)
                ->where('class_id', $classId)
                ->exists();
            
            if (!$isEnrolledThroughSection) {
                return response()->json(['error' => 'Student not enrolled in this class'], 403);
            }
        } else {
            return response()->json(['error' => 'Student not enrolled in this class'], 403);
        }
    }

    // Get all quizzes for this class with their lessons
    $quizzes = DB::table('quizzes as q')
        ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
        ->where('l.class_id', $classId)
        ->where('q.status', 1)
        ->select(
            'q.id as quiz_id',
            'q.title as quiz_title',
            'q.passing_score',
            'l.id as lesson_id',
            'l.title as lesson_title',
            'l.order_number'
        )
        ->orderBy('l.order_number')
        ->orderBy('q.id')
        ->get();

    $grades = [];

    foreach ($quizzes as $quiz) {
        // Get the best attempt for this student on this quiz
        $bestAttempt = DB::table('student_quiz_attempts')
            ->where('student_number', $studentNumber)
            ->where('quiz_id', $quiz->quiz_id)
            ->where('status', 'graded')
            ->orderBy('score', 'desc')
            ->first();

        // Get total number of attempts
        $attemptCount = DB::table('student_quiz_attempts')
            ->where('student_number', $studentNumber)
            ->where('quiz_id', $quiz->quiz_id)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        $gradeData = [
            'quiz_id' => $quiz->quiz_id,
            'quiz_title' => $quiz->quiz_title,
            'lesson_id' => $quiz->lesson_id,
            'lesson_title' => $quiz->lesson_title,
            'passing_score' => (float) $quiz->passing_score,
            'attempt_count' => $attemptCount,
            'score' => null,
            'total_points' => null,
            'percentage' => null,
            'submitted_at' => null
        ];

        if ($bestAttempt) {
            $gradeData['score'] = (float) $bestAttempt->score;
            $gradeData['total_points'] = (float) $bestAttempt->total_points;
            
            if ($bestAttempt->total_points > 0) {
                $gradeData['percentage'] = ($bestAttempt->score / $bestAttempt->total_points) * 100;
            }
            
            $gradeData['submitted_at'] = $bestAttempt->submitted_at;
        }

        $grades[] = $gradeData;
    }

    return response()->json([
        'grades' => $grades,
        'student_number' => $studentNumber,
        'class' => [
            'id' => $class->id,
            'code' => $class->class_code,
            'name' => $class->class_name
        ]
    ]);
}
}

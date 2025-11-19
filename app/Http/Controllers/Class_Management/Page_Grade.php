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
        $class = DB::table('classes')->where('id', $classId)->first();

        $attempts = DB::table('student_quiz_attempts as sqa')
            ->join('quizzes as q', 'sqa.quiz_id', '=', 'q.id')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('sqa.student_number', $studentNumber)
            ->where('sqa.status', 'graded')
            ->select(
                'q.id as quiz_id',
                'q.title as quiz_title',
                'l.title as lesson_title',
                'q.passing_score',
                'sqa.score',
                'sqa.total_points',
                'sqa.submitted_at',
                'sqa.attempt_number',
                DB::raw('ROUND((sqa.score / sqa.total_points * 100), 2) as percentage')
            )
            ->orderBy('l.order_number')
            ->orderBy('sqa.submitted_at', 'desc')
            ->get()
            ->groupBy('quiz_id');

        $grades = [];
        foreach ($attempts as $quizId => $quizAttempts) {
            $best = $quizAttempts->sortByDesc('score')->first();
            $grades[] = [
                'quiz_id' => $best->quiz_id,
                'quiz_title' => $best->quiz_title,
                'lesson_title' => $best->lesson_title,
                'score' => floatval($best->score),
                'total_points' => floatval($best->total_points),
                'percentage' => floatval($best->percentage),
                'passing_score' => floatval($best->passing_score),
                'passed' => $best->percentage >= $best->passing_score,
                'attempt_count' => $quizAttempts->count(),
                'submitted_at' => $best->submitted_at
            ];
        }

        $totalScore = collect($grades)->sum('score');
        $totalPoints = collect($grades)->sum('total_points');
        $averagePercentage = $totalPoints > 0 ? round(($totalScore / $totalPoints) * 100, 2) : 0;

        return response()->json([
            'grades' => $grades,
            'summary' => [
                'total_score' => $totalScore,
                'total_points' => $totalPoints,
                'average_percentage' => $averagePercentage,
                'quiz_count' => count($grades),
            ],
            'class' => $class
        ]);
    }
}

<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;

class Page_Grade extends MainController
{
    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        return view('modules.class.page_gradebook', [
            'userType' => 'teacher',
            'class' => $class,
        ]);
    }

    public function studentIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        // Get student number from session
        $studentNumber = session('student_number');

        return view('modules.class.page_grade', [
            'userType' => 'student',
            'class' => $class,
            'studentNumber' => $studentNumber,
        ]);
    }

    public function getStudents($classId)
    {
        $students = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->join('classes as c', 'scm.class_code', '=', 'c.class_code')
            ->where('c.id', $classId)
            ->select(
                's.id',
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.gender'
            )
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
        
        // Get all students in class
        $students = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->join('classes as c', 'scm.class_code', '=', 'c.class_code')
            ->where('c.id', $classId)
            ->select('s.student_number', 's.first_name', 's.middle_name', 's.last_name')
            ->orderBy('s.last_name')
            ->get();

        // Get all quizzes for this class
        $quizzes = DB::table('quizzes as q')
            ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
            ->where('l.class_id', $classId)
            ->where('q.status', 1)
            ->select('q.id', 'q.title', 'l.title as lesson_title')
            ->orderBy('l.order_number')
            ->get();

        // Get quiz attempts for all students
        $attempts = DB::table('student_quiz_attempts as sqa')
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
            ->get()
            ->groupBy('student_number');

        // Build grade matrix
        $grades = [];
        foreach ($students as $student) {
            $studentGrades = [
                'student_number' => $student->student_number,
                'full_name' => $student->last_name . ', ' . $student->first_name . ' ' . $student->middle_name,
                'quizzes' => [],
                'ww_total' => 0,
                'pt_total' => 0,
                'qa_total' => 0,
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
        
        // Get student quiz attempts with details
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
                DB::raw('(sqa.score / sqa.total_points * 100) as percentage')
            )
            ->orderBy('l.order_number')
            ->orderBy('sqa.submitted_at', 'desc')
            ->get()
            ->groupBy('quiz_id');

        // Get best attempts
        $grades = [];
        foreach ($attempts as $quizId => $quizAttempts) {
            $best = $quizAttempts->sortByDesc('score')->first();
            $grades[] = [
                'quiz_id' => $best->quiz_id,
                'quiz_title' => $best->quiz_title,
                'lesson_title' => $best->lesson_title,
                'score' => $best->score,
                'total_points' => $best->total_points,
                'percentage' => round($best->percentage, 2),
                'passing_score' => $best->passing_score,
                'passed' => $best->percentage >= $best->passing_score,
                'attempt_count' => $quizAttempts->count(),
                'submitted_at' => $best->submitted_at
            ];
        }

        // Calculate summary
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

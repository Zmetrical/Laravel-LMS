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
    $pendingQuizzes = [];
    
    if (Auth::guard('student')->check()) {
        $student = Auth::guard('student')->user();
        
        // Get active semester
        $activeSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();
        
        if (!$activeSemester) {
            $view->with([
                'studentClasses' => [],
                'pendingQuizzes' => []
            ]);
            return;
        }
        
        // Session keys
        $classesSessionKey = 'student_classes_' . $student->id . '_sem_' . $activeSemester->id;
        $quizzesSessionKey = 'pending_quizzes_' . $student->id . '_sem_' . $activeSemester->id;
        
        // Get classes (existing logic)
        if (Session::has($classesSessionKey)) {
            $studentClasses = Session::get($classesSessionKey);
        } else {
            if ($student->student_type === 'regular' && $student->section_id) {
                $studentClasses = DB::table('section_class_matrix as scm')
                    ->join('classes as c', 'scm.class_id', '=', 'c.id')
                    ->where('scm.section_id', $student->section_id)
                    ->where('scm.semester_id', $activeSemester->id)
                    ->select('c.id', 'c.class_code', 'c.class_name')
                    ->orderBy('c.class_code')
                    ->get()
                    ->toArray();
            } else {
                $studentClasses = DB::table('student_class_matrix as scm')
                    ->join('classes as c', function($join) {
                        $join->on(
                            DB::raw('scm.class_code COLLATE utf8mb4_unicode_ci'),
                            '=',
                            DB::raw('c.class_code COLLATE utf8mb4_unicode_ci')
                        );
                    })
                    ->where('scm.student_number', $student->student_number)
                    ->where('scm.semester_id', $activeSemester->id)
                    ->where('scm.enrollment_status', 'enrolled')
                    ->select('c.id', 'c.class_code', 'c.class_name')
                    ->orderBy('c.class_code')
                    ->get()
                    ->toArray();
            }
            
            Session::put($classesSessionKey, $studentClasses);
        }
        
        // Get pending quizzes count (cached for 2 minutes for better real-time updates)
        if (Session::has($quizzesSessionKey)) {
            $pendingQuizzes = Session::get($quizzesSessionKey);
        } else {
            if (!empty($studentClasses)) {
                $classIds = array_column($studentClasses, 'id');
                $now = now();
                
                // Get all active and available quizzes
                $activeQuizzes = DB::table('quizzes as q')
                    ->join('lessons as l', 'q.lesson_id', '=', 'l.id')
                    ->whereIn('l.class_id', $classIds)
                    ->where('q.status', 1)
                    // Quiz must be currently available (started but not ended)
                    ->where(function($query) use ($now) {
                        $query->whereNull('q.available_from')
                              ->orWhere('q.available_from', '<=', $now);
                    })
                    ->where(function($query) use ($now) {
                        $query->whereNull('q.available_until')
                              ->orWhere('q.available_until', '>=', $now);
                    })
                    ->select(
                        'q.id', 
                        'l.class_id', 
                        'q.max_attempts',
                        'q.available_until',
                        'q.time_limit'
                    )
                    ->get();
                
                // Get student's attempts (only completed/submitted ones)
                $attempts = DB::table('student_quiz_attempts')
                    ->where('student_number', $student->student_number)
                    ->whereIn('quiz_id', $activeQuizzes->pluck('id'))
                    ->whereIn('status', ['submitted', 'graded']) // Only count finished attempts
                    ->select('quiz_id', DB::raw('COUNT(*) as attempt_count'))
                    ->groupBy('quiz_id')
                    ->get()
                    ->keyBy('quiz_id');
                
                // Count pending quizzes per class
                $pendingQuizzes = [];
                foreach ($activeQuizzes as $quiz) {
                    $attemptCount = $attempts->get($quiz->id)->attempt_count ?? 0;
                    
                    // Quiz is pending if:
                    // 1. Student hasn't attempted it at all, OR
                    // 2. Student has attempts left (attemptCount < max_attempts)
                    // Note: max_attempts = 0 or null means unlimited attempts
                    $hasAttemptsLeft = ($quiz->max_attempts == 0 || $quiz->max_attempts === null) 
                                       || ($attemptCount < $quiz->max_attempts);
                    
                    if ($attemptCount == 0 || $hasAttemptsLeft) {
                        if (!isset($pendingQuizzes[$quiz->class_id])) {
                            $pendingQuizzes[$quiz->class_id] = 0;
                        }
                        $pendingQuizzes[$quiz->class_id]++;
                    }
                }
                
                // Cache for 2 minutes (more frequent updates for time-sensitive quizzes)
                Session::put($quizzesSessionKey, $pendingQuizzes, now()->addMinutes(2));
            }
        }
    }
    
    $view->with([
        'studentClasses' => $studentClasses,
        'pendingQuizzes' => $pendingQuizzes
    ]);
}
}
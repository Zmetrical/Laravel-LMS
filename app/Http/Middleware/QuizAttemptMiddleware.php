<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class QuizAttemptMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $quizId = $request->route('quizId');
        
        // Only apply to student guard
        if (!Auth::guard('student')->check()) {
            return $next($request);
        }

        $studentNumber = Auth::guard('student')->user()->student_number;

        // Check for in-progress attempt
        $activeAttempt = DB::table('student_quiz_attempts as sqa')
            ->join('quizzes as q', 'sqa.quiz_id', '=', 'q.id')
            ->where('sqa.student_number', $studentNumber)
            ->where('sqa.quiz_id', $quizId)
            ->where('sqa.status', 'in_progress')
            ->select(
                'sqa.id',
                'sqa.quiz_id',
                'sqa.started_at',
                'sqa.attempt_number',
                'q.time_limit'
            )
            ->first();

        if ($activeAttempt) {
            // Check if attempt has expired based on time limit
            if ($activeAttempt->time_limit) {
                $startedAt = Carbon::parse($activeAttempt->started_at);
                $expiresAt = $startedAt->addMinutes($activeAttempt->time_limit);
                
                // If expired, auto-submit before proceeding
                if (Carbon::now()->greaterThan($expiresAt)) {
                    $this->autoSubmitExpiredAttempt($activeAttempt->id);
                    
                    // Clear from session
                    session()->forget(['active_quiz_attempt', 'active_quiz_id', 'quiz_timer_started']);
                    
                    // Redirect to quiz view with message
                    return redirect()
                        ->route('student.class.quiz.view', [
                            'classId' => $request->route('classId'),
                            'lessonId' => $request->route('lessonId'),
                            'quizId' => $quizId
                        ])
                        ->with('warning', 'Your previous attempt was automatically submitted due to time expiration.');
                }
            }

            // Store active attempt in session with server timestamp
            session([
                'active_quiz_attempt' => $activeAttempt->id,
                'active_quiz_id' => $quizId,
                'quiz_timer_started' => $activeAttempt->started_at,
                'quiz_time_limit' => $activeAttempt->time_limit
            ]);
        }

        return $next($request);
    }

    /**
     * Auto-submit an expired quiz attempt
     */
    private function autoSubmitExpiredAttempt($attemptId)
    {
        DB::beginTransaction();
        try {
            $attempt = DB::table('student_quiz_attempts')->where('id', $attemptId)->first();
            
            if (!$attempt) {
                DB::rollBack();
                return;
            }

            // Calculate score from saved answers
            $score = 0;
            $hasEssay = false;

            $savedAnswers = DB::table('student_quiz_answers as sa')
                ->join('quiz_questions as q', 'sa.question_id', '=', 'q.id')
                ->leftJoin('quiz_question_options as o', 'sa.option_id', '=', 'o.id')
                ->where('sa.attempt_id', $attemptId)
                ->select('sa.*', 'q.question_type', 'q.points', 'o.is_correct')
                ->get();

            foreach ($savedAnswers as $answer) {
                if ($answer->question_type === 'essay') {
                    $hasEssay = true;
                    DB::table('student_quiz_answers')
                        ->where('id', $answer->id)
                        ->update([
                            'is_correct' => null,
                            'points_earned' => 0,
                            'updated_at' => now()
                        ]);
                } else {
                    if ($answer->option_id && $answer->is_correct == 1) {
                        $score += $answer->points;
                        DB::table('student_quiz_answers')
                            ->where('id', $answer->id)
                            ->update([
                                'is_correct' => 1,
                                'points_earned' => $answer->points,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('student_quiz_answers')
                            ->where('id', $answer->id)
                            ->update([
                                'is_correct' => 0,
                                'points_earned' => 0,
                                'updated_at' => now()
                            ]);
                    }
                }
            }

            // Update attempt status
            DB::table('student_quiz_attempts')
                ->where('id', $attemptId)
                ->update([
                    'score' => $score,
                    'submitted_at' => now(),
                    'status' => $hasEssay ? 'submitted' : 'graded',
                    'updated_at' => now()
                ]);

            DB::commit();
            \Log::info("Auto-submitted expired quiz attempt: {$attemptId} with score: {$score}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to auto-submit expired attempt {$attemptId}: " . $e->getMessage());
        }
    }
}
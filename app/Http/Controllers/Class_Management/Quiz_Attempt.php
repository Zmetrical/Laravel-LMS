<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Quiz_Attempt extends MainController
{
    /**
     * Student views quiz details before attempting
     */
    public function viewQuiz($classId, $lessonId, $quizId)
    {
        $studentNumber = Auth::guard('student')->user()->student_number;
        
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')
            ->where('id', $lessonId)
            ->where('class_id', $classId)
            ->first();
        $quiz = DB::table('quizzes')
            ->where('id', $quizId)
            ->where('lesson_id', $lessonId)
            ->where('status', 1)
            ->first();

        if (!$class || !$lesson || !$quiz) {
            abort(404, 'Resource not found');
        }

        // Check for active in-progress attempt
        $activeAttempt = DB::table('student_quiz_attempts')
            ->where('student_number', $studentNumber)
            ->where('quiz_id', $quizId)
            ->where('status', 'in_progress')
            ->first();

        // Check if active attempt has expired
        if ($activeAttempt && $quiz->time_limit) {
            $startedAt = Carbon::parse($activeAttempt->started_at);
            $expiresAt = $startedAt->addMinutes($quiz->time_limit);
            
            if (Carbon::now()->greaterThan($expiresAt)) {
                // Auto-submit expired attempt
                $this->autoSubmitAttempt($activeAttempt->id);
                $activeAttempt = null;
            }
        }

        // If there's an active attempt, redirect to resume it
        if ($activeAttempt) {
            return redirect()->route('student.class.quiz.start', [
                'classId' => $classId,
                'lessonId' => $lessonId,
                'quizId' => $quizId
            ])->with('info', 'Resuming your active quiz attempt...');
        }

        // Get student's completed attempts
        $attempts = DB::table('student_quiz_attempts')
            ->where('student_number', $studentNumber)
            ->where('quiz_id', $quizId)
            ->whereIn('status', ['submitted', 'graded'])
            ->orderBy('attempt_number', 'desc')
            ->get();

        // Get question count
        $questionCount = DB::table('quiz_questions')
            ->where('quiz_id', $quizId)
            ->count();

        // Calculate total possible points
        $totalPoints = DB::table('quiz_questions')
            ->where('quiz_id', $quizId)
            ->sum('points');

        // Count total attempts (including in-progress)
        $totalAttempts = DB::table('student_quiz_attempts')
            ->where('student_number', $studentNumber)
            ->where('quiz_id', $quizId)
            ->count();

        $data = [
            'scripts' => ['class_quiz/student_view.js'],
            'userType' => 'student',
            'class' => $class,
            'lesson' => $lesson,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'questionCount' => $questionCount,
            'totalPoints' => $totalPoints,
            'canAttempt' => $totalAttempts < $quiz->max_attempts
        ];

        return view('modules.quiz.view_quiz', $data);
    }

    /**
     * Student starts or resumes quiz attempt
     */
    public function startQuiz($classId, $lessonId, $quizId)
    {
        $studentNumber = Auth::guard('student')->user()->student_number;
        
        // Debug: Log current timezone
        \Log::info('Quiz Start Debug:', [
            'config_timezone' => config('app.timezone'),
            'php_timezone' => date_default_timezone_get(),
            'carbon_now' => Carbon::now()->toDateTimeString(),
            'db_now' => DB::selectOne('SELECT NOW() as now')->now
        ]);
        
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')
            ->where('id', $lessonId)
            ->where('class_id', $classId)
            ->first();
        $quiz = DB::table('quizzes')
            ->where('id', $quizId)
            ->where('lesson_id', $lessonId)
            ->where('status', 1)
            ->first();

        if (!$class || !$lesson || !$quiz) {
            abort(404, 'Resource not found');
        }

        DB::beginTransaction();
        try {
            // Check for existing in-progress attempt
            $existingAttempt = DB::table('student_quiz_attempts')
                ->where('student_number', $studentNumber)
                ->where('quiz_id', $quizId)
                ->where('status', 'in_progress')
                ->orderBy('id', 'desc')
                ->first();

            $isResuming = false;
            $savedAnswers = [];
            $timerStartedAt = null;

            if ($existingAttempt) {
                // Check if expired
                if ($quiz->time_limit) {
                    $startedAt = Carbon::parse($existingAttempt->started_at);
                    $elapsedSeconds = Carbon::now()->diffInSeconds($startedAt);
                    $timeLimit = $quiz->time_limit * 60; // Convert to seconds
                    
                    if ($elapsedSeconds >= $timeLimit) {
                        // Auto-submit and don't allow resume
                        $this->autoSubmitAttempt($existingAttempt->id);
                        DB::rollBack();
                        
                        return redirect()->route('student.class.quiz.view', [
                            'classId' => $classId,
                            'lessonId' => $lessonId,
                            'quizId' => $quizId
                        ])->with('warning', 'Your previous attempt expired and was automatically submitted.');
                    }
                }

                // RESUME existing attempt
                $attemptId = $existingAttempt->id;
                $attemptNumber = $existingAttempt->attempt_number;
                $isResuming = true;
                
                // Calculate elapsed time
                $startedAt = Carbon::parse($existingAttempt->started_at);
                $elapsedSeconds = Carbon::now()->diffInSeconds($startedAt);

                // Load saved answers
                $savedAnswersData = DB::table('student_quiz_answers')
                    ->where('attempt_id', $attemptId)
                    ->get();

                    foreach ($savedAnswersData as $answer) {
                        $qId = $answer->question_id;
                        
                        // Get question type to determine how to structure saved answer
                        $qType = DB::table('quiz_questions')
                            ->where('id', $qId)
                            ->value('question_type');
                        
                        if ($qType === 'multiple_answer') {
                            // For multiple answer, collect all option_ids
                            if (!isset($savedAnswers[$qId])) {
                                $savedAnswers[$qId] = ['option_ids' => []];
                            }
                            if ($answer->option_id) {
                                $savedAnswers[$qId]['option_ids'][] = $answer->option_id;
                            }
                        } else {
                            $savedAnswers[$qId] = [
                                'option_id' => $answer->option_id,
                                'answer_text' => $answer->answer_text
                            ];
                        }
                    }

            } else {
                // Check max attempts
                $attemptCount = DB::table('student_quiz_attempts')
                    ->where('student_number', $studentNumber)
                    ->where('quiz_id', $quizId)
                    ->count();

                if ($attemptCount >= $quiz->max_attempts) {
                    DB::rollBack();
                    return redirect()->route('student.class.quiz.view', [
                        'classId' => $classId,
                        'lessonId' => $lessonId,
                        'quizId' => $quizId
                    ])->with('error', 'Maximum attempts reached');
                }

                // Create NEW attempt
                $totalPoints = DB::table('quiz_questions')
                    ->where('quiz_id', $quizId)
                    ->sum('points');

                $attemptId = DB::table('student_quiz_attempts')->insertGetId([
                    'student_number' => $studentNumber,
                    'quiz_id' => $quizId,
                    'attempt_number' => $attemptCount + 1,
                    'total_points' => $totalPoints,
                    'started_at' => now(),
                    'status' => 'in_progress',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $attemptNumber = $attemptCount + 1;
                $elapsedSeconds = 0;
            }

            // Use attempt ID as seed for consistent randomization
            $seed = $attemptId;

            // Get questions with consistent order
            if ($quiz->shuffle_questions) {
                $questions = DB::table('quiz_questions')
                    ->where('quiz_id', $quizId)
                    ->orderBy('order_number', 'asc')
                    ->get()
                    ->shuffle($seed);
            } else {
                $questions = DB::table('quiz_questions')
                    ->where('quiz_id', $quizId)
                    ->orderBy('order_number', 'asc')
                    ->get();
            }

            $questionsData = [];
            foreach ($questions as $question) {
                $questionData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'saved_answer' => $savedAnswers[$question->id] ?? null
                ];

                if ($question->question_type !== 'essay') {
                    $options = DB::table('quiz_question_options')
                        ->where('question_id', $question->id)
                        ->orderBy('order_number', 'asc')
                        ->select('id', 'option_text', 'order_number')
                        ->get();

                    // Randomize options using same seed
                    if ($quiz->shuffle_questions) {
                        $optionSeed = $seed + $question->id;
                        $options = $options->shuffle($optionSeed);
                    }

                    $questionData['options'] = $options->values()->all();
                }

                $questionsData[] = $questionData;
            }

            DB::commit();

            // Recalculate elapsed time to ensure consistency
            if ($quiz->time_limit && $isResuming) {
                $attempt = DB::table('student_quiz_attempts')
                    ->where('id', $attemptId)
                    ->first();
                    
                $startedAt = Carbon::parse($attempt->started_at);
                $now = Carbon::now();
                
                // Get absolute difference in seconds (started_at should be in the past)
                $elapsedSeconds = abs($startedAt->diffInSeconds($now));
                
                // Ensure we have a valid positive number
                $elapsedSeconds = max(0, $elapsedSeconds);
            }

            $data = [
                'scripts' => ['class_quiz/student_view_active.js'],
                'userType' => 'student',
                'class' => $class,
                'lesson' => $lesson,
                'quiz' => $quiz,
                'questions' => $questionsData,
                'attemptNumber' => $attemptNumber,
                'attemptId' => $attemptId,
                'isResuming' => $isResuming,
                'savedAnswers' => $savedAnswers,
                'elapsedSeconds' => $elapsedSeconds
            ];

            return view('modules.quiz.view_quiz_active', $data);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('student.class.quiz.view', [
                'classId' => $classId,
                'lessonId' => $lessonId,
                'quizId' => $quizId
            ])->with('error', 'Failed to start quiz: ' . $e->getMessage());
        }
    }

    /**
     * Save progress (auto-save) - called periodically via AJAX
     */
    public function saveProgress(Request $request, $classId, $lessonId, $quizId)
    {
        $studentNumber = Auth::guard('student')->user()->student_number;
        
        try {
            $attemptId = $request->input('attempt_id');
            $answers = $request->input('answers', []);

            // Verify attempt belongs to student and is in progress
            $attempt = DB::table('student_quiz_attempts as sqa')
                ->join('quizzes as q', 'sqa.quiz_id', '=', 'q.id')
                ->where('sqa.id', $attemptId)
                ->where('sqa.student_number', $studentNumber)
                ->where('sqa.quiz_id', $quizId)
                ->where('sqa.status', 'in_progress')
                ->select('sqa.*', 'q.time_limit')
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid attempt or already submitted'
                ], 403);
            }

            // Check if time expired
            if ($attempt->time_limit) {
                $startedAt = Carbon::parse($attempt->started_at);
                $expiresAt = $startedAt->addMinutes($attempt->time_limit);
                
                if (Carbon::now()->greaterThan($expiresAt)) {
                    // Auto-submit
                    $this->autoSubmitAttempt($attemptId);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Time expired',
                        'time_expired' => true
                    ], 410);
                }
            }

            // Update last activity timestamp
            DB::table('student_quiz_attempts')
                ->where('id', $attemptId)
                ->update(['updated_at' => now()]);

            // Save/update answers
            foreach ($answers as $questionId => $answerData) {
                $existing = DB::table('student_quiz_answers')
                    ->where('attempt_id', $attemptId)
                    ->where('question_id', $questionId)
                    ->first();

                $data = [
                    'attempt_id' => $attemptId,
                    'question_id' => $questionId,
                    'updated_at' => now()
                ];

                if (isset($answerData['option_id'])) {
                    $data['option_id'] = $answerData['option_id'];
                }
                if (isset($answerData['answer_text'])) {
                    $data['answer_text'] = $answerData['answer_text'];
                }

                if ($existing) {
                    DB::table('student_quiz_answers')
                        ->where('id', $existing->id)
                        ->update($data);
                } else {
                    $data['created_at'] = now();
                    DB::table('student_quiz_answers')->insert($data);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Progress saved',
                'server_time' => now()->timestamp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save progress'
            ], 500);
        }
    }

    /**
     * Heartbeat to keep session alive
     */
    public function heartbeat(Request $request, $classId, $lessonId, $quizId)
    {
        $studentNumber = Auth::guard('student')->user()->student_number;
        
        try {
            $attemptId = $request->input('attempt_id');

            // Verify attempt
            $attempt = DB::table('student_quiz_attempts as sqa')
                ->join('quizzes as q', 'sqa.quiz_id', '=', 'q.id')
                ->where('sqa.id', $attemptId)
                ->where('sqa.student_number', $studentNumber)
                ->where('sqa.quiz_id', $quizId)
                ->where('sqa.status', 'in_progress')
                ->select('sqa.*', 'q.time_limit')
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt not found or already submitted'
                ], 404);
            }

            // Check time expiration
            if ($attempt->time_limit) {
                $startedAt = Carbon::parse($attempt->started_at);
                $expiresAt = $startedAt->addMinutes($attempt->time_limit);
                $timeRemaining = Carbon::now()->diffInSeconds($expiresAt, false);
                
                if ($timeRemaining <= 0) {
                    return response()->json([
                        'success' => false,
                        'time_expired' => true,
                        'message' => 'Time expired'
                    ], 410);
                }
            }

            // Update timestamp
            DB::table('student_quiz_attempts')
                ->where('id', $attemptId)
                ->update(['updated_at' => now()]);

            return response()->json([
                'success' => true,
                'server_time' => now()->timestamp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Heartbeat failed'
            ], 500);
        }
    }

    /**
     * Auto-submit an attempt (helper method)
     */
    private function autoSubmitAttempt($attemptId)
    {
        DB::beginTransaction();
        try {
            $attempt = DB::table('student_quiz_attempts')->where('id', $attemptId)->first();
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                DB::rollBack();
                return;
            }

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

            DB::table('student_quiz_attempts')
                ->where('id', $attemptId)
                ->update([
                    'score' => $score,
                    'submitted_at' => now(),
                    'status' => $hasEssay ? 'submitted' : 'graded',
                    'updated_at' => now()
                ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to auto-submit attempt {$attemptId}: " . $e->getMessage());
        }
    }
}
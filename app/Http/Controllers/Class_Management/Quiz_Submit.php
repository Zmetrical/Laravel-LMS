<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class Quiz_Submit extends MainController
{
    use AuditLogger;

    /**
     * Submit quiz attempt
     */
    public function submitQuiz(Request $request, $classId, $lessonId, $quizId)
    {
        $student = Auth::guard('student')->user();
        $studentNumber = $student->student_number;
        
        DB::beginTransaction();
        try {
            $quiz = DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->first();

            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], 404);
            }

            // Get the active attempt
            $attempt = DB::table('student_quiz_attempts')
                ->where('student_number', $studentNumber)
                ->where('quiz_id', $quizId)
                ->where('status', 'in_progress')
                ->orderBy('id', 'desc')
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active quiz attempt found'
                ], 403);
            }
            
            $quarterId = $quiz->quarter_id;
            $semesterId = $quiz->semester_id;
            
            // Check time expiration
            if ($quiz->time_limit) {
                $startedAt = Carbon::parse($attempt->started_at);
                $expiresAt = $startedAt->addMinutes($quiz->time_limit);
                
                if (Carbon::now()->greaterThan($expiresAt)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Time expired',
                        'time_expired' => true
                    ], 410);
                }
            }

            // Verify questions belong to this quiz
            $quizQuestionIds = DB::table('quiz_questions')
                ->where('quiz_id', $quizId)
                ->pluck('id')
                ->toArray();

            $answers = $request->input('answers', []);
            foreach ($answers as $answer) {
                if (!in_array($answer['question_id'], $quizQuestionIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid question ID detected'
                    ], 400);
                }
            }

            $score = 0;
            $hasEssay = false;

            // Process final answers
            foreach ($answers as $answer) {
                $question = DB::table('quiz_questions')
                    ->where('id', $answer['question_id'])
                    ->where('quiz_id', $quizId)
                    ->first();

                if (!$question) continue;

                if ($question->question_type === 'essay') {
                    // Essay - manual grading
                    $answerData = [
                        'attempt_id' => $attempt->id,
                        'question_id' => $answer['question_id'],
                        'answer_text' => $answer['answer_text'] ?? '',
                        'is_correct' => null,
                        'points_earned' => 0,
                        'updated_at' => now(),
                        'created_at' => now()
                    ];
                    DB::table('student_quiz_answers')->insert($answerData);
                    $hasEssay = true;
                    
                } else if ($question->question_type === 'short_answer') {
                    // Short answer - check against accepted answers
                    $userAnswer = trim(strtolower($answer['answer_text'] ?? ''));
                    $acceptedAnswers = DB::table('quiz_short_answers')
                        ->where('question_id', $question->id)
                        ->pluck('answer_text')
                        ->toArray();
                    
                    $isCorrect = false;
                    if ($question->exact_match) {
                        // Exact match required
                        $isCorrect = in_array($userAnswer, array_map('strtolower', array_map('trim', $acceptedAnswers)));
                    } else {
                        // Partial match allowed
                        foreach ($acceptedAnswers as $accepted) {
                            if (stripos($userAnswer, trim($accepted)) !== false) {
                                $isCorrect = true;
                                break;
                            }
                        }
                    }
                    
                    $pointsEarned = $isCorrect ? $question->points : 0;
                    $score += $pointsEarned;
                    
                    $answerData = [
                        'attempt_id' => $attempt->id,
                        'question_id' => $answer['question_id'],
                        'answer_text' => $answer['answer_text'] ?? '',
                        'is_correct' => $isCorrect ? 1 : 0,
                        'points_earned' => $pointsEarned,
                        'updated_at' => now(),
                        'created_at' => now()
                    ];
                    DB::table('student_quiz_answers')->insert($answerData);
                    
                } else if ($question->question_type === 'multiple_answer') {
                    // Multiple answer - all correct options must be selected
                    $selectedOptions = $answer['option_ids'] ?? [];
                    
                    $allOptions = DB::table('quiz_question_options')
                        ->where('question_id', $question->id)
                        ->get();
                    
                    $correctOptionIds = $allOptions->where('is_correct', 1)->pluck('id')->toArray();
                    
                    // Check if selected matches correct (same elements, order doesn't matter)
                    sort($selectedOptions);
                    sort($correctOptionIds);
                    $isCorrect = $selectedOptions === $correctOptionIds;
                    
                    $pointsEarned = $isCorrect ? $question->points : 0;
                    $score += $pointsEarned;
                    
                    // Insert each selected option as separate row
                    foreach ($selectedOptions as $optionId) {
                        DB::table('student_quiz_answers')->insert([
                            'attempt_id' => $attempt->id,
                            'question_id' => $answer['question_id'],
                            'option_id' => $optionId,
                            'is_correct' => $isCorrect ? 1 : 0,
                            'points_earned' => $isCorrect ? $question->points : 0,
                            'updated_at' => now(),
                            'created_at' => now()
                        ]);
                    }
                    
                    // If nothing selected, still record attempt
                    if (empty($selectedOptions)) {
                        DB::table('student_quiz_answers')->insert([
                            'attempt_id' => $attempt->id,
                            'question_id' => $answer['question_id'],
                            'is_correct' => 0,
                            'points_earned' => 0,
                            'updated_at' => now(),
                            'created_at' => now()
                        ]);
                    }
                    
                } else {
                    // Multiple choice or True/False
                    $optionId = $answer['option_id'] ?? null;
                    
                    if ($optionId) {
                        $option = DB::table('quiz_question_options')
                            ->where('id', $optionId)
                            ->where('question_id', $question->id)
                            ->first();

                        if ($option) {
                            $isCorrect = $option->is_correct == 1;
                            $pointsEarned = $isCorrect ? $question->points : 0;
                            $score += $pointsEarned;
                            
                            DB::table('student_quiz_answers')->insert([
                                'attempt_id' => $attempt->id,
                                'question_id' => $answer['question_id'],
                                'option_id' => $optionId,
                                'is_correct' => $isCorrect ? 1 : 0,
                                'points_earned' => $pointsEarned,
                                'updated_at' => now(),
                                'created_at' => now()
                            ]);
                        }
                    }
                }
            }

            // Mark attempt as submitted/graded
            DB::table('student_quiz_attempts')
                ->where('id', $attempt->id)
                ->update([
                    'score' => $score,
                    'semester_id' => $semesterId,
                    'quarter_id' => $quarterId,
                    'submitted_at' => now(),
                    'status' => $hasEssay ? 'submitted' : 'graded',
                    'updated_at' => now()
                ]);

            // **INVALIDATE CACHE** - Force sidebar badge to update
            $activeSemester = DB::table('semesters')
                ->where('status', 'active')
                ->first();
            
            if ($activeSemester) {
                $quizzesSessionKey = 'pending_quizzes_' . $student->id . '_sem_' . $activeSemester->id;
                Session::forget($quizzesSessionKey);
            }

            // Get related data for audit log
            $class = DB::table('classes')->where('id', $classId)->first();
            $lesson = DB::table('lessons')->where('id', $lessonId)->first();
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $semesterId)
                ->select('semesters.*', 'school_years.code as sy_code')
                ->first();
            $quarter = DB::table('quarters')->where('id', $quarterId)->first();

            $percentage = $attempt->total_points > 0 ? round(($score / $attempt->total_points) * 100, 2) : 0;

            // Audit Log - EXPLICITLY pass student user type
            $this->logAudit(
                'submitted',
                'quizzes',
                (string)$quizId,
                "Submitted quiz '{$quiz->title}' for lesson '{$lesson->title}' in class '{$class->class_name}' - {$quarter->name} {$semester->name} {$semester->sy_code}",
                null,
                [
                    'attempt_id' => $attempt->id,
                    'quiz_id' => $quizId,
                    'quiz_title' => $quiz->title,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'semester_id' => $semesterId,
                    'semester_name' => $semester->name,
                    'quarter_id' => $quarterId,
                    'quarter_name' => $quarter->name,
                    'school_year' => $semester->sy_code,
                    'attempt_number' => $attempt->attempt_number,
                    'score' => $score,
                    'total_points' => $attempt->total_points,
                    'percentage' => $percentage,
                    'status' => $hasEssay ? 'submitted' : 'graded',
                    'has_essay' => $hasEssay,
                    'total_answers' => count($answers),
                    'submitted_at' => now()->toDateTimeString()
                ],
                'student',  // EXPLICIT user type
                $studentNumber  // EXPLICIT user identifier
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $hasEssay 
                    ? 'Quiz submitted. Essay questions require manual grading.' 
                    : 'Quiz submitted successfully',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'score' => $score,
                    'total_points' => $attempt->total_points,
                    'percentage' => $percentage,
                    'has_essay' => $hasEssay
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quiz: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;

class Page_Quiz extends MainController
{
    public function teacherIndex($classId)
    {
        // Fetch class info
        $class = DB::table('classes')->where('id', $classId)->first();

        // If not found, you can handle it gracefully
        if (!$class) {
            abort(404, 'Class not found');
        }

        // Return the view
        return view('modules.class.page_quiz', [
            'userType' => 'teacher',
            'class' => $class,
        ]);
    }


    /**
     * Display quiz creation page for teacher
     */
    public function teacherCreate($classId, $lessonId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')
            ->where('id', $lessonId)
            ->where('class_id', $classId)
            ->first();

        if (!$class || !$lesson) {
            abort(404, 'Class or lesson not found');
        }

        $data = [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'isEdit' => false
        ];

        return view('modules.class.create_quiz', $data);
    }

    /**
     * Display quiz edit page for teacher
     */
    public function teacherEdit($classId, $lessonId, $quizId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')
            ->where('id', $lessonId)
            ->where('class_id', $classId)
            ->first();
        $quiz = DB::table('quizzes')
            ->where('id', $quizId)
            ->where('lesson_id', $lessonId)
            ->first();

        if (!$class || !$lesson || !$quiz) {
            abort(404, 'Resource not found');
        }

        $data = [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'quiz' => $quiz,
            'isEdit' => true
        ];

        return view('modules.class.create_quiz', $data);
    }

    /**
     * Get quiz data with questions for editing
     */
    public function getQuizData($classId, $lessonId, $quizId)
    {
        try {
            $quiz = DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->first();

            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], 404);
            }

            // Get questions
            $questions = DB::table('quiz_questions')
                ->where('quiz_id', $quizId)
                ->orderBy('order_number', 'asc')
                ->get();

            $questionsData = [];
            foreach ($questions as $question) {
                $questionData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'order_number' => $question->order_number
                ];

                // Get options for multiple choice and true/false
                if ($question->question_type === 'multiple_choice' || $question->question_type === 'true_false') {
                    $options = DB::table('quiz_question_options')
                        ->where('question_id', $question->id)
                        ->orderBy('order_number', 'asc')
                        ->get();
                    $questionData['options'] = $options;
                }

                $questionsData[] = $questionData;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'quiz' => $quiz,
                    'questions' => $questionsData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new quiz with questions
     */
    public function store(Request $request, $classId, $lessonId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'time_limit' => 'nullable|integer|min:1',
                'passing_score' => 'required|numeric|min:0|max:100',
                'max_attempts' => 'required|integer|min:1',
                'show_results' => 'required|boolean',
                'shuffle_questions' => 'required|boolean',
                'questions' => 'required|array|min:1',
                'questions.*.question_text' => 'required|string',
                'questions.*.question_type' => 'required|in:multiple_choice,true_false,essay',
                'questions.*.points' => 'required|numeric|min:0.01'
            ]);

            // Create quiz
            $quizId = DB::table('quizzes')->insertGetId([
                'lesson_id' => $lessonId,
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'passing_score' => $request->passing_score,
                'max_attempts' => $request->max_attempts,
                'show_results' => $request->show_results,
                'shuffle_questions' => $request->shuffle_questions,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create questions
            foreach ($request->questions as $index => $questionData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'points' => $questionData['points'],
                    'order_number' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create options for multiple choice and true/false
                if (isset($questionData['options']) && is_array($questionData['options'])) {
                    foreach ($questionData['options'] as $optIndex => $option) {
                        DB::table('quiz_question_options')->insert([
                            'question_id' => $questionId,
                            'option_text' => $option['text'],
                            'is_correct' => $option['is_correct'] ?? 0,
                            'order_number' => $optIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quiz created successfully',
                'data' => ['quiz_id' => $quizId]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing quiz
     */
    public function update(Request $request, $classId, $lessonId, $quizId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'time_limit' => 'nullable|integer|min:1',
                'passing_score' => 'required|numeric|min:0|max:100',
                'max_attempts' => 'required|integer|min:1',
                'show_results' => 'required|boolean',
                'shuffle_questions' => 'required|boolean',
                'questions' => 'required|array|min:1'
            ]);

            // Update quiz
            DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'time_limit' => $request->time_limit,
                    'passing_score' => $request->passing_score,
                    'max_attempts' => $request->max_attempts,
                    'show_results' => $request->show_results,
                    'shuffle_questions' => $request->shuffle_questions,
                    'updated_at' => now()
                ]);

            // Delete existing questions and options
            $questionIds = DB::table('quiz_questions')
                ->where('quiz_id', $quizId)
                ->pluck('id');

            if ($questionIds->count() > 0) {
                DB::table('quiz_question_options')
                    ->whereIn('question_id', $questionIds)
                    ->delete();
            }

            DB::table('quiz_questions')
                ->where('quiz_id', $quizId)
                ->delete();

            // Create new questions
            foreach ($request->questions as $index => $questionData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'points' => $questionData['points'],
                    'order_number' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if (isset($questionData['options']) && is_array($questionData['options'])) {
                    foreach ($questionData['options'] as $optIndex => $option) {
                        DB::table('quiz_question_options')->insert([
                            'question_id' => $questionId,
                            'option_text' => $option['text'],
                            'is_correct' => $option['is_correct'] ?? 0,
                            'order_number' => $optIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quiz updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete quiz
     */
    public function destroy($classId, $lessonId, $quizId)
    {
        try {
            DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Quiz deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete quiz: ' . $e->getMessage()
            ], 500);
        }
    }
}

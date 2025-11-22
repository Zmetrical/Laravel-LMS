<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Page_Quiz extends MainController
{
    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        if (!$class) abort(404, 'Class not found');

        return view('modules.quiz.page_quiz', [
            'userType' => 'teacher',
            'class' => $class,
        ]);
    }

    public function teacherCreate($classId, $lessonId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')->where('id', $lessonId)->where('class_id', $classId)->first();

        if (!$class || !$lesson) abort(404, 'Class or lesson not found');

        return view('modules.quiz.create_quiz', [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'isEdit' => false
        ]);
    }

    public function teacherEdit($classId, $lessonId, $quizId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')->where('id', $lessonId)->where('class_id', $classId)->first();
        $quiz = DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->first();

        if (!$class || !$lesson || !$quiz) abort(404, 'Resource not found');

        return view('modules.quiz.create_quiz', [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'quiz' => $quiz,
            'isEdit' => true
        ]);
    }

    public function getQuizData($classId, $lessonId, $quizId)
    {
        try {
            $quiz = DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->first();
            if (!$quiz) return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);

            $questions = DB::table('quiz_questions')->where('quiz_id', $quizId)->orderBy('order_number')->get();

            $questionsData = [];
            foreach ($questions as $question) {
                $qData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'order_number' => $question->order_number
                ];

                // Get options for MC, MA, TF
                if (in_array($question->question_type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    $qData['options'] = DB::table('quiz_question_options')
                        ->where('question_id', $question->id)
                        ->orderBy('order_number')
                        ->get();
                }

                // Get accepted answers for short_answer
                if ($question->question_type === 'short_answer') {
                    $answers = DB::table('quiz_short_answers')
                        ->where('question_id', $question->id)
                        ->pluck('answer_text')
                        ->toArray();
                    $qData['accepted_answers'] = $answers;
                    $qData['exact_match'] = (bool) $question->exact_match;
                }

                $questionsData[] = $qData;
            }

            return response()->json([
                'success' => true,
                'data' => ['quiz' => $quiz, 'questions' => $questionsData]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

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
                'questions.*.question_type' => 'required|in:multiple_choice,multiple_answer,true_false,short_answer,essay',
                'questions.*.points' => 'required|numeric|min:0.01'
            ]);

            // Validate questions
            foreach ($request->questions as $i => $q) {
                $type = $q['question_type'];
                
                // Validate options for MC, MA, TF
                if (in_array($type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    if (!isset($q['options']) || count($q['options']) < 2) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least 2 options");
                    }
                    if (count($q['options']) > 10) {
                        throw new \Exception("Question " . ($i + 1) . " cannot have more than 10 options");
                    }
                    
                    // Check for duplicates
                    $optTexts = array_map(fn($o) => strtolower(trim($o['text'])), $q['options']);
                    if (count($optTexts) !== count(array_unique($optTexts))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate options");
                    }
                    
                    // Check for correct answer
                    $hasCorrect = array_filter($q['options'], fn($o) => $o['is_correct'] ?? false);
                    if (empty($hasCorrect)) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least one correct answer");
                    }
                }
                
                // Validate short answer
                if ($type === 'short_answer') {
                    if (!isset($q['accepted_answers']) || count($q['accepted_answers']) < 1) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least one accepted answer");
                    }
                    
                    // Check for duplicates
                    $answers = array_map(fn($a) => strtolower(trim($a)), $q['accepted_answers']);
                    if (count($answers) !== count(array_unique($answers))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate accepted answers");
                    }
                }
            }

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
            foreach ($request->questions as $index => $qData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question_text' => $qData['question_text'],
                    'question_type' => $qData['question_type'],
                    'points' => $qData['points'],
                    'exact_match' => $qData['exact_match'] ?? true,
                    'order_number' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create options for MC, MA, TF
                if (isset($qData['options']) && is_array($qData['options'])) {
                    foreach ($qData['options'] as $optIndex => $opt) {
                        DB::table('quiz_question_options')->insert([
                            'question_id' => $questionId,
                            'option_text' => $opt['text'],
                            'is_correct' => $opt['is_correct'] ?? 0,
                            'order_number' => $optIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                // Create accepted answers for short_answer
                if ($qData['question_type'] === 'short_answer' && isset($qData['accepted_answers'])) {
                    foreach ($qData['accepted_answers'] as $answer) {
                        DB::table('quiz_short_answers')->insert([
                            'question_id' => $questionId,
                            'answer_text' => trim($answer),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Quiz created successfully', 'data' => ['quiz_id' => $quizId]]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $classId, $lessonId, $quizId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'questions' => 'required|array|min:1'
            ]);

            // Same validation as store
            foreach ($request->questions as $i => $q) {
                $type = $q['question_type'];
                
                if (in_array($type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    if (!isset($q['options']) || count($q['options']) < 2) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least 2 options");
                    }
                    if (count($q['options']) > 10) {
                        throw new \Exception("Question " . ($i + 1) . " cannot have more than 10 options");
                    }
                    
                    $optTexts = array_map(fn($o) => strtolower(trim($o['text'])), $q['options']);
                    if (count($optTexts) !== count(array_unique($optTexts))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate options");
                    }
                    
                    $hasCorrect = array_filter($q['options'], fn($o) => $o['is_correct'] ?? false);
                    if (empty($hasCorrect)) {
                        throw new \Exception("Question " . ($i + 1) . " must have a correct answer");
                    }
                }
                
                if ($type === 'short_answer') {
                    if (!isset($q['accepted_answers']) || count($q['accepted_answers']) < 1) {
                        throw new \Exception("Question " . ($i + 1) . " needs at least one answer");
                    }
                    
                    $answers = array_map(fn($a) => strtolower(trim($a)), $q['accepted_answers']);
                    if (count($answers) !== count(array_unique($answers))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate answers");
                    }
                }
            }

            // Update quiz
            DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->update([
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'passing_score' => $request->passing_score,
                'max_attempts' => $request->max_attempts,
                'show_results' => $request->show_results,
                'shuffle_questions' => $request->shuffle_questions,
                'updated_at' => now()
            ]);

            // Delete old questions
            $qIds = DB::table('quiz_questions')->where('quiz_id', $quizId)->pluck('id');
            if ($qIds->count() > 0) {
                DB::table('quiz_question_options')->whereIn('question_id', $qIds)->delete();
                DB::table('quiz_short_answers')->whereIn('question_id', $qIds)->delete();
            }
            DB::table('quiz_questions')->where('quiz_id', $quizId)->delete();

            // Create new questions
            foreach ($request->questions as $index => $qData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question_text' => $qData['question_text'],
                    'question_type' => $qData['question_type'],
                    'points' => $qData['points'],
                    'exact_match' => $qData['exact_match'] ?? true,
                    'order_number' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if (isset($qData['options'])) {
                    foreach ($qData['options'] as $optIndex => $opt) {
                        DB::table('quiz_question_options')->insert([
                            'question_id' => $questionId,
                            'option_text' => $opt['text'],
                            'is_correct' => $opt['is_correct'] ?? 0,
                            'order_number' => $optIndex + 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                if ($qData['question_type'] === 'short_answer' && isset($qData['accepted_answers'])) {
                    foreach ($qData['accepted_answers'] as $answer) {
                        DB::table('quiz_short_answers')->insert([
                            'question_id' => $questionId,
                            'answer_text' => trim($answer),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Quiz updated successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($classId, $lessonId, $quizId)
    {
        try {
            DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->update([
                'status' => 0,
                'updated_at' => now()
            ]);
            return response()->json(['success' => true, 'message' => 'Quiz deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
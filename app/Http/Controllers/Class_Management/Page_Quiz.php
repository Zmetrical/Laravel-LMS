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
        $lesson = DB::table('lessons')
            ->select('lessons.*', 'classes.class_code')
            ->join('classes', 'lessons.class_id', '=', 'classes.id')
            ->where('lessons.id', $lessonId)
            ->where('lessons.class_id', $classId)
            ->first();

        if (!$class || !$lesson) abort(404, 'Class or lesson not found');

        // Get the active semester and its quarters
        $currentSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        if (!$currentSemester) {
            return back()->with('error', 'No active semester found. Please activate a semester first.');
        }

        $quarters = DB::table('quarters')
            ->where('semester_id', $currentSemester->id)
            ->orderBy('order_number')
            ->get();

        // Try to get quarter from lesson's existing quizzes
        $lessonQuarter = DB::table('quizzes')
            ->where('lesson_id', $lessonId)
            ->whereNotNull('quarter_id')
            ->value('quarter_id');

        // Default to first quarter if no existing quiz
        $defaultQuarterId = $lessonQuarter ?? ($quarters->first()->id ?? null);

        return view('modules.quiz.create_quiz', [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'quarters' => $quarters,
            'semesterId' => $currentSemester->id,
            'defaultQuarterId' => $defaultQuarterId,
            'isEdit' => false
        ]);
    }

    public function teacherEdit($classId, $lessonId, $quizId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')->where('id', $lessonId)->where('class_id', $classId)->first();
        $quiz = DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->first();

        if (!$class || !$lesson || !$quiz) abort(404, 'Resource not found');

        // Get semester from quiz or current active semester
        $semesterId = $quiz->semester_id;
        if (!$semesterId) {
            $semesterId = DB::table('semesters')->where('status', 'active')->value('id');
        }

        $quarters = DB::table('quarters')
            ->where('semester_id', $semesterId)
            ->orderBy('order_number')
            ->get();

        return view('modules.quiz.create_quiz', [
            'scripts' => ['class_quiz/teacher_quiz.js'],
            'userType' => 'teacher',
            'class' => $class,
            'lesson' => $lesson,
            'quiz' => $quiz,
            'quarters' => $quarters,
            'semesterId' => $semesterId,
            'defaultQuarterId' => $quiz->quarter_id,
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

                if (in_array($question->question_type, ['multiple_choice', 'multiple_answer', 'true_false'])) {
                    $qData['options'] = DB::table('quiz_question_options')
                        ->where('question_id', $question->id)
                        ->orderBy('order_number')
                        ->get();
                }

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
                'available_from' => 'nullable|date',
                'available_until' => 'nullable|date|after_or_equal:available_from',
                'passing_score' => 'required|numeric|min:0|max:100',
                'max_attempts' => 'required|integer|min:1|max:5',
                'quarter_id' => 'required|integer|exists:quarters,id',
                'semester_id' => 'required|integer|exists:semesters,id',
                'questions' => 'required|array|min:1',
                'questions.*.question_text' => 'required|string',
                'questions.*.question_type' => 'required|in:multiple_choice,multiple_answer,true_false,short_answer',
                'questions.*.points' => 'required|numeric|min:0.01'
            ]);

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
                        throw new \Exception("Question " . ($i + 1) . " must have at least one correct answer");
                    }
                }
                
                if ($type === 'short_answer') {
                    if (!isset($q['accepted_answers']) || count($q['accepted_answers']) < 1) {
                        throw new \Exception("Question " . ($i + 1) . " must have at least one accepted answer");
                    }
                    
                    $answers = array_map(fn($a) => strtolower(trim($a)), $q['accepted_answers']);
                    if (count($answers) !== count(array_unique($answers))) {
                        throw new \Exception("Question " . ($i + 1) . " has duplicate accepted answers");
                    }
                }
            }

            $quizId = DB::table('quizzes')->insertGetId([
                'lesson_id' => $lessonId,
                'semester_id' => $request->semester_id,
                'quarter_id' => $request->quarter_id,
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'available_from' => $request->available_from,
                'available_until' => $request->available_until,
                'passing_score' => $request->passing_score,
                'max_attempts' => $request->max_attempts,
                'show_results' => 1,
                'shuffle_questions' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

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
                'quarter_id' => 'required|integer|exists:quarters,id',
                'semester_id' => 'required|integer|exists:semesters,id',
                'available_from' => 'nullable|date',
                'available_until' => 'nullable|date|after_or_equal:available_from',
                'questions' => 'required|array|min:1'
            ]);

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

            DB::table('quizzes')->where('id', $quizId)->where('lesson_id', $lessonId)->update([
                'title' => $request->title,
                'description' => $request->description,
                'time_limit' => $request->time_limit,
                'available_from' => $request->available_from,
                'available_until' => $request->available_until,
                'passing_score' => $request->passing_score,
                'max_attempts' => $request->max_attempts,
                'semester_id' => $request->semester_id,
                'quarter_id' => $request->quarter_id,
                'updated_at' => now()
            ]);

            $qIds = DB::table('quiz_questions')->where('quiz_id', $quizId)->pluck('id');
            if ($qIds->count() > 0) {
                DB::table('quiz_question_options')->whereIn('question_id', $qIds)->delete();
                DB::table('quiz_short_answers')->whereIn('question_id', $qIds)->delete();
            }
            DB::table('quiz_questions')->where('quiz_id', $quizId)->delete();

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

    public function checkAvailability($classId, $lessonId, $quizId)
    {
        try {
            $quiz = DB::table('quizzes')
                ->where('id', $quizId)
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->first();

            if (!$quiz) {
                return response()->json(['available' => false, 'message' => 'Quiz not found'], 404);
            }

            $now = now();
            $isAvailable = true;
            $message = '';

            if ($quiz->available_from && $now->lt($quiz->available_from)) {
                $isAvailable = false;
                $message = 'Quiz will be available from ' . date('F j, Y g:i A', strtotime($quiz->available_from));
            } elseif ($quiz->available_until && $now->gt($quiz->available_until)) {
                $isAvailable = false;
                $message = 'Quiz closed on ' . date('F j, Y g:i A', strtotime($quiz->available_until));
            }

            return response()->json([
                'available' => $isAvailable,
                'message' => $message,
                'available_from' => $quiz->available_from,
                'available_until' => $quiz->available_until
            ]);
        } catch (\Exception $e) {
            return response()->json(['available' => false, 'message' => 'Error checking availability'], 500);
        }
    }
}
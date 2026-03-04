<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Quiz_Preview extends MainController{
    public function storePreview(Request $request, $classId, $lessonId)
{
    $request->validate([
        'title'         => 'required|string|max:255',
        'questions'     => 'required|array|min:1',
        'time_limit'    => 'nullable|integer|min:0',
        'passing_score' => 'required|integer|min:0|max:100',
        'max_questions' => 'nullable|integer|min:1',
    ]);

    session(['quiz_preview' => [
        'title'         => $request->title,
        'time_limit'    => $request->time_limit ?: null,
        'passing_score' => $request->passing_score,
        'max_questions' => $request->max_questions,
        'questions'     => $request->questions,
        'back_url'      => url()->previous(),
        'class_id'      => $classId,
        'lesson_id'     => $lessonId,
    ]]);

    return response()->json([
        'success'  => true,
        'redirect' => route('teacher.class.quiz.preview.show', [
            'classId'  => $classId,
            'lessonId' => $lessonId
        ])
    ]);
}

public function showPreview($classId, $lessonId)
{
    $preview = session('quiz_preview');

    if (!$preview || $preview['class_id'] != $classId || $preview['lesson_id'] != $lessonId) {
        return redirect()->back()->with('error', 'No preview data found. Please try again.');
    }

    $class  = DB::table('classes')->where('id', $classId)->first();
    $lesson = DB::table('lessons')->where('id', $lessonId)->first();

    if (!$class || !$lesson) abort(404);

    $questions = $preview['questions'];

    // Apply max_questions — shuffle so it's truly random per preview
// Always shuffle (mirrors shuffle_questions = 1 in student quiz)
shuffle($questions);

// Then slice if max_questions is set
if (!empty($preview['max_questions']) && $preview['max_questions'] < count($questions)) {
    $questions = array_slice($questions, 0, $preview['max_questions']);
}
foreach ($questions as &$question) {
    if (in_array($question['question_type'], ['multiple_choice', 'multiple_answer'])) {
        shuffle($question['options']);
    }
}
unset($question); // break reference
    return view('modules.quiz.preview_quiz', [
        'userType' => 'teacher',
        'class'    => $class,
        'lesson'   => $lesson,
        'preview'  => $preview,
        'questions' => $questions,
        'backUrl'  => $preview['back_url'],
        'scripts'  => ['class_quiz/teacher_preview_quiz.js'],
    ]);
}
}
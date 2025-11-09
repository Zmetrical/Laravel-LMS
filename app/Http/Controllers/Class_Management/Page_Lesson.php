<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;

class Page_Lesson extends MainController
{
    /**
     * Display lessons page for teacher
     */
    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        $data = [
            'scripts' => ['class/teacher_lesson.js'],
            'userType' => 'teacher',
            'class' => $class
        ];

        return view('modules.class.page_lesson', $data);
    }

    /**
     * Display lessons page for student
     */
    public function studentIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        // TODO: Verify student is enrolled in this class
        $data = [
            'scripts' => ['class/student_lesson.js'],
            'userType' => 'student',
            'class' => $class
        ];
        return view('modules.class.page_lesson', $data);
    }

    /**
     * Get all lessons for a class (Teacher)
     */
    public function teacherList($classId)
    {
        try {
            $lessons = DB::table('lessons')
                ->where('class_id', $classId)
                ->where('status', 1)
                ->orderBy('order_number', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            $lessonsData = [];

            foreach ($lessons as $lesson) {
                // Get lectures
                $lectures = DB::table('lectures')
                    ->where('lesson_id', $lesson->id)
                    ->where('status', 1)
                    ->orderBy('order_number', 'asc')
                    ->select('id', 'title', 'content_type', 'order_number')
                    ->get();

                // Get quizzes
                $quizzes = DB::table('quizzes')
                    ->where('lesson_id', $lesson->id)
                    ->where('status', 1)
                    ->select('id', 'title', 'time_limit', 'passing_score')
                    ->get();

                $lessonsData[] = [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'order_number' => $lesson->order_number,
                    'lectures' => $lectures,
                    'quizzes' => $quizzes
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $lessonsData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lessons: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all lessons for a class (Student)
     */
    public function studentList($classId)
    {
        try {
            // TODO: Verify student enrollment

            $lessons = DB::table('lessons')
                ->where('class_id', $classId)
                ->where('status', 1)
                ->orderBy('order_number', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            $lessonsData = [];

            foreach ($lessons as $lesson) {
                // Get lectures
                $lectures = DB::table('lectures')
                    ->where('lesson_id', $lesson->id)
                    ->where('status', 1)
                    ->orderBy('order_number', 'asc')
                    ->select('id', 'title', 'content_type', 'order_number')
                    ->get();

                // Get quizzes
                $quizzes = DB::table('quizzes')
                    ->where('lesson_id', $lesson->id)
                    ->where('status', 1)
                    ->select('id', 'title', 'time_limit', 'passing_score', 'max_attempts')
                    ->get();

                $lessonsData[] = [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'order_number' => $lesson->order_number,
                    'lectures' => $lectures,
                    'quizzes' => $quizzes
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $lessonsData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lessons: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new lesson (Teacher only)
     */
    public function store(Request $request, $classId)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            // Get the next order number
            $maxOrder = DB::table('lessons')
                ->where('class_id', $classId)
                ->max('order_number');

            $lessonId = DB::table('lessons')->insertGetId([
                'class_id' => $classId,
                'title' => $request->title,
                'description' => $request->description,
                'order_number' => ($maxOrder ?? 0) + 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => [
                    'id' => $lessonId
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson (Teacher only)
     */
    public function update(Request $request, $classId, $lessonId)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            // Verify lesson belongs to class
            $lesson = DB::table('lessons')
                ->where('id', $lessonId)
                ->where('class_id', $classId)
                ->first();

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            DB::table('lessons')
                ->where('id', $lessonId)
                ->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lesson (Teacher only)
     */
    public function destroy($classId, $lessonId)
    {
        try {
            // Verify lesson belongs to class
            $lesson = DB::table('lessons')
                ->where('id', $lessonId)
                ->where('class_id', $classId)
                ->first();

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            // Soft delete by setting status to 0
            DB::table('lessons')
                ->where('id', $lessonId)
                ->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson: ' . $e->getMessage()
            ], 500);
        }
    }
}

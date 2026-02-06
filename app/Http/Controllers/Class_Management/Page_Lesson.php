<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Page_Lesson extends MainController
{
    use AuditLogger;

    /**
     * Display lessons page for teacher
     */
    public function teacherIndex($classId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        // Get quarters for the current semester
        $quarters = DB::table('quarters')
            ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
            ->where('semesters.status', 'active')
            ->orderBy('quarters.order_number', 'asc')
            ->select('quarters.*')
            ->get();

        $data = [
            'scripts' => ['class/teacher_lesson.js'],
            'userType' => 'teacher',
            'class' => $class,
            'quarters' => $quarters
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

        // Get quarters for the current semester
        $quarters = DB::table('quarters')
            ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
            ->where('semesters.status', 'active')
            ->orderBy('quarters.order_number', 'asc')
            ->select('quarters.*')
            ->get();

        $data = [
            'scripts' => ['class/student_lesson.js'],
            'userType' => 'student',
            'class' => $class,
            'quarters' => $quarters
        ];
        
        return view('modules.class.page_lesson', $data);
    }

    /**
     * Get all lessons for a class grouped by quarters (Teacher)
     */
    public function teacherList($classId)
    {
        try {
            // Get active quarters
            $quarters = DB::table('quarters')
                ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
                ->where('semesters.status', 'active')
                ->orderBy('quarters.order_number', 'asc')
                ->select('quarters.*')
                ->get();

            $quartersData = [];

            foreach ($quarters as $quarter) {
                // Get lessons for this quarter
                $lessons = DB::table('lessons')
                    ->where('class_id', $classId)
                    ->where('quarter_id', $quarter->id)
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

                $quartersData[] = [
                    'id' => $quarter->id,
                    'name' => $quarter->name,
                    'code' => $quarter->code,
                    'lessons' => $lessonsData
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $quartersData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lessons: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all lessons for a class grouped by quarters (Student)
     */
    public function studentList($classId)
    {
        try {
            // Get active quarters
            $quarters = DB::table('quarters')
                ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
                ->where('semesters.status', 'active')
                ->orderBy('quarters.order_number', 'asc')
                ->select('quarters.*')
                ->get();

            $quartersData = [];

            foreach ($quarters as $quarter) {
                // Get lessons for this quarter
                $lessons = DB::table('lessons')
                    ->where('class_id', $classId)
                    ->where('quarter_id', $quarter->id)
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

                $quartersData[] = [
                    'id' => $quarter->id,
                    'name' => $quarter->name,
                    'code' => $quarter->code,
                    'lessons' => $lessonsData
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $quartersData
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
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'quarter_id' => 'required|integer|exists:quarters,id'
            ]);

            // Get related data for audit log
            $class = DB::table('classes')->where('id', $classId)->first();
            
            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            $quarter = DB::table('quarters')
                ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('quarters.id', $request->quarter_id)
                ->select('quarters.*', 'semesters.name as semester_name', 'school_years.code as sy_code')
                ->first();

            // Get the next order number for this quarter
            $maxOrder = DB::table('lessons')
                ->where('class_id', $classId)
                ->where('quarter_id', $request->quarter_id)
                ->max('order_number');

            $lessonId = DB::table('lessons')->insertGetId([
                'class_id' => $classId,
                'quarter_id' => $request->quarter_id,
                'title' => $request->title,
                'description' => $request->description,
                'order_number' => ($maxOrder ?? 0) + 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Audit Log
            $this->logAudit(
                'created',
                'lessons',
                (string)$lessonId,
                "Created lesson '{$request->title}' in class '{$class->class_name}' - {$quarter->name} {$quarter->semester_name} {$quarter->sy_code}",
                null,
                [
                    'lesson_id' => $lessonId,
                    'lesson_title' => $request->title,
                    'description' => $request->description,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'quarter_id' => $request->quarter_id,
                    'quarter_name' => $quarter->name,
                    'semester_name' => $quarter->semester_name,
                    'school_year' => $quarter->sy_code,
                    'order_number' => ($maxOrder ?? 0) + 1
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => [
                    'id' => $lessonId
                ]
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
                'message' => 'Failed to create lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson (Teacher only)
     */
    public function update(Request $request, $classId, $lessonId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'quarter_id' => 'required|integer|exists:quarters,id'
            ]);

            // Get old lesson data for audit log
            $oldLesson = DB::table('lessons')
                ->where('id', $lessonId)
                ->where('class_id', $classId)
                ->first();

            if (!$oldLesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            // Get related data for audit log
            $class = DB::table('classes')->where('id', $classId)->first();
            
            $oldQuarter = DB::table('quarters')
                ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('quarters.id', $oldLesson->quarter_id)
                ->select('quarters.*', 'semesters.name as semester_name', 'school_years.code as sy_code')
                ->first();

            $newQuarter = DB::table('quarters')
                ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('quarters.id', $request->quarter_id)
                ->select('quarters.*', 'semesters.name as semester_name', 'school_years.code as sy_code')
                ->first();

            // Count lectures and quizzes
            $lectureCount = DB::table('lectures')
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->count();

            $quizCount = DB::table('quizzes')
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->count();

            // Update lesson
            DB::table('lessons')
                ->where('id', $lessonId)
                ->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'quarter_id' => $request->quarter_id,
                    'updated_at' => now()
                ]);

            // Audit Log
            $this->logAudit(
                'updated',
                'lessons',
                (string)$lessonId,
                "Updated lesson '{$request->title}' in class '{$class->class_name}' - {$newQuarter->name} {$newQuarter->semester_name} {$newQuarter->sy_code}",
                [
                    'lesson_title' => $oldLesson->title,
                    'description' => $oldLesson->description,
                    'quarter_id' => $oldLesson->quarter_id,
                    'quarter_name' => $oldQuarter->name,
                    'semester_name' => $oldQuarter->semester_name,
                    'school_year' => $oldQuarter->sy_code,
                    'lecture_count' => $lectureCount,
                    'quiz_count' => $quizCount
                ],
                [
                    'lesson_title' => $request->title,
                    'description' => $request->description,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'quarter_id' => $request->quarter_id,
                    'quarter_name' => $newQuarter->name,
                    'semester_name' => $newQuarter->semester_name,
                    'school_year' => $newQuarter->sy_code,
                    'lecture_count' => $lectureCount,
                    'quiz_count' => $quizCount
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully'
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
                'message' => 'Failed to update lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lesson (Teacher only)
     */
    public function destroy($classId, $lessonId)
    {
        DB::beginTransaction();
        try {
            // Get lesson data before deletion for audit log
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

            // Get related data for audit log
            $class = DB::table('classes')->where('id', $classId)->first();
            
            $quarter = DB::table('quarters')
                ->join('semesters', 'quarters.semester_id', '=', 'semesters.id')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('quarters.id', $lesson->quarter_id)
                ->select('quarters.*', 'semesters.name as semester_name', 'school_years.code as sy_code')
                ->first();

            // Count lectures and quizzes
            $lectureCount = DB::table('lectures')
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->count();

            $quizCount = DB::table('quizzes')
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->count();

            // Soft delete by setting status to 0
            DB::table('lessons')
                ->where('id', $lessonId)
                ->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);

            // Audit Log
            $this->logAudit(
                'deleted',
                'lessons',
                (string)$lessonId,
                "Deleted lesson '{$lesson->title}' from class '{$class->class_name}' - {$quarter->name} {$quarter->semester_name} {$quarter->sy_code}",
                [
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'description' => $lesson->description,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'quarter_id' => $lesson->quarter_id,
                    'quarter_name' => $quarter->name,
                    'semester_name' => $quarter->semester_name,
                    'school_year' => $quarter->sy_code,
                    'lecture_count' => $lectureCount,
                    'quiz_count' => $quizCount,
                    'status' => 1
                ],
                [
                    'status' => 0
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class Page_Lecture extends MainController
{
    use AuditLogger;

    /**
     * Show create lecture form
     */
    public function create($classId, $lessonId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        $lesson = DB::table('lessons')->where('id', $lessonId)->where('class_id', $classId)->first();

        if (!$class || !$lesson) {
            abort(404, 'Class or Lesson not found');
        }

        return view('modules.lecture.create_lecture', [
            'class' => $class,
            'lesson' => $lesson,
            'scripts' => ['class_lecture\teacher_lecture.js']
        ]);
    }

    /**
     * Store new lecture
     */
    public function store(Request $request, $classId, $lessonId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:text,video,pdf,file',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
            'order_number' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify lesson exists and belongs to class
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

            // Get class info for audit log
            $class = DB::table('classes')->where('id', $classId)->first();

            $data = [
                'lesson_id' => $lessonId,
                'title' => $request->title,
                'content_type' => $request->content_type,
                'order_number' => $request->order_number ?? 0,
                'status' => $request->status ?? 1,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Handle content based on type
            if ($request->content_type === 'text' || $request->content_type === 'video') {
                $data['content'] = $request->content;
            }

            // Handle file upload
            $fileName = null;
            if ($request->hasFile('file') && ($request->content_type === 'pdf' || $request->content_type === 'file')) {
                $file = $request->file('file');

                // Generate safe filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '', pathinfo($originalName, PATHINFO_FILENAME));
                $fileName = $safeName . '.' . $extension;

                // Store with custom name
                $path = $file->storeAs('lectures', $fileName, 'public');
                $data['file_path'] = $path;
            }

            $lectureId = DB::table('lectures')->insertGetId($data);

            // Audit Log
            $this->logAudit(
                'created',
                'lectures',
                (string)$lectureId,
                "Created lecture '{$request->title}' for lesson '{$lesson->title}' in class '{$class->class_name}'",
                null,
                [
                    'lecture_id' => $lectureId,
                    'lecture_title' => $request->title,
                    'content_type' => $request->content_type,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'order_number' => $data['order_number'],
                    'has_file' => $fileName ? true : false,
                    'file_name' => $fileName,
                    'status' => $data['status']
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lecture created successfully',
                'data' => [
                    'id' => $lectureId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lecture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show edit lecture form
     */
    public function edit($classId, $lessonId, $lectureId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $lecture = DB::table('lectures')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->where('lectures.id', $lectureId)
            ->where('lessons.id', $lessonId)
            ->where('lessons.class_id', $classId)
            ->select('lectures.*')
            ->first();

        if (!$class || !$lecture) {
            abort(404, 'Class or Lecture not found');
        }

        $lesson = DB::table('lessons')->where('id', $lessonId)->first();

        return view('modules.lecture.create_lecture', [
            'class' => $class,
            'lesson' => $lesson,
            'lecture' => $lecture,
            'scripts' => ['class_lecture\teacher_lecture.js']
        ]);
    }

    /**
     * Update lecture
     */
    public function update(Request $request, $classId, $lessonId, $lectureId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:text,video,pdf,file',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
            'order_number' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify lecture exists and belongs to lesson and class
            $lecture = DB::table('lectures')
                ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
                ->where('lectures.id', $lectureId)
                ->where('lessons.id', $lessonId)
                ->where('lessons.class_id', $classId)
                ->select('lectures.*')
                ->first();

            if (!$lecture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecture not found'
                ], 404);
            }

            // Get lesson and class info for audit log
            $lesson = DB::table('lessons')->where('id', $lessonId)->first();
            $class = DB::table('classes')->where('id', $classId)->first();

            // Store old values for audit
            $oldValues = [
                'title' => $lecture->title,
                'content_type' => $lecture->content_type,
                'order_number' => $lecture->order_number,
                'status' => $lecture->status,
                'has_file' => $lecture->file_path ? true : false,
                'file_name' => $lecture->file_path ? basename($lecture->file_path) : null
            ];

            $data = [
                'title' => $request->title,
                'content_type' => $request->content_type,
                'order_number' => $request->order_number ?? 0,
                'status' => $request->status ?? 1,
                'updated_at' => now()
            ];

            // Handle content based on type
            if ($request->content_type === 'text' || $request->content_type === 'video') {
                $data['content'] = $request->content;
                // Clear file_path if changing from file type to text/video
                if ($lecture->content_type === 'pdf' || $lecture->content_type === 'file') {
                    $data['file_path'] = null;
                    // Delete old file
                    if ($lecture->file_path && Storage::disk('public')->exists($lecture->file_path)) {
                        Storage::disk('public')->delete($lecture->file_path);
                    }
                }
            }

            // Handle file upload
            $fileName = null;
            if ($request->hasFile('file') && ($request->content_type === 'pdf' || $request->content_type === 'file')) {
                // Delete old file if exists
                if ($lecture->file_path && Storage::disk('public')->exists($lecture->file_path)) {
                    Storage::disk('public')->delete($lecture->file_path);
                }

                $file = $request->file('file');

                // Generate safe filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '', pathinfo($originalName, PATHINFO_FILENAME));
                $fileName = $safeName . '.' . $extension;

                // Store with custom name
                $path = $file->storeAs('lectures', $fileName, 'public');
                $data['file_path'] = $path;
                $data['content'] = null;
            }

            DB::table('lectures')
                ->where('id', $lectureId)
                ->update($data);

            // New values for audit
            $newValues = [
                'title' => $data['title'],
                'content_type' => $data['content_type'],
                'order_number' => $data['order_number'],
                'status' => $data['status'],
                'has_file' => $fileName || ($lecture->file_path && !isset($data['file_path'])) ? true : false,
                'file_name' => $fileName ?? ($lecture->file_path && !isset($data['file_path']) ? basename($lecture->file_path) : null)
            ];

            // Audit Log
            $this->logAudit(
                'updated',
                'lectures',
                (string)$lectureId,
                "Updated lecture '{$request->title}' in lesson '{$lesson->title}' for class '{$class->class_name}'",
                $oldValues,
                array_merge($newValues, [
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Lecture updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lecture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete lecture (set status to 0)
     */
    public function destroy($classId, $lessonId, $lectureId)
    {
        try {
            // Verify lecture exists and belongs to lesson and class
            $lecture = DB::table('lectures')
                ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
                ->where('lectures.id', $lectureId)
                ->where('lessons.id', $lessonId)
                ->where('lessons.class_id', $classId)
                ->select('lectures.*')
                ->first();

            if (!$lecture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecture not found'
                ], 404);
            }

            // Get lesson and class info for audit log
            $lesson = DB::table('lessons')->where('id', $lessonId)->first();
            $class = DB::table('classes')->where('id', $classId)->first();

            // Soft delete: set status to 0
            DB::table('lectures')
                ->where('id', $lectureId)
                ->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);

            // Audit Log
            $this->logAudit(
                'deleted',
                'lectures',
                (string)$lectureId,
                "Deleted lecture '{$lecture->title}' from lesson '{$lesson->title}' in class '{$class->class_name}'",
                [
                    'lecture_id' => $lectureId,
                    'lecture_title' => $lecture->title,
                    'content_type' => $lecture->content_type,
                    'status' => 1
                ],
                [
                    'status' => 0,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lesson->title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lecture deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lecture: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($filename)
    {
        $path = 'lectures/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $filePath = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return Response::download($filePath, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    public function stream($filename)
    {
        $path = 'lectures/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return Response::make($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    /**
     * Show lecture view for student
     */
    public function view($classId, $lessonId, $lectureId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        if (!$class) {
            abort(404, 'Class not found');
        }

        $lecture = DB::table('lectures')
            ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
            ->where('lectures.id', $lectureId)
            ->where('lessons.id', $lessonId)
            ->where('lessons.class_id', $classId)
            ->where('lectures.status', 1)
            ->where('lessons.status', 1)
            ->select(
                'lectures.*',
                'lessons.title as lesson_title',
                'lessons.description as lesson_description',
                'lessons.class_id'
            )
            ->first();

        if (!$lecture) {
            abort(404, 'Lecture not found or not available');
        }

        $allLectures = DB::table('lectures')
            ->where('lesson_id', $lessonId)
            ->where('status', 1)
            ->orderBy('order_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $currentIndex = $allLectures->search(function ($item) use ($lectureId) {
            return $item->id == $lectureId;
        });

        $previousLecture = $currentIndex > 0 ? $allLectures[$currentIndex - 1] : null;
        $nextLecture = $currentIndex < $allLectures->count() - 1 ? $allLectures[$currentIndex + 1] : null;

        return view('modules.lecture.view_lecture', [
            'class' => $class,
            'lessonId' => $lessonId,
            'lectureId' => $lectureId,
            'lecture' => $lecture,
            'allLectures' => $allLectures,
            'previousLecture' => $previousLecture,
            'nextLecture' => $nextLecture,
            'currentIndex' => $currentIndex + 1,
            'totalLectures' => $allLectures->count(),
            'userType' => 'student',
            'scripts' => ['class_lecture/student_lecture.js']
        ]);
    }

    public function getContent($classId, $lessonId, $lectureId)
    {
        try {
            $lecture = DB::table('lectures')
                ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
                ->where('lectures.id', $lectureId)
                ->where('lessons.id', $lessonId)
                ->where('lessons.class_id', $classId)
                ->where('lectures.status', 1)
                ->where('lessons.status', 1)
                ->select('lectures.*')
                ->first();

            if (!$lecture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecture not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $lecture->id,
                    'title' => $lecture->title,
                    'content_type' => $lecture->content_type,
                    'content' => $lecture->content,
                    'file_path' => $lecture->file_path,
                    'file_name' => $lecture->file_path ? basename($lecture->file_path) : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load lecture: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getData($classId, $lessonId, $lectureId)
    {
        try {
            $class = DB::table('classes')->where('id', $classId)->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found'
                ], 404);
            }

            $lecture = DB::table('lectures')
                ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
                ->where('lectures.id', $lectureId)
                ->where('lessons.id', $lessonId)
                ->where('lessons.class_id', $classId)
                ->where('lectures.status', 1)
                ->where('lessons.status', 1)
                ->select(
                    'lectures.*',
                    'lessons.title as lesson_title',
                    'lessons.description as lesson_description'
                )
                ->first();

            if (!$lecture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecture not found or not available'
                ], 404);
            }

            $allLectures = DB::table('lectures')
                ->where('lesson_id', $lessonId)
                ->where('status', 1)
                ->orderBy('order_number', 'asc')
                ->orderBy('created_at', 'asc')
                ->select('id', 'title', 'content_type', 'order_number')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'lecture_id' => $lecture->id,
                    'lesson_id' => $lessonId,
                    'title' => $lecture->title,
                    'lesson_title' => $lecture->lesson_title,
                    'content_type' => $lecture->content_type,
                    'content' => $lecture->content,
                    'file_path' => $lecture->file_path,
                    'file_name' => $lecture->file_path ? basename($lecture->file_path) : null,
                    'all_lectures' => $allLectures
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load lecture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark lecture as complete (Student)
     */
    public function markAsComplete(Request $request, $classId, $lessonId, $lectureId)
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Verify lecture exists and is active
            $lecture = DB::table('lectures')
                ->join('lessons', 'lectures.lesson_id', '=', 'lessons.id')
                ->where('lectures.id', $lectureId)
                ->where('lessons.id', $lessonId)
                ->where('lessons.class_id', $classId)
                ->where('lectures.status', 1)
                ->where('lessons.status', 1)
                ->select('lectures.*', 'lessons.title as lesson_title')
                ->first();

            if (!$lecture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lecture not found'
                ], 404);
            }

            // Get class info for audit log
            $class = DB::table('classes')->where('id', $classId)->first();

            // Check if already exists
            $existing = DB::table('student_lecture_progress')
                ->where('student_number', $student->student_number)
                ->where('lecture_id', $lectureId)
                ->first();

            if ($existing) {
                // Update existing record
                DB::table('student_lecture_progress')
                    ->where('id', $existing->id)
                    ->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                        'updated_at' => now()
                    ]);
            } else {
                // Create new record
                DB::table('student_lecture_progress')->insert([
                    'student_number' => $student->student_number,
                    'lecture_id' => $lectureId,
                    'lesson_id' => $lessonId,
                    'class_id' => $classId,
                    'is_completed' => true,
                    'completed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Audit Log
            $this->logAudit(
                'completed',
                'student_lecture_progress',
                "{$student->student_number}_{$lectureId}",
                "Student {$student->student_number} completed lecture '{$lecture->title}' in lesson '{$lecture->lesson_title}' for class '{$class->class_name}'",
                null,
                [
                    'student_number' => $student->student_number,
                    'student_name' => "{$student->first_name} {$student->last_name}",
                    'lecture_id' => $lectureId,
                    'lecture_title' => $lecture->title,
                    'lesson_id' => $lessonId,
                    'lesson_title' => $lecture->lesson_title,
                    'class_id' => $classId,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'is_completed' => true,
                    'completed_at' => now()->toDateTimeString()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lecture marked as complete'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark lecture as complete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lecture progress for student
     */
    public function getProgress($classId, $lessonId, $lectureId)
    {
        try {
            $student = Auth::guard('student')->user();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $progress = DB::table('student_lecture_progress')
                ->where('student_number', $student->student_number)
                ->where('lecture_id', $lectureId)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_completed' => $progress ? (bool)$progress->is_completed : false,
                    'completed_at' => $progress ? $progress->completed_at : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress: ' . $e->getMessage()
            ], 500);
        }
    }
}
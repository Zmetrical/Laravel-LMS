<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class Lecture extends MainController
{
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

        return view('modules.class.lecture', [
            'class' => $class,
            'lesson' => $lesson,
            'scripts' => ['class\teacher_lecture.js']
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
            'file' => 'nullable|file|max:10240', // 10MB max
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

        return view('modules.class.lecture', [
            'class' => $class,
            'lesson' => $lesson,
            'lecture' => $lecture,

            'scripts' => ['class\teacher_lecture.js']

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
                $data['content'] = null; // Clear text content when uploading file
            }

            DB::table('lectures')
                ->where('id', $lectureId)
                ->update($data);

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

            // Soft delete: set status to 0
            DB::table('lectures')
                ->where('id', $lectureId)
                ->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);

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

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        // Get file path
        $filePath = Storage::disk('public')->path($path);

        // Get mime type
        $mimeType = Storage::disk('public')->mimeType($path);

        // Return file response
        return Response::download($filePath, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    // Alternative: Stream in browser instead of download
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
}

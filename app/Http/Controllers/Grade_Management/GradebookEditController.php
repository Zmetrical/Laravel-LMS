<?php

namespace App\Http\Controllers\Grade_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GradebookEditController extends MainController
{
    /**
     * Verify passcode before allowing edit access
     */
    public function verify_passcode(Request $request, $classId)
    {
        $request->validate([
            'passcode' => 'required'
        ]);

        $teacher = Auth::guard('teacher')->user();
        
        $plainPasscode = DB::table('teacher_password_matrix')
            ->where('teacher_id', $teacher->id)
            ->value('plain_passcode');

        if ($request->passcode === $plainPasscode) {
            session([
                'gradebook_passcode_verified_' . $classId => true,
                'gradebook_passcode_time_' . $classId => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Passcode verified successfully',
                'redirect' => route('teacher.gradebook.edit', ['classId' => $classId])
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid passcode. Please try again.'
        ], 401);
    }

    /**
     * Edit gradebook page
     */
    public function edit_gradebook($classId) 
    {
        $teacher = Auth::guard('teacher')->user();
        
        $hasAccess = DB::table('teacher_class_matrix')
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Unauthorized access to this class');
        }

        // Check passcode verification
        $sessionKey = 'gradebook_passcode_verified_' . $classId;
        $timeKey = 'gradebook_passcode_time_' . $classId;
        
        if (!session($sessionKey) || 
            !session($timeKey) || 
            now()->diffInMinutes(session($timeKey)) > 5) {
            session()->forget([$sessionKey, $timeKey]);
            return redirect()->route('teacher.gradebook.view', ['classId' => $classId])
                ->with('error', 'Please verify your passcode to access edit mode');
        }

        // Clear the session after successful access
        session()->forget([$sessionKey, $timeKey]);

        $class = DB::table('classes')->where('id', $classId)->first();
        
        $quarters = DB::table('quarters as q')
            ->join('semesters as s', 'q.semester_id', '=', 's.id')
            ->where('s.status', 'active')
            ->orderBy('q.order_number')
            ->select('q.*')
            ->get();

        $sections = DB::table('sections as sec')
            ->join('section_class_matrix as scm', 'sec.id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->select('sec.id', 'sec.name', 'sec.code')
            ->get();

        $data = [
            'scripts' => ['gradebook/edit_gradebook.js'],
            'classId' => $classId,
            'class' => $class,
            'quarters' => $quarters,
            'sections' => $sections
        ];

        return view('teacher.gradebook.edit_gradebook', $data);
    }

    /**
     * Enable/disable a column
     */
    public function toggleColumn(Request $request, $classId, $columnId)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
                'max_points' => 'nullable|integer|min:1',
                'quiz_id' => 'nullable|exists:quizzes,id'
            ]);

            $column = DB::table('gradebook_columns')->where('id', $columnId)->first();

            if (!$column) {
                return response()->json(['success' => false, 'message' => 'Column not found'], 404);
            }

            $updateData = [
                'is_active' => $validated['is_active'],
                'updated_at' => now()
            ];

            if ($validated['is_active']) {
                if (isset($validated['max_points'])) {
                    $updateData['max_points'] = $validated['max_points'];
                }
                
                if (isset($validated['quiz_id']) && $validated['quiz_id']) {
                    $updateData['quiz_id'] = $validated['quiz_id'];
                    $updateData['source_type'] = 'online';
                } else {
                    $updateData['quiz_id'] = null;
                    $updateData['source_type'] = 'manual';
                }
            }

            DB::table('gradebook_columns')
                ->where('id', $columnId)
                ->update($updateData);

            return response()->json([
                'success' => true,
                'message' => $validated['is_active'] ? 'Column enabled successfully' : 'Column disabled successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update column: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update column configuration
     */
    public function updateColumn(Request $request, $classId, $columnId)
    {
        try {
            $validated = $request->validate([
                'max_points' => 'required|integer|min:1',
                'quiz_id' => 'nullable|exists:quizzes,id'
            ]);

            $column = DB::table('gradebook_columns')->where('id', $columnId)->first();

            $updateData = [
                'max_points' => $validated['max_points'],
                'updated_at' => now()
            ];

            if (isset($validated['quiz_id']) && $validated['quiz_id']) {
                $updateData['quiz_id'] = $validated['quiz_id'];
                $updateData['source_type'] = 'online';
            } else {
                $updateData['quiz_id'] = null;
                $updateData['source_type'] = 'manual';
            }

            DB::table('gradebook_columns')
                ->where('id', $columnId)
                ->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Column updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update column: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch update scores
     */
    public function batchUpdateScores(Request $request, $classId)
    {
        try {
            $validated = $request->validate([
                'scores' => 'required|array',
                'scores.*.column_id' => 'required|exists:gradebook_columns,id',
                'scores.*.student_number' => 'required|exists:students,student_number',
                'scores.*.score' => 'nullable|numeric|min:0'
            ]);

            DB::beginTransaction();

            foreach ($validated['scores'] as $scoreData) {
                DB::table('gradebook_scores')->updateOrInsert(
                    [
                        'column_id' => $scoreData['column_id'],
                        'student_number' => $scoreData['student_number']
                    ],
                    [
                        'score' => $scoreData['score'],
                        'source' => 'manual',
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Scores updated successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit final grades for the semester
     */
    public function submitFinalGrades(Request $request, $classId)
    {
        try {
            $teacher = Auth::guard('teacher')->user();
            
            $hasAccess = DB::table('teacher_class_matrix')
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $classId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'grades' => 'required|array',
                'grades.*.student_number' => 'required|exists:students,student_number',
                'grades.*.q1_grade' => 'required|numeric|min:0|max:100',
                'grades.*.q2_grade' => 'required|numeric|min:0|max:100',
                'grades.*.final_grade' => 'required|integer|min:0|max:100',
                'grades.*.remarks' => 'required|in:PASSED,FAILED,INC,DRP,W',
                'semester_id' => 'required|exists:semesters,id',
                'section_id' => 'required|exists:sections,id'
            ]);

            $class = DB::table('classes')->where('id', $classId)->first();
            $semesterId = $validated['semester_id'];

            DB::beginTransaction();

            foreach ($validated['grades'] as $gradeData) {
                $student = DB::table('students')
                    ->where('student_number', $gradeData['student_number'])
                    ->first();

                if (!$student) continue;

                // Verify enrollment
                $isEnrolled = false;
                
                if ($student->student_type === 'regular') {
                    $isEnrolled = DB::table('section_class_matrix')
                        ->where('section_id', $validated['section_id'])
                        ->where('class_id', $classId)
                        ->where('semester_id', $semesterId)
                        ->exists();
                } else {
                    $isEnrolled = DB::table('student_class_matrix')
                        ->where('student_number', $gradeData['student_number'])
                        ->where('class_code', $class->class_code)
                        ->where('semester_id', $semesterId)
                        ->where('enrollment_status', 'enrolled')
                        ->exists();
                }

                if (!$isEnrolled) continue;

                DB::table('grades_final')->updateOrInsert(
                    [
                        'student_number' => $gradeData['student_number'],
                        'class_code' => $class->class_code,
                        'semester_id' => $semesterId
                    ],
                    [
                        'q1_grade' => $gradeData['q1_grade'],
                        'q2_grade' => $gradeData['q2_grade'],
                        'final_grade' => $gradeData['final_grade'],
                        'remarks' => $gradeData['remarks'],
                        'computed_by' => $teacher->id,
                        'computed_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Final grades submitted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to submit final grades', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit final grades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if final grades are already submitted
     */
    public function checkFinalGradesStatus($classId, Request $request)
    {
        try {
            $teacher = Auth::guard('teacher')->user();
            
            $hasAccess = DB::table('teacher_class_matrix')
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $classId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $semesterId = $request->input('semester_id');
            $sectionId = $request->input('section_id');
            
            if (!$semesterId || !$sectionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester ID and Section ID required'
                ], 400);
            }

            $class = DB::table('classes')->where('id', $classId)->first();

            $students = $this->getEnrolledStudentsBySection($classId, $sectionId);
            
            $submittedGrades = DB::table('grades_final')
                ->where('class_code', $class->class_code)
                ->where('semester_id', $semesterId)
                ->whereIn('student_number', $students->pluck('student_number'))
                ->get()
                ->keyBy('student_number');

            $isSubmitted = $submittedGrades->count() > 0;
            $submittedCount = $submittedGrades->count();
            $totalStudents = $students->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_submitted' => $isSubmitted,
                    'submitted_count' => $submittedCount,
                    'total_students' => $totalStudents,
                    'submitted_grades' => $submittedGrades
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to check final grades status', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage()
            ], 500);
        }
    }

    // Private helper method
    private function getEnrolledStudentsBySection($classId, $sectionId)
    {
        $class = DB::table('classes')->where('id', $classId)->first();

        $regular = DB::table('students as s')
            ->join('section_class_matrix as scm', 's.section_id', '=', 'scm.section_id')
            ->where('scm.class_id', $classId)
            ->where('s.section_id', $sectionId)
            ->where('s.student_type', 'regular')
            ->select(
                's.student_number',
                's.gender',
                's.section_id',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        $irregular = DB::table('students as s')
            ->join('student_class_matrix as scm', 's.student_number', '=', 'scm.student_number')
            ->where('scm.class_code', $class->class_code)
            ->where('s.section_id', $sectionId)
            ->where('scm.enrollment_status', 'enrolled')
            ->where('s.student_type', 'irregular')
            ->select(
                's.student_number',
                's.gender',
                's.section_id',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(SUBSTRING(s.middle_name, 1, 1), ''), '.') as full_name")
            );

        return $regular->union($irregular)
            ->orderBy('gender')
            ->orderBy('full_name')
            ->get();
    }
}
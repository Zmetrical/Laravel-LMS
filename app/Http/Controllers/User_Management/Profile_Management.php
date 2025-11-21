<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

use App\Models\User_Management\Student;
use App\Models\User_Management\Teacher;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use Exception;
class Profile_Management extends MainController
{
// View only
public function show_student($id)
{
    $student = DB::table('students')
        ->join('sections', 'students.section_id', '=', 'sections.id')
        ->join('levels', 'sections.level_id', '=', 'levels.id')
        ->join('strands', 'sections.strand_id', '=', 'strands.id')
        ->select(
            'students.*',
            'sections.name as section',
            'levels.name as level',
            'strands.code as strand'
        )
        ->where('students.id', '=', $id)
        ->first();

    // Get enrolled semesters based on student type
    if ($student->student_type === 'regular') {
        // For regular students, get semesters from section_class_matrix
        $enrolledSemesters = DB::table('section_class_matrix as scm')
            ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->select(
                'sem.id as semester_id',
                'sem.name as semester_name',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->where('scm.section_id', '=', $student->section_id)
            ->groupBy('sem.id', 'sem.name', 'sy.year_start', 'sy.year_end', 'sy.code')
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.name', 'asc')
            ->get();
    } else {
        // For irregular students, get semesters from student_class_matrix
        $enrolledSemesters = DB::table('student_class_matrix as scm')
            ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->select(
                'sem.id as semester_id',
                'sem.name as semester_name',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->where('scm.student_number', '=', $student->student_number)
            ->where('scm.enrollment_status', '=', 'enrolled')
            ->groupBy('sem.id', 'sem.name', 'sy.year_start', 'sy.year_end', 'sy.code')
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.name', 'asc')
            ->get();
    }

    $data = [
        'student' => $student,
        'enrolledSemesters' => $enrolledSemesters,
        'mode' => 'view',
        'scripts' => ['profile_management/profile_student.js']
    ];

    return view('modules.profile.profile_student', $data);
}

// Edit form
public function edit_student($id)
{
    $student = DB::table('students')
        ->join('sections', 'students.section_id', '=', 'sections.id')
        ->join('levels', 'sections.level_id', '=', 'levels.id')
        ->join('strands', 'sections.strand_id', '=', 'strands.id')
        ->select(
            'students.*',
            'sections.name as section',
            'levels.name as level',
            'strands.code as strand'
        )
        ->where('students.id', '=', $id)
        ->first();

    // Get enrolled semesters based on student type
    if ($student->student_type === 'regular') {
        // For regular students, get semesters from section_class_matrix
        $enrolledSemesters = DB::table('section_class_matrix as scm')
            ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->select(
                'sem.id as semester_id',
                'sem.name as semester_name',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->where('scm.section_id', '=', $student->section_id)
            ->groupBy('sem.id', 'sem.name', 'sy.year_start', 'sy.year_end', 'sy.code')
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.name', 'asc')
            ->get();
    } else {
        // For irregular students, get semesters from student_class_matrix
        $enrolledSemesters = DB::table('student_class_matrix as scm')
            ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->select(
                'sem.id as semester_id',
                'sem.name as semester_name',
                'sy.year_start',
                'sy.year_end',
                'sy.code as school_year_code'
            )
            ->where('scm.student_number', '=', $student->student_number)
            ->where('scm.enrollment_status', '=', 'enrolled')
            ->groupBy('sem.id', 'sem.name', 'sy.year_start', 'sy.year_end', 'sy.code')
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.name', 'asc')
            ->get();
    }

    $data = [
        'student' => $student,
        'enrolledSemesters' => $enrolledSemesters,
        'mode' => 'edit',
        'scripts' => ['profile_management/profile_student.js']
    ];

    return view('modules.profile.profile_student', $data);
}
    public function update_student(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255|unique:students,email,' . $id,
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // === Handle Image  ===
            if ($request->hasFile('profile_image')) {
                if ($student->profile_image) {
                    Storage::disk('public')->delete($student->profile_image);
                }

                $image = $request->file('profile_image');
                $imageName = 'student_' . $id . '_' . time() . '.' . $image->extension();
                $imagePath = $image->storeAs('students', $imageName, 'public');
                $validated['profile_image'] = $imagePath;
            }

            // === Update DB  ===
            $student->update($validated);

            // === Return Response  ===
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $student
                ]);
            }

            // === Return Redirect  ===
            return redirect()->route('profile.student.show', $id)
                ->with('success', 'Profile updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update profile');
        }
    }

    // Get enrolled classes for a student
    public function get_enrolled_classes($id)
    {
        try {
            $student = Student::findOrFail($id);
            
            // Check student type
            if ($student->student_type === 'regular') {
                // For regular students, get classes from section_class_matrix
                $enrolledClasses = DB::table('section_class_matrix as scm')
                    ->join('classes as c', 'scm.class_id', '=', 'c.id')
                    ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
                    ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                    ->select(
                        'c.class_code',
                        'c.class_name',
                        'sem.name as semester_name',
                        'sy.year_start',
                        'sy.year_end',
                        'scm.semester_id'
                    )
                    ->where('scm.section_id', '=', $student->section_id)
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('sem.name', 'asc')
                    ->orderBy('c.class_name', 'asc')
                    ->get();
            } else {
                // For irregular students, get classes from student_class_matrix
                $enrolledClasses = DB::table('student_class_matrix as scm')
                    ->join('classes as c', 'scm.class_code', '=', 'c.class_code')
                    ->join('semesters as sem', 'scm.semester_id', '=', 'sem.id')
                    ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                    ->select(
                        'c.class_code',
                        'c.class_name',
                        'sem.name as semester_name',
                        'sy.year_start',
                        'sy.year_end',
                        'scm.semester_id',
                        'scm.enrollment_status'
                    )
                    ->where('scm.student_number', '=', $student->student_number)
                    ->where('scm.enrollment_status', '=', 'enrolled')
                    ->orderBy('sy.year_start', 'desc')
                    ->orderBy('sem.name', 'asc')
                    ->orderBy('c.class_name', 'asc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $enrolledClasses,
                'student_type' => $student->student_type
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enrolled classes: ' . $e->getMessage()
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Teacher
    // ---------------------------------------------------------------------------

    public function show_teacher($id)
    {
        $teacher = DB::table('teachers')
            ->select('teachers.*')
            ->where('teachers.id', '=', $id)
            ->first();

        if (!$teacher) {
            return redirect()->back()->with('error', 'Teacher not found');
        }

        $data = [
            'mode' => 'view',
            'scripts' => ['profile_management/profile_teacher.js'],
            'teacher' => $teacher
        ];

        return view('modules.profile.profile_teacher', $data);
    }

    public function edit_teacher($id)
    {
        $teacher = DB::table('teachers')
            ->select('teachers.*')
            ->where('teachers.id', '=', $id)
            ->first();

        if (!$teacher) {
            return redirect()->back()->with('error', 'Teacher not found');
        }

        $data = [
            'mode' => 'edit',
            'scripts' => ['profile_management/profile_teacher.js'],
            'teacher' => $teacher
        ];

        return view('modules.profile.profile_teacher', $data);
    }

    public function update_teacher(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:teachers,email,' . $id,
                'phone' => 'required|string|max:20',
                'gender' => 'nullable|in:Male,Female,Other',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            DB::beginTransaction();

            $teacher = Teacher::find($id);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }

            if ($request->hasFile('profile_image')) {
                if ($teacher->profile_image) {
                    Storage::disk('public')->delete($teacher->profile_image);
                }
                $image = $request->file('profile_image');
                $imageName = 'teacher_' . $id . '_' . time() . '.' . $image->extension();
                $imagePath = $image->storeAs('teachers', $imageName, 'public');
                $validated['profile_image'] = $imagePath;
            }

            $teacher->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'] ?? $teacher->gender,
                'user' => $validated['first_name'] . ' ' . $validated['last_name'],
                'profile_image' => $validated['profile_image'] ?? $teacher->profile_image,
                'updated_at' => now(),
            ]);

            DB::commit();

            \Log::info('Teacher updated successfully', [
                'teacher_id' => $teacher->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'data' => $teacher
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update teacher', [
                'teacher_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function change_password_teacher(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);

            DB::beginTransaction();

            $teacher = Teacher::find($id);
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }

            if (!Hash::check($validated['current_password'], $teacher->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            $teacher->update([
                'password' => Hash::make($validated['new_password']),
                'updated_at' => now(),
            ]);

            DB::commit();

            \Log::info('Teacher password changed', [
                'teacher_id' => $teacher->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully!'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }
}
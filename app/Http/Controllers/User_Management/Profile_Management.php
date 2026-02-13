<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

use App\Models\User_Management\Student;
use App\Models\User_Management\Teacher;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Traits\AuditLogger;

use Exception;
class Profile_Management extends MainController
{
    use AuditLogger;

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

    // Get guardians
    $guardians = DB::table('guardian_students as gs')
        ->join('guardians as g', 'gs.guardian_id', '=', 'g.id')
        ->where('gs.student_number', '=', $student->student_number)
        ->where('g.is_active', '=', 1)
        ->select(
            'g.id',
            'g.first_name',
            'g.last_name',
            'g.email'
        )
        ->get();

    $data = [
        'student' => $student,
        'enrolledSemesters' => $enrolledSemesters,
        'guardians' => $guardians,
        'mode' => 'view',
        'scripts' => ['profile_management/view_profile_student.js']
    ];

    return view('modules.profile.view_profile_student', $data);
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

    // Get guardians
    $guardians = DB::table('guardian_students as gs')
        ->join('guardians as g', 'gs.guardian_id', '=', 'g.id')
        ->where('gs.student_number', '=', $student->student_number)
        ->where('g.is_active', '=', 1)
        ->select(
            'g.id',
            'g.first_name',
            'g.last_name',
            'g.email'
        )
        ->get();

    $data = [
        'student' => $student,
        'enrolledSemesters' => $enrolledSemesters,
        'guardians' => $guardians,
        'mode' => 'edit',
        'scripts' => ['profile_management/view_profile_student.js']
    ];

    return view('modules.profile.view_profile_student', $data);
}

    public function update_student(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            // Store old values for audit
            $oldValues = [
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
            ];

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
                
                $oldValues['profile_image'] = $student->profile_image;
            }

            // === Update DB  ===
            $student->update($validated);

            // Prepare new values for audit
            $newValues = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'] ?? '',
            ];

            if (isset($validated['profile_image'])) {
                $newValues['profile_image'] = $validated['profile_image'];
            }

            // Audit log
            $this->logAudit(
                'updated',
                'students',
                $student->student_number,
                "Updated student profile: {$student->first_name} {$student->last_name} ({$student->student_number})",
                $this->formatAuditValues($oldValues),
                $this->formatAuditValues($newValues)
            );

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
/**
 * Get student credentials (password)
 * Only accessible by admin type 1
 */
public function getStudentCredentials(Request $request, $id)
{
    // Check if user is admin type 1
    if (!auth()->guard('admin')->check()) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access'
        ], 403);
    }

    try {
        $student = DB::table('students')->where('id', $id)->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        // Get credentials from student_password_matrix
        $passwordMatrix = DB::table('student_password_matrix')
            ->where('student_number', $student->student_number)
            ->first();

        $credential = $passwordMatrix ? $passwordMatrix->plain_password : 'Not set';

        // Audit log for viewing credentials
        $this->logAudit(
            'viewed',
            'students',
            $student->student_number,
            "Viewed student password: {$student->first_name} {$student->last_name} ({$student->student_number})",
            null,
            ['credential_type' => 'password']
        );

        return response()->json([
            'success' => true,
            'credential' => $credential
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving credential'
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
            'scripts' => ['profile_management/view_profile_teacher.js'],
            'teacher' => $teacher
        ];

        return view('modules.profile.view_profile_teacher', $data);
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
            'scripts' => ['profile_management/view_profile_teacher.js'],
            'teacher' => $teacher
        ];

        return view('modules.profile.view_profile_teacher', $data);
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

            // Store old values for audit
            $oldValues = [
                'first_name' => $teacher->first_name,
                'last_name' => $teacher->last_name,
                'middle_name' => $teacher->middle_name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'gender' => $teacher->gender,
            ];

            if ($request->hasFile('profile_image')) {
                if ($teacher->profile_image) {
                    Storage::disk('public')->delete($teacher->profile_image);
                }
                $image = $request->file('profile_image');
                $imageName = 'teacher_' . $id . '_' . time() . '.' . $image->extension();
                $imagePath = $image->storeAs('teachers', $imageName, 'public');
                $validated['profile_image'] = $imagePath;
                
                $oldValues['profile_image'] = $teacher->profile_image;
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

            // Prepare new values for audit
            $newValues = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'] ?? $teacher->gender,
            ];

            if (isset($validated['profile_image'])) {
                $newValues['profile_image'] = $validated['profile_image'];
            }

            // Audit log
            $this->logAudit(
                'updated',
                'teachers',
                (string)$teacher->id,
                "Updated teacher profile: {$teacher->first_name} {$teacher->last_name} ({$teacher->email})",
                $this->formatAuditValues($oldValues),
                $this->formatAuditValues($newValues)
            );

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

public function getCredentials(Request $request, $id)
{
    // Check if user is admin type 1
    if (!auth()->guard('admin')->check() || auth()->guard('admin')->user()->admin_type != 1) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access'
        ], 403);
    }

    try {
        $teacher = DB::table('teachers')->where('id', $id)->first();
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        // Get credentials from teacher_password_matrix
        $passwordMatrix = DB::table('teacher_password_matrix')
            ->where('teacher_id', $id)
            ->first();

        $type = $request->input('type', 'password');
        
        if ($type === 'password') {
            $credential = $passwordMatrix ? $passwordMatrix->plain_password : 'Not set';
        } else {
            $credential = $passwordMatrix ? $passwordMatrix->plain_passcode : 'Not set';
        }

        // Audit log for viewing credentials
        $this->logAudit(
            'viewed',
            'teachers',
            (string)$id,
            "Viewed teacher {$type}: {$teacher->first_name} {$teacher->last_name} ({$teacher->email})",
            null,
            ['credential_type' => $type]
        );

        return response()->json([
            'success' => true,
            'credential' => $credential
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving credential'
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

            // Update password
            $teacher->update([
                'password' => Hash::make($validated['new_password']),
                'updated_at' => now(),
            ]);

            // Update password matrix
            DB::table('teacher_password_matrix')
                ->updateOrInsert(
                    ['teacher_id' => $id],
                    ['plain_password' => $validated['new_password']]
                );

            // Audit log - password change (don't log actual passwords)
            $this->logAudit(
                'updated',
                'teachers',
                (string)$teacher->id,
                "Changed password: {$teacher->first_name} {$teacher->last_name} ({$teacher->email})",
                ['password' => '[REDACTED]'],
                ['password' => '[REDACTED]']
            );

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
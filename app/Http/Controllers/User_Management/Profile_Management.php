<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

use App\Models\User_Management\Student;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

class Profile_Management extends MainController
{
    // View only
    public function show_student($id)
    {

        $student = DB::table(table: 'students')
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

        $data = [
            'student' => $student,
            'mode' => 'view',
            'scripts' => ['user_management/profile_student.js']
        ];

        return view('profile.profile_student', $data);
    }

    // Edit form
    public function edit_student($id)
    {
        $student = Student::findOrFail($id);
        $student = DB::table(table: 'students')
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

        $data = [
            'student' => $student,
            'mode' => 'edit',
            'scripts' => ['user_management/profile_student.js']
        ];

        return view('profile.profile_student', $data);
    }


    public function update_student(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);
            
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:students,email,' . $id,
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // === Handle Image  ===
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($student->profile_image) {
                    Storage::disk('public')->delete($student->profile_image);
                }
                
                // Save new image to storage/app/public/students
                $image = $request->file('profile_image');
                $imageName = 'student_' . $id . '_' . time() . '.' . $image->extension();
                $imagePath = $image->storeAs('students', $imageName, 'public');
                
                // Store path in database: students/student_1_1234567890.jpg
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
}

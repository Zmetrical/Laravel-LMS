<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User_Management\Teacher;
use Exception;
class TeacherController extends Controller
{
    public function index()
    {

        $data = [
            'scripts' => [
                'teacher/dashboard.js',
            ],
        ];

        return view('teacher.dashboard', $data);

    }



    public function login()
    {

        $data = [
            'scripts' => [
                'teacher/login.js',
            ],
        ];

        return view('admin.login', $data);
    }
        // View teacher profile
    public function show_profile()
    {
        $teacherId = Auth::guard('teacher')->id();
        $teacher = Teacher::findOrFail($teacherId);

        $data = [
            'mode' => 'view',
            'teacher' => $teacher
        ];

        return view('teacher.teacher_profile', $data);
    }

    // Edit teacher profile
    public function edit_profile()
    {
        $teacherId = Auth::guard('teacher')->id();
        $teacher = Teacher::findOrFail($teacherId);

        $data = [
            'mode' => 'edit',
            'teacher' => $teacher
        ];

        return view('teacher.teacher_profile', $data);
    }

    // Update teacher profile
    public function update_profile(Request $request)
    {
        try {
            $teacherId = Auth::guard('teacher')->id();
            
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:teachers,email,' . $teacherId,
                'phone' => 'required|string|max:20',
                'gender' => 'nullable|in:Male,Female,Other',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            DB::beginTransaction();

            $teacher = Teacher::find($teacherId);
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
                $imageName = 'teacher_' . $teacherId . '_' . time() . '.' . $image->extension();
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

}

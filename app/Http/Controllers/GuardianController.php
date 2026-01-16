<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Exception;

class GuardianController extends Controller
{
    public function index()
    {
        $data = [
            'scripts' => [
                'guardian/dashboard.js',
            ],
        ];

        return view('guardian.dashboard', $data);
    }

    public function login()
    {
        $data = [
            'scripts' => [
                'guardian/login.js',
            ],
        ];

        return view('student.login', $data);
    }

    // View guardian profile
    public function show_profile()
    {
        // Temporary mock data until database is ready
        $guardian = (object)[
            'id' => 1,
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@email.com',
            'phone' => '09123456789',
            'relationship' => 'Parent',
            'profile_image' => null
        ];

        // Mock students data
        $students = collect([
            (object)[
                'student_number' => '2024-0001',
                'full_name' => 'Maria Dela Cruz',
                'section' => 'STEM 11-A',
                'level' => 'Grade 11'
            ],
            (object)[
                'student_number' => '2024-0002',
                'full_name' => 'Jose Dela Cruz',
                'section' => 'ABM 12-B',
                'level' => 'Grade 12'
            ]
        ]);

        $data = [
            'mode' => 'view',
            'guardian' => $guardian,
            'students' => $students
        ];

        return view('guardian.guardian_profile', $data);
    }

    // Edit guardian profile
    public function edit_profile()
    {
        // Temporary mock data until database is ready
        $guardian = (object)[
            'id' => 1,
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@email.com',
            'phone' => '09123456789',
            'relationship' => 'Parent',
            'profile_image' => null
        ];

        // Mock students data
        $students = collect([
            (object)[
                'student_number' => '2024-0001',
                'full_name' => 'Maria Dela Cruz',
                'section' => 'STEM 11-A',
                'level' => 'Grade 11'
            ],
            (object)[
                'student_number' => '2024-0002',
                'full_name' => 'Jose Dela Cruz',
                'section' => 'ABM 12-B',
                'level' => 'Grade 12'
            ]
        ]);

        $data = [
            'mode' => 'edit',
            'guardian' => $guardian,
            'students' => $students
        ];

        return view('guardian.guardian_profile', $data);
    }

    // Update guardian profile (placeholder)
    public function update_profile(Request $request)
    {
        try {
            // Validation rules
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'relationship' => 'required|string|max:50',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // TODO: Implement actual database update when guardian table is created

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    // View students' grades
    public function view_students()
    {
        // Mock data for students
        $students = collect([
            (object)[
                'student_number' => '2024-0001',
                'full_name' => 'Maria Dela Cruz',
                'section' => 'STEM 11-A',
                'level' => 'Grade 11',
                'profile_image' => null
            ],
            (object)[
                'student_number' => '2024-0002',
                'full_name' => 'Jose Dela Cruz',
                'section' => 'ABM 12-B',
                'level' => 'Grade 12',
                'profile_image' => null
            ]
        ]);

        $data = [
            'scripts' => ['guardian/students.js'],
            'students' => $students
        ];

        return view('guardian.students', $data);
    }

    // View specific student's grades
    public function view_student_grades($student_number)
    {
        // Mock student data
        $student = (object)[
            'student_number' => $student_number,
            'full_name' => 'Maria Dela Cruz',
            'section' => 'STEM 11-A',
            'level' => 'Grade 11',
            'profile_image' => null
        ];

        // Mock grades data
        $grades = collect([
            (object)[
                'class_name' => 'General Mathematics',
                'q1_grade' => 88.5,
                'q2_grade' => 90.0,
                'final_grade' => 89.25,
                'remarks' => 'PASSED'
            ],
            (object)[
                'class_name' => 'English for Academic Purposes',
                'q1_grade' => 92.0,
                'q2_grade' => 91.5,
                'final_grade' => 91.75,
                'remarks' => 'PASSED'
            ]
        ]);

        $data = [
            'scripts' => ['guardian/student_grades.js'],
            'student' => $student,
            'grades' => $grades
        ];

        return view('guardian.student_grades', $data);
    }
}
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User_Management\Student;

class Login_Controller extends MainController
{
    // ---------------------------------------------------------------------------
    //  Student Authentication
    // ---------------------------------------------------------------------------

    public function auth_student(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check if student exists
        $student = Student::where('student_number', $request->student_number)->first();
        
        if (!$student) {
            Log::warning('Student not found', [
                'student_number' => $request->student_number
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid student number or password.'
            ], 401);
        }

        // Attempt to authenticate
        $credentials = [
            'student_number' => $request->student_number,
            'password' => $request->password,
        ];

        $remember = $request->has('remember') && $request->remember == 1;

        if (Auth::guard('student')->attempt($credentials, $remember)) {
            // Authentication passed
            $request->session()->regenerate();

            Log::info('Student login successful', [
                'student_id' => $student->id,
                'student_number' => $student->student_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => route('student.home')
            ]);
        }

        // Authentication failed - try manual verification for debugging
        $passwordMatch = Hash::check($request->password, $student->student_password);
        
        Log::warning('Student login failed', [
            'student_number' => $request->student_number,
            'password_match_manual' => $passwordMatch,
            'stored_password_starts_with' => substr($student->student_password, 0, 7)
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid student number or password.',
            'debug' => config('app.debug') ? [
                'student_exists' => true,
                'password_match' => $passwordMatch,
                'guard' => 'student'
            ] : null
        ], 401);
    }

    public function logout_student(Request $request)
    {
        $studentNumber = Auth::guard('student')->user()?->student_number;
        
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Student logged out', [
            'student_number' => $studentNumber
        ]);

        return redirect()->route('student.login');
    }


    // ---------------------------------------------------------------------------
    //  Teacher
    // ---------------------------------------------------------------------------

    public function auth_teacher(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Attempt to authenticate
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'status' => 1, // Only active teachers can login
        ];

        $remember = $request->has('remember') && $request->remember == 1;

        if (Auth::guard('teacher')->attempt($credentials, $remember)) {
            // Authentication passed
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => route('teacher.home')
            ]);
        }

        // Authentication failed
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    public function logout_teacher(Request $request)
    {
        Auth::guard('teacher')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }
}

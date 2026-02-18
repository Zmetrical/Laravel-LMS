<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use App\Traits\AuditLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User_Management\Student;
use App\Models\Admin;

class Login_Controller extends MainController
{
    use AuditLogger, AuditLogin;

    public function auth_student(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

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

        // Check password first before semester validation
        if (!Hash::check($request->password, $student->student_password)) {
            Log::warning('Student login failed - invalid password', [
                'student_number' => $request->student_number
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid student number or password.'
            ], 401);
        }

        // Get active semester
        $activeSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        if (!$activeSemester) {
            Log::warning('Student login blocked - no active semester', [
                'student_number' => $request->student_number
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No active semester found. Please contact the administrator.'
            ], 403);
        }

        // Check if student is enrolled in active semester
        $enrollment = DB::table('student_semester_enrollment')
            ->where('student_number', $student->student_number)
            ->where('semester_id', $activeSemester->id)
            ->where('enrollment_status', 'enrolled')
            ->first();

        if (!$enrollment) {
            Log::warning('Student login blocked - not enrolled in active semester', [
                'student_number' => $request->student_number,
                'active_semester_id' => $activeSemester->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in the current semester. Please contact the registrar.'
            ], 403);
        }

        // Proceed with authentication
        $credentials = [
            'student_number' => $request->student_number,
            'password' => $request->password,
        ];

        $remember = $request->has('remember') && $request->remember == 1;

        if (Auth::guard('student')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $loginId = $this->logLogin(
                'student',
                $student->student_number,
                $request->session()->getId()
            );

            $request->session()->put('audit_login_id', $loginId);

            Log::info('Student login successful', [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'semester_id' => $activeSemester->id
            ]);

            $sessionKey = 'student_classes_' . $student->id;
            $request->session()->forget($sessionKey);

            return response()->json([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => route('student.home')
            ]);
        }

        Log::warning('Student login failed - authentication error', [
            'student_number' => $request->student_number
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid student number or password.'
        ], 401);
    }

    public function logout_student(Request $request)
    {
        $student = Auth::guard('student')->user();
        $studentNumber = $student?->student_number;
        
        $loginId = $request->session()->get('audit_login_id');
        if ($loginId) {
            $this->logLogout($loginId);
        }

        if ($student) {
            $sessionKey = 'student_classes_' . $student->id;
            $request->session()->forget($sessionKey);
        }

        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Student logged out', [
            'student_number' => $studentNumber
        ]);

        return redirect()->route('student.login');
    }

public function auth_teacher(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $teacher = DB::table('teachers')
        ->where('email', $request->email)
        ->first();
    
    if (!$teacher) {
        Log::warning('Teacher not found', [
            'email' => $request->email
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    // Check password first before other validations
    if (!Hash::check($request->password, $teacher->password)) {
        Log::warning('Teacher login failed - invalid password', [
            'email' => $request->email
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    if ($teacher->status != 1) {
        Log::warning('Teacher login blocked - account inactive', [
            'teacher_id' => $teacher->id,
            'email' => $request->email
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Your account is inactive. Please contact the administrator.'
        ], 403);
    }

    $activeSemester = DB::table('semesters as s')
        ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
        ->where('s.status', 'active')
        ->select('s.*', 'sy.id as school_year_id', 'sy.code as school_year_code')
        ->first();

    if ($activeSemester) {
        $statusTrail = DB::table('teacher_school_year_status')
            ->where('teacher_id', $teacher->id)
            ->where('school_year_id', $activeSemester->school_year_id)
            ->first();

        if ($statusTrail && $statusTrail->status === 'inactive') {
            Log::warning('Teacher login blocked - deactivated for current school year', [
                'teacher_id' => $teacher->id,
                'email' => $request->email,
                'school_year_id' => $activeSemester->school_year_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Your account is currently inactive for this school year. Please contact the administrator.'
            ], 403);
        }
    }

    $credentials = [
        'email' => $request->email,
        'password' => $request->password,
        'status' => 1,
    ];

    $remember = $request->has('remember') && $request->remember == 1;

    if (Auth::guard('teacher')->attempt($credentials, $remember)) {
        $request->session()->regenerate();

        $authenticatedTeacher = Auth::guard('teacher')->user();

        $loginId = $this->logLogin(
            'teacher',
            $authenticatedTeacher->email,
            $request->session()->getId()
        );

        $request->session()->put('audit_login_id', $loginId);

        Log::info('Teacher login successful', [
            'teacher_id' => $teacher->id,
            'email' => $teacher->email,
            'semester_id' => $activeSemester->id ?? null,
            'school_year_id' => $activeSemester->school_year_id ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => route('teacher.home')
        ]);
    }

    Log::warning('Teacher login failed - authentication error', [
        'email' => $request->email
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Invalid email or password.'
    ], 401);
}

    public function logout_teacher(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();

        $loginId = $request->session()->get('audit_login_id');
        if ($loginId) {
            $this->logLogout($loginId);
        }

        Auth::guard('teacher')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }

public function auth_admin(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $admin = Admin::where('email', $request->email)->first();

    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    // Check password first
    if (!Hash::check($request->password, $admin->admin_password)) {
        Log::warning('Admin login failed - invalid password', ['email' => $request->email]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    // Check account status
    if ($admin->status != 1) {
        Log::warning('Admin login blocked - account inactive', [
            'admin_id' => $admin->id,
            'email'    => $request->email
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Your account has been deactivated. Please contact the Super Admin.'
        ], 403);
    }

    $remember = $request->has('remember') && $request->remember == 1;

    if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
        $request->session()->regenerate();

        $loginId = $this->logLogin('admin', $admin->email, $request->session()->getId());
        $request->session()->put('audit_login_id', $loginId);

        Log::info('Admin login successful', ['admin_id' => $admin->id, 'email' => $admin->email]);

        return response()->json([
            'success'  => true,
            'message'  => 'Login successful! Redirecting...',
            'redirect' => route('admin.home')
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid email or password.'
    ], 401);
}

    public function logout_admin(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $loginId = $request->session()->get('audit_login_id');
        if ($loginId) {
            $this->logLogout($loginId);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function auth_guardian(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => route('guardian.home')
        ]);
    }

    public function logout_guardian(Request $request)
    {
        $loginId = $request->session()->get('audit_login_id');
        if ($loginId) {
            $this->logLogout($loginId);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guardian.login');
    }
}
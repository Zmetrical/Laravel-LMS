<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use App\Traits\AuditLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User_Management\Student;
use App\Models\Admin;

class Login_Controller extends MainController
{
    use AuditLogger, AuditLogin;

    // ---------------------------------------------------------------------------
    //  Student Authentication
    // ---------------------------------------------------------------------------

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

    $credentials = [
        'student_number' => $request->student_number,
        'password' => $request->password,
    ];

    $remember = $request->has('remember') && $request->remember == 1;

    if (Auth::guard('student')->attempt($credentials, $remember)) {
        $request->session()->regenerate();

        // Log to audit_login table ONLY
        $loginId = $this->logLogin(
            'student',
            $student->student_number,
            $request->session()->getId()
        );

        // Store login ID in session for logout
        $request->session()->put('audit_login_id', $loginId);

        Log::info('Student login successful', [
            'student_id' => $student->id,
            'student_number' => $student->student_number
        ]);

        $sessionKey = 'student_classes_' . $student->id;
        $request->session()->forget($sessionKey);

        return response()->json([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => route('student.home')
        ]);
    }

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
    $student = Auth::guard('student')->user();
    $studentNumber = $student?->student_number;
    
    // Log logout in audit_login table ONLY
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

    // ---------------------------------------------------------------------------
    //  Teacher Authentication
    // ---------------------------------------------------------------------------

public function auth_teacher(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $credentials = [
        'email' => $request->email,
        'password' => $request->password,
        'status' => 1,
    ];

    $remember = $request->has('remember') && $request->remember == 1;

    if (Auth::guard('teacher')->attempt($credentials, $remember)) {
        $request->session()->regenerate();

        $teacher = Auth::guard('teacher')->user();

        // Log to audit_login table ONLY
        $loginId = $this->logLogin(
            'teacher',
            $teacher->email,
            $request->session()->getId()
        );

        $request->session()->put('audit_login_id', $loginId);

        return response()->json([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => route('teacher.home')
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid email or password.'
    ], 401);
}

public function logout_teacher(Request $request)
{
    $teacher = Auth::guard('teacher')->user();

    // Log logout in audit_login table ONLY
    $loginId = $request->session()->get('audit_login_id');
    if ($loginId) {
        $this->logLogout($loginId);
    }

    Auth::guard('teacher')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('teacher.login');
}

    // ---------------------------------------------------------------------------
    //  Admin Authentication
    // ---------------------------------------------------------------------------

public function auth_admin(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $admin = Admin::where('email', $request->email)->first();
    
    if (!$admin) {
        Log::warning('Admin not found', [
            'email' => $request->email
        ]);
            
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    $credentials = [
        'email' => $request->email,
        'password' => $request->password,
    ];

    $remember = $request->has('remember') && $request->remember == 1;

    if (Auth::guard('admin')->attempt($credentials, $remember)) {
        $request->session()->regenerate();

        // Log to audit_login table ONLY
        $loginId = $this->logLogin(
            'admin',
            $admin->email,
            $request->session()->getId()
        );

        $request->session()->put('audit_login_id', $loginId);

        Log::info('Admin login successful', [
            'admin_id' => $admin->id,
            'email' => $admin->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => route('admin.home')
        ]);
    }

    $passwordMatch = Hash::check($request->password, $admin->admin_password);
    
    Log::warning('Admin login failed', [
        'email' => $request->email,
        'password_match_manual' => $passwordMatch,
        'stored_password_starts_with' => substr($admin->admin_password, 0, 7)
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Invalid email or password.',
        'debug' => config('app.debug') ? [
            'admin_exists' => true,
            'password_match' => $passwordMatch,
            'guard' => 'admin'
        ] : null
    ], 401);
}

public function logout_admin(Request $request)
{
    $admin = Auth::guard('admin')->user();

    // Log logout in audit_login table ONLY
    $loginId = $request->session()->get('audit_login_id');
    if ($loginId) {
        $this->logLogout($loginId);
    }

    Auth::guard('admin')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('admin.login');
}

    // ---------------------------------------------------------------------------
    //  Guardian Authentication
    // ---------------------------------------------------------------------------

public function auth_guardian(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    // TODO: Implement actual authentication when guardian table is ready
    
    return response()->json([
        'success' => true,
        'message' => 'Login successful! Redirecting...',
        'redirect' => route('guardian.home')
    ]);
}

public function logout_guardian(Request $request)
{
    // Log logout in audit_login table ONLY
    $loginId = $request->session()->get('audit_login_id');
    if ($loginId) {
        $this->logLogout($loginId);
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('guardian.login');
}
}
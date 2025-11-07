<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MainController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Login_Controller extends MainController
{
    // ---------------------------------------------------------------------------
    //  Student 
    // ---------------------------------------------------------------------------

    public function auth_student(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt to authenticate
        $credentials = [
            'student_number' => $request->student_number,
            'password' => $request->password,
        ];

        $remember = $request->has('remember') && $request->remember == 1;

        if (Auth::guard('student')->attempt($credentials, $remember)) {
            // Authentication passed
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => route('student.home')
            ]);
        }

        // Authentication failed
        return response()->json([
            'success' => false,
            'message' => 'Invalid student number or password.'
        ], 401);
    }

    public function logout_student(Request $request)
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }


    
}

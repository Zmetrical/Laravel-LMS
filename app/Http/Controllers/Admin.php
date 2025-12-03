<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin as AdminModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Admin extends Controller
{
    public function index()
    {

        $data = [
            'scripts' => [
                'admin/dashboard.js',
            ],
            'styles' => [
                'admin/dashboard.css'
            ],

        ];

        return view('admin.dashboard', $data);
    }


    public function login()
    {
        // Redirect if already logged in
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.home');
        }

        $data = [
            'scripts' => [
                'admin/login.js',
            ],
        ];

        return view('admin.login', $data);
    }

    public function auth_admin(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Check if admin exists
        $admin = AdminModel::where('email', $request->email)->first();
        
        if (!$admin) {
            Log::warning('Admin not found', [
                'email' => $request->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], 401);
        }

        // Attempt to authenticate
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        $remember = $request->has('remember') && $request->remember == 1;

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            // Authentication passed
            $request->session()->regenerate();

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

        // Authentication failed - try manual verification for debugging
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
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();



        return redirect()->route('admin.login');
    }

}

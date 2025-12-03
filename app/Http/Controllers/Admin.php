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



}

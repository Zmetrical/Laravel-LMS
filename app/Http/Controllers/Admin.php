<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}

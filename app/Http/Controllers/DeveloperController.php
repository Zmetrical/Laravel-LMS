<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeveloperController extends Controller
{
    public function index()
    {

        $data = [
            'scripts' => [
                'developer/startup.js',
            ]

        ];

        return view('layouts.startup', $data);
    }
}

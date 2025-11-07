<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeveloperController extends Controller
{
    public function index()
    {

        $data = [
            'scripts' => [
                'index.js',
            ]

        ];

        return view('layouts.index', $data);
    }
}

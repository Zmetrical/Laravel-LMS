<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class User_Management_Controller extends Controller
{
        public function index(){
        $data = [
            'scripts' => [
                'user_management/user_management.js',
                ],
            'styles' => [
                'user_management/user_management.css'
            ],
        ];
        
        return view('user_management.user_management', $data);
    }
}

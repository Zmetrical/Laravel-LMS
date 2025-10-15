<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
class User_Management extends MainController
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

    public function store(Request $request)
    {
        \Log::info('Store method called', $request->all());
            
        // Validate the form data
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users,user_name', 
            'email' => 'required|email|unique:users,email', 
            'password' => 'required|string|min:8',
            'strand_id' => 'required|exists:strands,id', 
            'section_id' => 'required|exists:sections,id', 

        ]);
        
        \Log::info('Validation passed', $validated);
        
        DB::transaction(function () use ($validated) {
            // Create new user record first
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'user_name' => $validated['user_name'],
                
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => 1,
                'status' => 1, 
            ]);
            
            // Generate student number
            $studentNumber = $this->generateStudentNumber();
            
            // Create student record
            Student::create([
                'user_id' => $user->id,
                'student_number' => $studentNumber,
                'strand_id' => $validated['strand_id'], 
                'section_id' => $validated['section_id'], 

                'enrollment_date' => now(),
                'year_level' => '1st Year',
            ]);
            
            \Log::info('User and Student created successfully', [
                'user_id' => $user->id,
                'student_number' => $studentNumber
            ]);
        });
        
        return redirect()->back()->with('success', 'User created successfully!');
    }

    private function generateStudentNumber()
    {
        $year = date('Y');
        $lastStudent = User::whereYear('created_at', $year)->count();
         // e.g., 202500001
        return $year . str_pad($lastStudent + 1, 5, '0', STR_PAD_LEFT);
    }

}

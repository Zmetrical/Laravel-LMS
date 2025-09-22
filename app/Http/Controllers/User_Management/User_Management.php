<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\User_List;

class User_Management extends Controller
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
            'email' => 'required|email|unique:users_list,email',
            'password' => 'required|string|min:8',
        ]);


        \Log::info('Validation passed', $validated);

        DB::transaction(function () use ($validated) {
            // Create new user record first
            $user = User_List::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => 1, // Student role
            ]);

            // Generate student number
            $studentNumber = $this->generateStudentNumber();

            // Create student record
            Student::create([
                'user_id' => $user->id,
                'student_number' => $studentNumber,
                'enrollment_date' => now(),
                'year_level' => '1st Year', // Default or add to form
                'section' => 'A', // Default or add to form
                'status' => 'active'
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
        $lastStudent = Student::whereYear('created_at', $year)->count();
        return $year . str_pad($lastStudent + 1, 5, '0', STR_PAD_LEFT); // e.g., 202500001
    }

}

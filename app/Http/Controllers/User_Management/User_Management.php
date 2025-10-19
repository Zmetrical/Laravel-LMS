<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User_Management\Strand;
use App\Models\User_Management\Section;
use App\Models\User_Management\Level;
use App\Models\User_Management\Student;

class User_Management extends MainController
{
    // ---------------------------------------------------------------------------
    //  Insert User Page
    // ---------------------------------------------------------------------------

    public function page_insertUser()
    {

        $strands = Strand::all();
        $levels = Level::all();
        $sections = Section::all();

        $data = [
            'scripts' => [
                'user_management/insert_user.js',
            ],
            'styles' => [
                'user_management/insert_user.css'
            ],

            'strands' => $strands,
            'levels' => $levels,
            'sections' => $sections,

        ];

        return view('admin.user_management.insert_user', $data);
    }

    public function get_Sections(Request $request)
    {
        $query = Section::query();

        if ($request->strand_id) {
            $query->where('strand_id', $request->strand_id);
        }
        if ($request->level_id) {
            $query->where('level_id', $request->level_id);
        }

        return response()->json($query->get());
    }



    public function insert_Student(Request $request)
    {
        // Validate the form data to match the view fields
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:students,email',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'section' => 'required|exists:sections,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Generate student number and password
                $studentNumber = $this->generateStudentNumber();
                $defaultPassword = $this->generateStudentPassword($validated['first_name'], $validated['last_name']);

                // Create student record
                $student = Student::create([
                    'student_number' => $studentNumber,
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'student_password' => Hash::make($defaultPassword),
                    'section_id' => $validated['section'],
                    'enrollment_date' => now(),
                ]);

                \Log::info('Student created successfully', [
                    'student_id' => $student->id,
                    'student_number' => $studentNumber,
                    'default_password' => $defaultPassword // Log for admin reference
                ]);
            });

            return redirect()->back()->with('success', 'Student created successfully!');
        } catch (\Exception $e) {
            \Log::error('Error creating student: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to create student. Please try again.']);
        }
    }

    private function generateStudentNumber()
    {
        $year = date('Y');
        $lastStudent = Student::whereYear('created_at', $year)->count();

        return $year . str_pad($lastStudent + 1, 5, '0', STR_PAD_LEFT);
    }

    private function generateStudentPassword($firstName, $lastName)
    {
        $password = ucfirst(strtolower($firstName)) . ucfirst(strtolower($lastName)) . '@' . date('Y');

        return $password;
    }
}

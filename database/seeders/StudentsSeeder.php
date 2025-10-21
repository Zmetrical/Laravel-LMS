<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
class StudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            [
                'student_number' => '2024-0001',
                'student_password' => Hash::make('password123'),
                'email' => 'john.doe@example.com',
                'first_name' => 'John',
                'middle_name' => 'Michael',
                'last_name' => 'Doe',
                'gender' => 'Male',
                'section_id' => 1,
                'enrollment_date' => '2024-08-15',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'student_number' => '2024-0002',
                'student_password' => Hash::make('password123'),
                'email' => 'jane.smith@example.com',
                'first_name' => 'Jane',
                'middle_name' => 'Marie',
                'last_name' => 'Smith',
                'gender' => 'Female',
                'section_id' => 1,
                'enrollment_date' => '2024-08-15',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'student_number' => '2024-0003',
                'student_password' => Hash::make('password123'),
                'email' => 'robert.johnson@example.com',
                'first_name' => 'Robert',
                'middle_name' => 'James',
                'last_name' => 'Johnson',
                'gender' => 'Male',
                'section_id' => 2,
                'enrollment_date' => '2024-08-16',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'student_number' => '2024-0004',
                'student_password' => Hash::make('password123'),
                'email' => 'emily.williams@example.com',
                'first_name' => 'Emily',
                'middle_name' => 'Rose',
                'last_name' => 'Williams',
                'gender' => 'Female',
                'section_id' => 2,
                'enrollment_date' => '2024-08-16',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'student_number' => '2024-0005',
                'student_password' => Hash::make('password123'),
                'email' => 'michael.brown@example.com',
                'first_name' => 'Michael',
                'middle_name' => 'David',
                'last_name' => 'Brown',
                'gender' => 'Male',
                'section_id' => 3,
                'enrollment_date' => '2024-08-17',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('students')->insert($students);
    }
}

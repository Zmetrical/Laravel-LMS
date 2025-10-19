<?php

namespace App\Models\User_Management;

use App\Models\ParentModel;

class Student extends ParentModel
{
    protected $table = 'students';    
    protected $fillable = [
        'student_number',
        'student_password',
        'email',
        'first_name',
        'middle_name',
        'last_name',
        'section_id',
        'enrollment_date'
    ];

    // Cast enrollment_date as date
    protected $casts = [
        'enrollment_date' => 'date',
    ];
}

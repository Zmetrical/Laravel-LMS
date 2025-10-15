<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends ParentModel
{
    protected $table = 'students'; // Your table name
    public $timestamps = false; // Add this line
    protected $fillable = [
        'user_id',
        'strand_id',
        'section_id',
        'student_number',
        'enrollment_date',
        'year_level',
        'section',
        'status'
    ];

}

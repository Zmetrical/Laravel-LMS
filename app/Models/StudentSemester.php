<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSemester extends Model
{
    protected $table = 'student_semester_enrollment';

    protected $fillable = [
        'student_number',
        'semester_id',
        'section_id',
        'enrollment_status',
        'enrollment_date'
    ];

    protected $casts = [
        'enrollment_date' => 'date'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    public function semester()
    {
        return $this->belongsTo(\App\Models\Semester::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
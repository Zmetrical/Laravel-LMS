<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'student_list'; // Your table name

    protected $fillable = [
        'user_id',
        'student_number',
        'enrollment_date',
        'year_level',
        'section',
        'status'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    // Student belongs to a user
    public function user()
    {
        return $this->belongsTo(User_List::class, 'user_id');
    }
}

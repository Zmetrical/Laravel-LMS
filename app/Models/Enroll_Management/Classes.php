<?php

namespace App\Models\Enroll_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'class_code',
        'class_name',
        'ww_perc',
        'pt_perc',
        'qa_perce'
    ];

    protected $casts = [
        'ww_perc' => 'integer',
        'pt_perc' => 'integer',
        'qa_perce' => 'integer',
    ];

    public function sections()
    {
        return $this->belongsToMany(
            Section::class,
            'section_class_matrix',
            'class_id',
            'section_id'
        );
    }

    public function teachers()
    {
        return $this->belongsToMany(
            Teacher::class,
            'teacher_class_matrix',
            'class_id',
            'teacher_id'
        );
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'student_class_matrix',
            'class_code',
            'student_number',
            'class_code',
            'student_number'
        );
    }
}
<?php

namespace App\Models\User_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Enroll_Management\Section;
use App\Models\Enroll_Management\Classes;

class Student extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'students';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_number',
        'student_password',

        'email',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'profile_image',
        'section_id',
        'student_type',
        'enrollment_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'student_password',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'enrollment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the password for the user.
     * Override to use student_password instead of password
     */
    public function getAuthPassword()
    {
        return $this->student_password;
    }

    /**
     * Get the name of the unique identifier for the user.
     * Override to use student_number instead of email
     */
    public function getAuthIdentifierName()
    {
        return 'student_number';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier()
    {
        return $this->student_number;
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    // Relationships
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function classes()
    {
        return $this->belongsToMany(
            Classes::class,
            'student_class_matrix',
            'student_number',
            'class_code',
            'student_number',
            'class_code'
        );
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    // Scopes
    public function scopeRegular($query)
    {
        return $query->where('student_type', 'regular');
    }

    public function scopeIrregular($query)
    {
        return $query->where('student_type', 'irregular');
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }
}

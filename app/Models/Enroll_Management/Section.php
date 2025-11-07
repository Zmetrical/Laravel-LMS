<?php

namespace App\Models\Enroll_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User_Management\Student;
use App\Models\User_Management\Teacher;

class Section extends Model
{
    use HasFactory;

    protected $table = 'sections';

    protected $fillable = [
        'code',
        'name',
        'strand_id',
        'level_id',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function strand()
    {
        return $this->belongsTo(Strand::class, 'strand_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    public function classes()
    {
        return $this->belongsToMany(
            Classes::class,
            'section_class_matrix',
            'section_id',
            'class_id'
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    public function scopeByStrand($query, $strandId)
    {
        return $query->where('strand_id', $strandId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->level->name} - {$this->strand->name} {$this->name}";
    }

    public function getStudentCountAttribute()
    {
        return $this->students()->count();
    }

    public function getClassCountAttribute()
    {
        return $this->classes()->count();
    }
}
<?php

namespace App\Models\Class_Management;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'class_code',
        'class_name',
        'ww_perc',
        'pt_perc',
        'qa_perce',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scope for active classes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope for inactive classes
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // Scope for search by code
    public function scopeSearchByCode($query, $code)
    {
        return $query->where('class_code', 'like', '%' . $code . '%');
    }

    // Scope for search by name
    public function scopeSearchByName($query, $name)
    {
        return $query->where('class_name', 'like', '%' . $name . '%');
    }
}

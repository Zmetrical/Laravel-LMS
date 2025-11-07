<?php

namespace App\Models\Enroll_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Strand extends Model
{
    use HasFactory;

    protected $table = 'strands';

    protected $fillable = ['code', 'name', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class, 'strand_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
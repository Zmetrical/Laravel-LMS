<?php

namespace App\Models\Enroll_Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $table = 'levels';

    protected $fillable = ['name'];

    public function sections()
    {
        return $this->hasMany(Section::class, 'level_id');
    }
}
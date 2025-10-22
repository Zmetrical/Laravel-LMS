<?php

namespace App\Models\User_Management;

use App\Models\ParentModel;

class Teacher extends ParentModel
{
    protected $table = 'teachers';    
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'email',
        'phone',

        'user',
        'password',
        'status',
        'created_at',
        'updated_at'
    ];
}

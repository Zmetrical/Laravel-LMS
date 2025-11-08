<?php

namespace App\Models\User_Management;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Teacher extends Authenticatable
{
    use Notifiable;

    protected $guard = 'teacher';
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
        'profile_image',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Get full name
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    }

    public function username()
    {
        return 'email';
    }

    // Override the password field
    public function getAuthPassword()
    {
        return $this->password;
    }
}

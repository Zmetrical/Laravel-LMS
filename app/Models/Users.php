<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends ParentModel
{
    // Specify the table name (Laravel expects 'user__lists' by default)
    protected $table = 'users_list';
    
    // Specify which fields can be mass assigned
    protected $fillable = [
        'first_name',
        'last_name',
        'password',
        'email',
        'is_active'
    ];
    
    protected $attributes = [
        'role_id' => 1,           // Default role ID
        'is_active' => 1,         // Default active status
        'email_verified_at' => null, // Default email verification
    ];

    // Hide sensitive fields when converting to array/JSON
    protected $hidden = [
        'password',
    ];
    
    // Cast certain fields to specific data types
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}

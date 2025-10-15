<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends ParentModel
{
    protected $table = 'users'; // Your table name

    protected $fillable = [
        'first_name',
        'last_name',
        'user_name',
        'email',
        'password',

        'role_id',
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\AdminFactory> */
    use HasFactory;

    protected $table = 'admins';

    protected $fillable = [
        'admin_name',
        'admin_password',
        'email',
    ];

    protected $hidden = [
        'admin_password',
    ];

    /**
     * Get the password for authentication
     */
    public function getAuthPassword()
    {
        return $this->admin_password;
    }

    /**
     * Get the name of the unique identifier for the user
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }
}

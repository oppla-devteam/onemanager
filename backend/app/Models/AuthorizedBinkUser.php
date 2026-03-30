<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorizedBinkUser extends Model
{
    protected $fillable = [
        'bink_username',
        'display_name',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];
}

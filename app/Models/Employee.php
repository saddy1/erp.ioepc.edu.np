<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // so we can log them in
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Authenticatable
{
    protected $fillable = [
        'department_id',
        'full_name',
        'contact',
        'employee_type',

        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

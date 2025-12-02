<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Student extends Model
{
    protected $fillable = [
        'symbol_no',
        'name',
        'faculty_id',
        'section_id',
        'batch',
        'year',
        'part',
        'semester',
        'contact',
        'email',
        'dob',
        'father_name',
        'mother_name',
        'gender',
        'municipality',
        'ward',
        'district',

        // login-related fields
        'password',
        'can_login',
        'must_change_password',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'can_login'          => 'boolean',
        'must_change_password' => 'boolean',
    ];

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function role()
    {
        return $this->hasOne(StudentRole::class);
    }

    /*
     |--------------------------------------------------------------------------
     | Model Events (keep semester consistent with year + part)
     |--------------------------------------------------------------------------
     */
    protected static function booted()
    {
        static::saving(function (Student $student) {
            if ($student->year && $student->part) {
                $student->semester = (($student->year - 1) * 2) + $student->part;
            }
        });
    }

    /*
     |--------------------------------------------------------------------------
     | Query Scopes (used in your student listing / filters)
     |--------------------------------------------------------------------------
     */
    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['faculty_id'])) {
            $q->where('faculty_id', $filters['faculty_id']);
        }

        if (!empty($filters['semester'])) {
            $q->where('semester', (int) $filters['semester']);
        }

        if (!empty($filters['batch'])) {
            $q->where('batch', $filters['batch']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('symbol_no', 'like', "%{$search}%");
            });
        }

        return $q;
    }

    /*
     |--------------------------------------------------------------------------
     | Mutators / helpers for login + roles
     |--------------------------------------------------------------------------
     */

    // Hash password when setting (no manual Hash::make() elsewhere)
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function isCr(): bool
    {
        return $this->role && $this->role->role === 'CR';
    }

    public function isVcr(): bool
    {
        return $this->role && $this->role->role === 'VCR';
    }
}

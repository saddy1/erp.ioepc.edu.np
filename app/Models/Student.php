<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'roll_no',
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
    ];

    // Relationships
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    // Automatically keep semester consistent with year & part
    protected static function booted()
    {
        static::saving(function (Student $student) {
            if ($student->year && $student->part) {
                $student->semester = (($student->year - 1) * 2) + $student->part;
            }
        });
    }

    // Filters used on index
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
}

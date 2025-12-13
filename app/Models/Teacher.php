<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'nick_name',
        'email',
        'phone',
        'faculty_id',
        'is_active',
        'password',
        'must_change_password',
    ];

    protected $hidden = ['password'];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function routines()
    {
        return $this->hasMany(Routine::class);
    }

    // For later when we create password-based login:
    public function setPasswordAttribute($value)
    {
        if ($value && !str_starts_with($value, '$2y$')) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }
    public static function boot()
{
    parent::boot();

    static::creating(function ($teacher) {
        $teacher->nick_name = self::makeNick($teacher->name);
    });

    static::updating(function ($teacher) {
        if ($teacher->isDirty('name') || empty($teacher->nick_name)) {
            $teacher->nick_name = self::makeNick($teacher->name);
        }
    });
}

public static function makeNick($name)
{
    if (!$name) return null;

    $parts = preg_split('/\s+/', trim($name));

    $letters = array_map(fn($p) => strtoupper($p[0] ?? ''), $parts);

    return implode('', $letters); // e.g. SKP
}


// app/Models/Teacher.php

public function departmentRoles()
{
    return $this->hasMany(DepartmentTeacherRole::class);
}

public function hodDepartments()
{
    return $this->belongsToMany(Department::class, 'department_teacher_roles')
        ->wherePivot('role', 'hod');
}

public function deputyDepartments()
{
    return $this->belongsToMany(Department::class, 'department_teacher_roles')
        ->wherePivot('role', 'deputy_hod');
}

/**
 * Faculties that this teacher manages as HOD or Deputy HOD.
 * Used for permissions: routines, students, teachers, analytics.
 */
public function managedFacultyIds(): array
{
    $deptIds = $this->departmentRoles()
        ->pluck('department_id')
        ->unique()
        ->toArray();

    if (empty($deptIds)) {
        return [];
    }

    return \DB::table('department_faculty')
        ->whereIn('department_id', $deptIds)
        ->pluck('faculty_id')
        ->unique()
        ->toArray();
}


}

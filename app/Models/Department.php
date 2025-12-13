<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['code', 'name'];

    public function faculties()
    {
        return $this->belongsToMany(Faculty::class, 'department_faculty');
    }

    public function teacherRoles()
    {
        return $this->hasMany(DepartmentTeacherRole::class);
    }

    public function hod()
    {
        return $this->hasOne(DepartmentTeacherRole::class)
            ->where('role', 'hod');
    }

    public function deputyHods()
    {
        return $this->hasMany(DepartmentTeacherRole::class)
            ->where('role', 'deputy_hod');
    }
}

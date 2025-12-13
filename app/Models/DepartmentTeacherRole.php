<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentTeacherRole extends Model
{
    protected $fillable = [
        'department_id',
        'teacher_id',
        'role',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}

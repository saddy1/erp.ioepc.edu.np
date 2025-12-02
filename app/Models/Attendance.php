<?php
// app/Models/Attendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'routine_id',
        'student_id',
        'teacher_id',
        'date',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}

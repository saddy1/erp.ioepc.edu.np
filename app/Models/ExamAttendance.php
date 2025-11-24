<?php
// app/Models/ExamAttendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttendance extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_date',
        'batch',
        'faculty_id',
        'subject_code',
        'symbol_no',
        'status',
    ];

    protected $casts = [
        'exam_date' => 'string',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}

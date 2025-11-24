<?php
// app/Models/ExamSeat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSeat extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_date',
        'batch',
        'room_id',
        'faculty_id',
        'subject_code',
        'symbol_no',
        'column_no',
        'row_no',
        'side',
        'bench_index',
    ];

    protected $casts = [
        'exam_date' => 'string',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatAllocation extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_date',
        'room_id',
        'faculty_id',
        'subject_code',
        'batch',
        'column_no',
        'row_no',
        'side',
        'exam_roll_no',
    ];

    protected $casts = [
        'exam_date' => 'date',
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

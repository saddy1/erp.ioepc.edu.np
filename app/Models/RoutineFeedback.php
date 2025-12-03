<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutineFeedback extends Model
{
    protected $fillable = [
        'routine_id',
        'student_id',
        'class_date',
        'status',
    ];
    protected $casts = [
        'class_date' => 'date',
    ];

    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

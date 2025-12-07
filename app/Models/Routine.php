<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Routine extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'batch',
        'year',
        'semester',
        'section_id',
        'period_id',
        'day_of_week',
        'group',
        'type',
        'subject_id',
        'teacher_id',
        'room_id',
        'academic_year',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

 public function teachers()
{
    return $this->belongsToMany(Teacher::class, 'routine_teacher');
}


    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Scope to filter by basic parameters (we'll re-use it later)
    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['faculty_id'])) {
            $q->where('faculty_id', $filters['faculty_id']);
        }
        if (!empty($filters['batch'])) {
            $q->where('batch', $filters['batch']);
        }
        if (!empty($filters['semester'])) {
            $q->where('semester', $filters['semester']);
        }
        if (!empty($filters['section_id'])) {
            $q->where('section_id', $filters['section_id']);
        }
        if (!empty($filters['day_of_week'])) {
            $q->where('day_of_week', $filters['day_of_week']);
        }
        if (!empty($filters['teacher_id'])) {
            $q->where('teacher_id', $filters['teacher_id']);
        }

        return $q;
    }
    public function attendances()
{
    return $this->hasMany(Attendance::class);
}

public function classFeedbacks()
{
    return $this->hasMany(RoutineFeedback::class);
}

}

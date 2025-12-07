<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
     public function scopeBetweenDates(Builder $q, $from, $to): Builder
    {
        return $q->whereBetween('class_date', [$from, $to]);
    }

    public function scopeForFaculty(Builder $q, $facultyId): Builder
    {
        if (!$facultyId) return $q;

        return $q->whereHas('routine', function (Builder $qq) use ($facultyId) {
            $qq->where('faculty_id', $facultyId);
        });
    }

    public function scopeForTeacher(Builder $q, $teacherId): Builder
    {
        if (!$teacherId) return $q;

        return $q->whereHas('routine', function (Builder $qq) use ($teacherId) {
            $qq->where('teacher_id', $teacherId)
               ->orWhereHas('teachers', function ($qqq) use ($teacherId) {
                    $qqq->where('teacher_id', $teacherId);
               });
        });
    }

    public function scopeForSection(Builder $q, $sectionId): Builder
    {
        if (!$sectionId) return $q;

        return $q->whereHas('routine', function (Builder $qq) use ($sectionId) {
            $qq->where('section_id', $sectionId);
        });
    }

    public function scopeForSemester(Builder $q, $semester): Builder
    {
        if (!$semester) return $q;

        return $q->whereHas('routine', function (Builder $qq) use ($semester) {
            $qq->where('semester', $semester);
        });
    }

    public function scopeForGroup(Builder $q, $groupId): Builder
    {
        if (!$groupId) return $q;

        return $q->whereHas('student', function (Builder $qq) use ($groupId) {
            $qq->where('group_id', $groupId);
        });
    }
}

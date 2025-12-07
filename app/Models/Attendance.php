<?php
// app/Models/Attendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


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
  

   public function scopeBetweenDates(Builder $q, $from, $to): Builder
    {
        return $q->whereBetween('date', [$from, $to]);
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

        return $q->where('teacher_id', $teacherId);
    }

    public function scopeForStudent(Builder $q, $studentId): Builder
    {
        if (!$studentId) return $q;

        return $q->where('student_id', $studentId);
    }

    public function scopeForSection(Builder $q, $sectionId): Builder
    {
        if (!$sectionId) return $q;

        return $q->whereHas('student', function (Builder $qq) use ($sectionId) {
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

    public function scopeForBatch(Builder $q, $batch): Builder
    {
        if (!$batch) return $q;

        return $q->whereHas('routine', function (Builder $qq) use ($batch) {
            $qq->where('batch', $batch);
        });
    }

    public function scopeForGroup(Builder $q, $groupId): Builder
    {
        if (!$groupId) return $q;

        // adjust to your schema if group is on routine instead of student
        return $q->whereHas('student', function (Builder $qq) use ($groupId) {
            $qq->where('group_id', $groupId);
        });
    }
    public function scopeForSubject(Builder $q, $subjectId): Builder
{
    if (!$subjectId) return $q;

    return $q->whereHas('routine', function (Builder $qq) use ($subjectId) {
        $qq->where('subject_id', $subjectId);
    });
}


}

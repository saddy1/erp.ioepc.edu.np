<?php
// app/Models/FacultySemesterSubject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultySemesterSubject extends Model
{
    protected $fillable = [
        'faculty_id',
        'semester',
        'batch',
        'subject_id',
        'subject_code',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}

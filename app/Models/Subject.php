<?php
// app/Models/Subject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'code',
        'name',
        'has_practical',
    ];

    protected $casts = [
        'has_practical' => 'boolean',
    ];

    public function semesterBindings()
    {
        return $this->hasMany(FacultySemesterSubject::class);
    }
}

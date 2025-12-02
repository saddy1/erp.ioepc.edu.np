<?php

// app/Models/StudentRole.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRole extends Model
{
    protected $fillable = [
        'student_id',
        'section_id',
        'year',
        'semester',
        'batch',   // keep if you already added it
        'role',    // 'CR' or 'VCR'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}

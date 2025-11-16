<?php
// app/Models/Student.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'campus_roll_no',
        'name',
        'campus_code',
        'batch_code',
        'program_code',
        'faculty_id',
    ];

    public function registrations()
    {
        return $this->hasMany(ExamRegistration::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassFeedback extends Model
{
    protected $fillable = [
        'routine_id',
        'student_id',
        'date',
        'was_taught',
        'remarks',
    ];

    protected $casts = [
        'was_taught' => 'boolean',
        'date'       => 'date',
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

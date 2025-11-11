<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester',
        'batch',
        'exam_title',
        'start_time',
        'end_time',
        'first_exam_date_bs',
        'status',
    ];
    public static function semesterNumbers(string $type): array
    {
        return $type === 'odd' ? [1,3,5,7,9] : [2,4,6,8,10];
    }
}

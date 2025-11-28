<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift',
        'order',
        'label',
        'start_time',
        'end_time',
    ];

    public function routines()
    {
        return $this->hasMany(Routine::class);
    }

    public function scopeMorning($q)
    {
        return $q->where('shift', 'morning');
    }

    public function scopeDay($q)
    {
        return $q->where('shift', 'day');
    }
}

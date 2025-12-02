<?php
// app/Http/Controllers/Teacher/TeacherDashboardController.php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Routine;
use App\Models\Period;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $teacher = Teacher::findOrFail(session('teacher_id'));

        $map = [
            'Sun' => 'sun',
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
        ];
        $day = $map[now()->format('D')] ?? 'sun';

        // All routines of today for this teacher (pivot + primary)
        $routines = Routine::with(['period','subject','section','faculty'])
            ->where('day_of_week', $day)
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                  ->orWhereHas('teachers', function ($qq) use ($teacher) {
                      $qq->where('teacher_id', $teacher->id);
                  });
            })
            ->orderBy('period_id')
            ->get();

        return view('Frontend.teacher.dashboard', compact('teacher','routines','day'));
    }
}

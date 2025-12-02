<?php
// app/Http/Controllers/Student/StudentDashboardController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Routine;
use App\Models\Period;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Student::with(['faculty','section','role'])->findOrFail(session('student_id'));

        return view('Frontend.student.dashboard', compact('student'));
    }

    public function routine(Request $request)
    {
        $student = Student::findOrFail(session('student_id'));

        $day = $request->get('day_of_week');
        if (!$day) {
            // default to today
            $map = [
                'Sun' => 'sun',
                'Mon' => 'mon',
                'Tue' => 'tue',
                'Wed' => 'wed',
                'Thu' => 'thu',
                'Fri' => 'fri',
                'Sat' => 'sat', // probably unused
            ];
            $day = $map[now()->format('D')] ?? 'sun';
        }

        $routines = Routine::with(['period','subject','teachers','room'])
            ->where('faculty_id', $student->faculty_id)
            ->where('batch', $student->batch)
            ->where('semester', $student->semester)
            ->where('section_id', $student->section_id)
            ->where('day_of_week', $day)
            ->orderBy('period_id')
            ->get();

        $periods = Period::orderBy('order')->get();

        return view('Frontend.student.routine', compact('student','routines','periods','day'));
    }
}

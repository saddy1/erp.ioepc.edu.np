<?php
// app/Http/Controllers/Teacher/AttendanceController.php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Routine;
use App\Models\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function show(Routine $routine)
    {
        $teacherId = session('teacher_id');
        $today = now()->toDateString();

        // Guard: teacher must be assigned to this routine
        if (!$this->teacherAssignedToRoutine($teacherId, $routine)) {
            abort(403, 'Not your class.');
        }

        // Guard: only today allowed
        $map = [
            'Sun' => 'sun',
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
        ];
        $todayDow = $map[now()->format('D')] ?? null;

        if ($routine->day_of_week !== $todayDow) {
            abort(403, 'You can take attendance only for today\'s classes.');
        }

        // Students of that faculty/batch/semester/section
        $students = Student::where('faculty_id', $routine->faculty_id)
            ->where('section_id', $routine->section_id)
            ->where('batch', $routine->batch)
            ->where('semester', $routine->semester)
            ->orderBy('symbol_no')
            ->get();

        // Existing attendance (if already taken)
        $existing = Attendance::where('routine_id', $routine->id)
            ->where('date', $today)
            ->get()
            ->keyBy('student_id');

        return view('Frontend.teacher.attendance', compact(
            'routine', 'students', 'existing', 'today'
        ));
    }

    public function store(Request $request, Routine $routine)
    {
        $teacherId = session('teacher_id');
        $today = now()->toDateString();

        if (!$this->teacherAssignedToRoutine($teacherId, $routine)) {
            abort(403, 'Not your class.');
        }

        $map = [
            'Sun' => 'sun',
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
        ];
        $todayDow = $map[now()->format('D')] ?? null;

        if ($routine->day_of_week !== $todayDow) {
            abort(403, 'You can take attendance only for today\'s classes.');
        }

        $data = $request->validate([
            'status'   => ['required', 'array'],
            'status.*' => ['required', 'in:P,A'], // key = student_id
        ]);

        foreach ($data['status'] as $studentId => $status) {
            Attendance::updateOrCreate(
                [
                    'routine_id' => $routine->id,
                    'student_id' => $studentId,
                    'date'       => $today,
                ],
                [
                    'teacher_id' => $teacherId,
                    'status'     => $status,
                ]
            );
        }

        return redirect()->route('teacher.dashboard')->with('ok', 'Attendance saved.');
    }

    private function teacherAssignedToRoutine(int $teacherId, Routine $routine): bool
    {
        if ($routine->teacher_id == $teacherId) {
            return true;
        }

        return $routine->teachers()->where('teacher_id', $teacherId)->exists();
    }
}

<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Routine;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $teacherId = session('teacher_id');
        $today     = now()->toDateString();

        $data = $request->validate([
            'date'         => ['required', 'date'],
            'routine_ids'  => ['required', 'array'],
            'routine_ids.*'=> ['exists:routines,id'],
            'attendance'   => ['required', 'array'],
        ]);

        if ($data['date'] !== $today) {
            return back()->with('error', 'You can only mark attendance for today.');
        }

        // Map today to slug
        $dayShort = now()->format('D');
        $mapDow   = [
            'Sun' => 'sun',
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
        ];
        $todaySlug = $mapDow[$dayShort] ?? 'sun';

        // Load all routines in this merged block
        $routines = Routine::with('teachers')
            ->whereIn('id', $data['routine_ids'])
            ->get();

        if ($routines->isEmpty()) {
            return back()->with('error', 'No valid class found for attendance.');
        }

        // Verify all routines belong to this teacher and are for today
        foreach ($routines as $routine) {
            $isOwn = $routine->teacher_id == $teacherId ||
                $routine->teachers()->where('teacher_id', $teacherId)->exists();

            if (!$isOwn || $routine->day_of_week !== $todaySlug) {
                return back()->with('error', 'You are not allowed to mark attendance for one or more selected classes.');
            }
        }

        // Counters (per student, not multiplied by number of periods)
        $presentCount = 0;
        $absentCount  = 0;

        foreach ($data['attendance'] as $studentId => $status) {
            $finalStatus = $status === 'A' ? 'A' : 'P';

            // Save for each routine in this merged block
            foreach ($routines as $routine) {
                Attendance::updateOrCreate(
                    [
                        'routine_id' => $routine->id,
                        'student_id' => $studentId,
                        'date'       => $today,
                    ],
                    [
                        'teacher_id' => $teacherId,
                        'status'     => $finalStatus,
                    ]
                );
            }

            if ($finalStatus === 'P') {
                $presentCount++;
            } else {
                $absentCount++;
            }
        }

        $totalStudents = count($data['attendance']);
        $message = "Attendance saved successfully! Total: {$totalStudents} | Present: {$presentCount} | Absent: {$absentCount}";

        // Go back to dashboard WITHOUT merged param => students panel hidden
        return redirect()
            ->route('teacher.dashboard')
            ->with('ok', $message);
    }
}

<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Routine;
use App\Models\Period;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeacherDashboardController extends Controller
{
    
    public function index(Request $request)
    {
        $teacher = Teacher::with('faculty')->findOrFail(session('teacher_id'));

        // Day labels for weekly + today
        $dayLabels = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];

        $now      = now();
        $dayShort = $now->format('D'); // Sun, Mon, ...
        $mapDow   = [
            'Sun' => 'sun',
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
        ];
        $todaySlug  = $mapDow[$dayShort] ?? 'sun';
        $todayDate  = $now->toDateString();
        $todayLabel = $dayLabels[$todaySlug] ?? ucfirst($todaySlug);

        // ---------- FULL WEEKLY ROUTINE (for grid) ----------
        $allRoutines = Routine::with(['period','subject','section','faculty','room','teachers'])
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                  ->orWhereHas('teachers', function ($qq) use ($teacher) {
                      $qq->where('teacher_id', $teacher->id);
                  });
            })
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get();

        $weeklyRoutines = $allRoutines->groupBy('day_of_week');

        $usedPeriodIds = $allRoutines->pluck('period_id')
            ->filter()
            ->unique()
            ->values();

        $gridPeriods = Period::whereIn('id', $usedPeriodIds)
            ->orderBy('order')
            ->get();

        // ---------- TODAY'S SLOTS (per-period) ----------
        $todaySlots = $allRoutines
            ->where('day_of_week', $todaySlug)
            ->values();

        // Mark which routine already has attendance today
        if ($todaySlots->isNotEmpty()) {
            $markedIds = Attendance::whereIn('routine_id', $todaySlots->pluck('id'))
                ->where('date', $todayDate)
                ->pluck('routine_id')
                ->unique();

            $todaySlots->each(function ($r) use ($markedIds) {
                $r->attendance_marked = $markedIds->contains($r->id);
            });
        }

        // ---------- MERGE CONTINUOUS SLOTS (like student dashboard) ----------
        $mergedToday = [];

        $sortedToday = $todaySlots->sortBy(function ($r) {
            return $r->period->order ?? 0;
        })->values();

        foreach ($sortedToday as $r) {
            if (!$r->period) {
                continue;
            }

            // teacher key (works for single + multi-teacher)
            if ($r->teachers && $r->teachers->count()) {
                $teacherKey = $r->teachers->pluck('id')->sort()->join('-');
            } else {
                $teacherKey = $r->teacher_id ?? $teacher->id;
            }

            // group key: same subject + teacher(s) + group + type + room + faculty + section + sem
            $key = implode('|', [
                $r->subject_id ?? 0,
                $teacherKey,
                $r->group ?? '',
                $r->type ?? '',
                $r->room_id ?? 0,
                $r->faculty_id ?? 0,
                $r->section_id ?? 0,
                $r->semester ?? 0,
            ]);

            $start = Carbon::parse($r->period->start_time)->format('H:i');
            $end   = Carbon::parse($r->period->end_time)->format('H:i');

            if (!isset($mergedToday[$key])) {
                $mergedToday[$key] = [
                    'key'         => $key,
                    'routine_ids' => [$r->id],
                    'start'       => $start,
                    'end'         => $end,
                    'sample'      => $r,   // for faculty/section/subject etc
                ];
            } else {
                // expand time range
                if ($start < $mergedToday[$key]['start']) {
                    $mergedToday[$key]['start'] = $start;
                }
                if ($end > $mergedToday[$key]['end']) {
                    $mergedToday[$key]['end'] = $end;
                }
                $mergedToday[$key]['routine_ids'][] = $r->id;
            }
        }

        $mergedToday = array_values($mergedToday); // reindex

        // ---------- SELECTED MERGED BLOCK (when clicking "Take Attendance") ----------
        $selectedGroup = null;
        $selectedSlot  = null;
        $students      = collect();
        $attendanceMap = [];

        if ($request->filled('merged')) {
            $mergedKey = $request->get('merged');

            foreach ($mergedToday as $g) {
                if ($g['key'] === $mergedKey) {
                    $selectedGroup = $g;
                    $selectedSlot  = $g['sample'];
                    break;
                }
            }

            if ($selectedSlot) {
                // students: faculty + semester + section (+ batch if you use it)
                $studentsQuery = Student::query()
                    ->where('faculty_id', $selectedSlot->faculty_id)
                    ->where('semester',   $selectedSlot->semester)
                    ->where('section_id', $selectedSlot->section_id);

                if (!empty($selectedSlot->batch)) {
                    $studentsQuery->where('batch', $selectedSlot->batch);
                }

                $students = $studentsQuery
                    ->orderBy('symbol_no')
                    ->orderBy('name')
                    ->get();

                // Prefill attendance from FIRST routine in that block (canonical)
                $firstRoutineId = $selectedGroup['routine_ids'][0] ?? null;

                if ($firstRoutineId) {
                    $attendanceRows = Attendance::where('routine_id', $firstRoutineId)
                        ->where('date', $todayDate)
                        ->get();

                    $attendanceMap = $attendanceRows->pluck('status', 'student_id')->toArray();
                }
            }
        }

        return view('Frontend.teacher.dashboard', [
            'teacher'        => $teacher,
            'dayLabels'      => $dayLabels,
            'weeklyRoutines' => $weeklyRoutines,
            'gridPeriods'    => $gridPeriods,
            'day'            => $todaySlug,
            'todayLabel'     => $todayLabel,
            'todayDate'      => $todayDate,
            'todaySlots'     => $todaySlots,
            'mergedToday'    => $mergedToday,
            'selectedGroup'  => $selectedGroup,
            'selectedSlot'   => $selectedSlot,
            'students'       => $students,
            'attendanceMap'  => $attendanceMap,
        ]);
    }
}

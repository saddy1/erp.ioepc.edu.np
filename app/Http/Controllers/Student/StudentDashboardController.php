<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Routine;
use App\Models\Period;
use App\Models\RoutineFeedback;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Student::with(['faculty','section','role'])
            ->findOrFail(session('student_id'));

        // Day labels
        $dayLabels = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
        ];

        // All routines for this student's faculty/batch/semester/section
        $allRoutines = Routine::with(['period','subject','teachers','room'])
            ->where('faculty_id', $student->faculty_id)
            ->where('batch',      $student->batch)
            ->where('semester',   $student->semester)
            ->where('section_id', $student->section_id)
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get();

        // Group by day for grid
        $weeklyRoutines = $allRoutines->groupBy('day_of_week');

        // Only show periods that are actually allocated for this section
        $usedPeriodIds = $allRoutines->pluck('period_id')
            ->filter()
            ->unique()
            ->values();

        $gridPeriods = Period::whereIn('id', $usedPeriodIds)
            ->orderBy('order')
            ->get();

        // Determine the shift
        $shift = $gridPeriods->first()->shift ?? null;

        // Build shift label
        $shiftLabel = null;
        if ($shift && $gridPeriods->isNotEmpty()) {
            $firstStart = $gridPeriods->min('start_time');
            $lastEnd    = $gridPeriods->max('end_time');

            $startStr = $firstStart ? substr($firstStart, 0, 5) : null;
            $endStr   = $lastEnd    ? substr($lastEnd, 0, 5)    : null;

            $shiftLabel = ucfirst($shift) . ' Shift';
            if ($startStr && $endStr) {
                $shiftLabel .= " ({$startStr} – {$endStr})";
            }
        }

        // Today's routine
        $now      = now();
        $dayShort = $now->format('D');
        $mapDow   = [
            'Sun' => 'sun',
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
        ];
        $todaySlug        = $mapDow[$dayShort] ?? 'sun';
        $currentDayLabel  = $dayLabels[$todaySlug] ?? $todaySlug;
        $currentDate      = $now->toDateString();

        $todayRoutines = $allRoutines
            ->where('day_of_week', $todaySlug)
            ->values();

        // Load feedback
        $feedbackByRoutine = collect();
        if ($todayRoutines->isNotEmpty()) {
            $feedbackByRoutine = RoutineFeedback::whereIn('routine_id', $todayRoutines->pluck('id'))
                ->where('class_date', $currentDate)
                ->where('student_id', $student->id)
                ->get()
                ->keyBy('routine_id');
        }

        return view('Frontend.student.dashboard', compact(
            'student',
            'dayLabels',
            'weeklyRoutines',
            'gridPeriods',
            'shiftLabel',
            'todayRoutines',
            'currentDayLabel',
            'currentDate',
            'feedbackByRoutine'
        ));
    }

    public function routine(Request $request)
    {
        $student = Student::findOrFail(session('student_id'));

        $day = $request->get('day_of_week');
        if (!$day) {
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
        }

        $routines = Routine::with(['period','subject','teachers','room'])
            ->where('faculty_id', $student->faculty_id)
            ->where('batch',      $student->batch)
            ->where('semester',   $student->semester)
            ->where('section_id', $student->section_id)
            ->where('day_of_week', $day)
            ->orderBy('period_id')
            ->get();

        $periods = Period::orderBy('order')->get();

        return view('Frontend.student.routine', compact('student','routines','periods','day'));
    }

    /**
     * Store / update taught / not_taught feedback for a single routine
     */
    public function storeFeedback(Request $request, Routine $routine)
    {
        $student = Student::findOrFail(session('student_id'));

        // Only CR / VCR allowed
        if (! $student->isCr() && ! $student->isVcr()) {
            return response()->json([
                'ok' => false,
                'message' => 'Only CR / VCR can submit class feedback.'
            ], 403);
        }

        $data = $request->validate([
            'status'     => ['required', 'in:taught,not_taught'],
            'class_date' => ['required', 'date'],
        ]);

        $feedback = RoutineFeedback::updateOrCreate(
            [
                'routine_id' => $routine->id,
                'student_id' => $student->id,
                'class_date' => $data['class_date'],
            ],
            [
                'status' => $data['status'],
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'ok'     => true,
                'status' => $feedback->status,
                'message' => 'Class status updated successfully.'
            ]);
        }

        return back()->with('ok', 'Class status updated.');
    }

    /**
     * Bulk update for merged class slots (multiple routine IDs)
     */
    public function bulkUpdate(Request $request)
    {
        // Get logged-in student
        $student = Student::findOrFail(session('student_id'));

        // Only CR / VCR allowed
        if (! $student->isCr() && ! $student->isVcr()) {
            return response()->json([
                'ok' => false,
                'message' => 'Only CR / VCR can submit class feedback.'
            ], 403);
        }

        // Validate request
        $data = $request->validate([
            'routine_ids' => 'required|array',
            'routine_ids.*' => 'exists:routines,id',
            'status' => 'required|in:taught,not_taught',
            'class_date' => 'required|date',
        ]);

        // Update or create feedback for each routine
        foreach ($data['routine_ids'] as $routineId) {
            RoutineFeedback::updateOrCreate(
                [
                    'routine_id' => $routineId,
                    'class_date' => $data['class_date'],
                    'student_id' => $student->id,
                ],
                [
                    'status' => $data['status'],
                ]
            );
        }

        return response()->json([
            'ok' => true,
            'message' => 'Class status updated successfully for all periods.'
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\Period;
use App\Models\Routine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoutineController extends Controller
{
    public function index(Request $request)
    {
        // 1) Master lists
        $faculties = Faculty::orderBy('name')->get();
        $batches   = Student::select('batch')
            ->distinct()
            ->orderBy('batch', 'desc')
            ->pluck('batch');
        $semesters = [1, 2, 3, 4, 5, 6, 7, 8];

        // 2) Filters from query
        $filters = [
            'faculty_id' => $request->input('faculty_id'),
            'batch'      => $request->input('batch'),
            'semester'   => $request->input('semester'),
            'section_id' => $request->input('section_id'),
            'day_of_week' => $request->input('day_of_week'),
            'teacher_id' => $request->input('teacher_id'),
            'shift'      => $request->input('shift'),
            'subject_batch'  => $request->input('subject_batch'),
        ];

        // 3) Sections & subjects (dependent on faculty + semester)
        $sections = collect();
        $subjects = collect();

        if ($filters['faculty_id']) {
            // sections of that faculty
            $sections = Section::where('faculty_id', $filters['faculty_id'])
                ->orderBy('name')
                ->get();
        }
        if ($filters['faculty_id'] && $filters['semester']) {
            // map UI value → db tinyint
            $subjectBatchCode = null;
            if ($filters['subject_batch'] === 'new') {
                $subjectBatchCode = 1;
            } elseif ($filters['subject_batch'] === 'old') {
                $subjectBatchCode = 2;
            }

            $subjects = Subject::whereHas('semesterBindings', function ($q) use ($filters, $subjectBatchCode) {
                $q->where('faculty_id', $filters['faculty_id'])
                    ->where('semester',   $filters['semester']);

                if (!is_null($subjectBatchCode)) {
                    $q->where('batch', $subjectBatchCode); // 1 or 2
                }
            })
                ->orderBy('code')
                ->get();
        }



        // 4) Teachers and rooms
        $teachers = Teacher::orderBy('name')->get();   // any teacher can teach any subject now
        $rooms    = Room::orderBy('room_no')->get();

        // 5) Periods filtered by shift (for dropdown + grid)
        $periods = Period::when($filters['shift'], function ($q, $shift) {
            $q->where('shift', $shift);
        })
            ->orderBy('order')   // correct order: 1,2,3,...
            ->get();

        // 6) Main routine list (paginated so ->total() & ->links() work)
        $routines = Routine::with(['faculty', 'section', 'period', 'subject', 'teachers', 'room'])
            ->filter($filters)               // your existing local scope
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->paginate(50);

        // 7) Grid helpers
        $gridPeriods = $periods;            // only periods of selected shift
        // Build grid as [day][period_id] = [ list of routines ]
        $grid = [];

        foreach ($routines as $r) {
            $day = $r->day_of_week;   // 'sun', 'mon', ...
            $pid = $r->period_id;     // 8, 9, 10 ...

            if (!isset($grid[$day][$pid])) {
                $grid[$day][$pid] = [];
            }

            $grid[$day][$pid][] = $r;
        }

        // 8) Days labels
        $days = [
            'sun' => 'SUN',
            'mon' => 'MON',
            'tue' => 'TUE',
            'wed' => 'WED',
            'thu' => 'THU',
            'fri' => 'FRI',
        ];

        // 9) Send everything to view
        return view('Backend.admin.routines.index', compact(
            'faculties',
            'batches',
            'semesters',
            'sections',
            'subjects',
            'teachers',
            'rooms',
            'filters',
            'routines',
            'days',
            'periods',
            'gridPeriods',
            'grid'
        ));
    }


    public function store(Request $request)
    {
        // 1) Validate base data (NO period_id here)
        $data = $this->validateStore($request);
        $teacherIds = $data['teacher_ids'];
        unset($data['teacher_ids']); // we'll use pivot

        // 2) Resolve start/end periods and build period range
        $startPeriod = Period::findOrFail($data['start_period_id']);
        $endPeriod   = Period::findOrFail($data['end_period_id']);

        // must be same shift
        if ($startPeriod->shift !== $endPeriod->shift) {
            return back()
                ->withErrors(['end_period_id' => 'Start and end period must be in the same shift.'])
                ->withInput();
        }

        if ($endPeriod->order < $startPeriod->order) {
            return back()
                ->withErrors(['end_period_id' => 'End period must be after or equal to start period.'])
                ->withInput();
        }

        $periodsRange = Period::where('shift', $startPeriod->shift)
            ->whereBetween('order', [$startPeriod->order, $endPeriod->order])
            ->orderBy('order')
            ->get();

        if ($periodsRange->isEmpty()) {
            return back()
                ->withErrors(['start_period_id' => 'Invalid period range selected.'])
                ->withInput();
        }

        // 3) Business validation for each period (teacher conflict etc.)
        foreach ($periodsRange as $p) {
            foreach ($teacherIds as $tid) {
                $row            = $data;
                $row['period_id']   = $p->id;
                $row['teacher_id']  = $tid; // for validation only

                $error = $this->businessValidation($row, null);
                if ($error) {
                    return back()->withErrors($error)->withInput();
                }
            }
        }

        // 4) Create routine row for EACH period in range
        foreach ($periodsRange as $p) {
            $row = $data;
            unset($row['start_period_id'], $row['end_period_id']);
            $row['period_id']  = $p->id;

            // Optionally keep a "primary" teacher for quick reference
            $row['teacher_id'] = $teacherIds[0];

            $routine = Routine::create($row);
            $routine->teachers()->sync($teacherIds);
        }

        return redirect()->route('admin.routines.index', [
            'faculty_id' => $data['faculty_id'],
            'batch'      => $data['batch'],
            'semester'   => $data['semester'],
            'section_id' => $data['section_id'],
        ])->with('ok', 'Routine entry created for selected period range.');
    }

    public function edit(Routine $routine)
    {
        $faculties = Faculty::orderBy('name')->get();
        $periods   = Period::orderBy('order')->get();

        $batches = Student::select('batch')
            ->distinct()
            ->orderBy('batch', 'desc')
            ->pluck('batch');

        $semesters = [1, 2, 3, 4, 5, 6, 7, 8];

        $sections = Section::where('faculty_id', $routine->faculty_id)
            ->orderBy('name')
            ->get();

        $subjects = Subject::whereHas('semesterBindings', function ($q) use ($routine) {
            $q->where('faculty_id', $routine->faculty_id)
                ->where('semester', $routine->semester);
        })
            ->orderBy('code')
            ->get();

        $teachers = Teacher::orderBy('name')->get();
        $rooms    = Room::orderBy('room_no')->get();

        $days = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
        ];

        return view('Backend.admin.routines.edit', compact(
            'routine',
            'faculties',
            'periods',
            'batches',
            'semesters',
            'sections',
            'subjects',
            'teachers',
            'rooms',
            'days'
        ));
    }

    public function update(Request $request, Routine $routine)
    {
        $data       = $this->validateUpdate($request, $routine->id);
        $teacherIds = $data['teacher_ids'];
        unset($data['teacher_ids']);

        // clash check for all teachers
        foreach ($teacherIds as $tid) {
            $row              = $data;
            $row['teacher_id'] = $tid;
            $error = $this->businessValidation($row, $routine->id);
            if ($error) {
                return back()->withErrors($error)->withInput();
            }
        }

        // keep primary teacher = first
        $data['teacher_id'] = $teacherIds[0];

        $routine->update($data);
        $routine->teachers()->sync($teacherIds);


        return redirect()->route('admin.routines.index', [
            'faculty_id' => $data['faculty_id'],
            'batch'      => $data['batch'],
            'semester'   => $data['semester'],
            'section_id' => $data['section_id'],
        ])->with('ok', 'Routine entry updated.');
    }

    public function destroy(Routine $routine)
    {
        $routine->delete();
        return back()->with('ok', 'Routine entry deleted.');
    }

    /**
     * AJAX meta for given faculty + semester (+batch)
     * returns sections and subjects.
     */
    public function meta(Request $request)
    {
        $facultyId = $request->input('faculty_id');
        $semester  = $request->input('semester');
        $subjectBatch  = $request->input('subject_batch'); // 'old' or 'new'

        $batch     = $request->input('batch'); // optional, in case you use it later

        if (!$facultyId || !$semester) {
            return response()->json([
                'sections' => [],
                'subjects' => [],
            ]);
        }

        $sections = Section::where('faculty_id', $facultyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectBatchCode = null;
        if ($subjectBatch === 'new') {
            $subjectBatchCode = 1;
        } elseif ($subjectBatch === 'old') {
            $subjectBatchCode = 2;
        }

        $subjects = Subject::whereHas('semesterBindings', function ($q) use ($facultyId, $semester, $subjectBatchCode) {
            $q->where('faculty_id', $facultyId)
                ->where('semester',   $semester);

            if (!is_null($subjectBatchCode)) {
                $q->where('batch', $subjectBatchCode);
            }
        })
            ->orderBy('code')
            ->get()
            ->map(function ($s) {
                return [
                    'id'            => $s->id,
                    'code'          => $s->code,
                    'name'          => $s->name,
                    'has_practical' => (bool) $s->has_practical,
                ];
            });

        return response()->json([
            'sections' => $sections,
            'subjects' => $subjects,
        ]);
    }

    // =========================
    //   VALIDATION HELPERS
    // =========================

    private function validateStore(Request $request): array
    {
        return $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'batch'      => ['required', 'string', 'max:10'],
            'semester'   => ['required', 'integer', 'between:1,8'],
            'section_id' => ['required', 'exists:sections,id'],

            'day_of_week' => ['required', Rule::in(['sun', 'mon', 'tue', 'wed', 'thu', 'fri'])],

            'start_period_id' => ['required', 'exists:periods,id'],
            'end_period_id'   => ['required', 'exists:periods,id'],

            'group'      => ['required', Rule::in(['ALL', 'A', 'B'])],
            'type'       => ['required', Rule::in(['TH', 'PR'])],

            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_ids'   => ['required', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],

            'room_id'       => ['nullable', 'exists:rooms,id'],
            'academic_year' => ['nullable', 'string', 'max:15'],
        ]);
    }

    private function validateUpdate(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'batch'      => ['required', 'string', 'max:10'],
            'semester'   => ['required', 'integer', 'between:1,8'],
            'section_id' => ['required', 'exists:sections,id'],

            'period_id'  => ['required', 'exists:periods,id'],
            'day_of_week' => ['required', Rule::in(['sun', 'mon', 'tue', 'wed', 'thu', 'fri'])],

            'group'      => ['required', Rule::in(['ALL', 'A', 'B'])],
            'type'       => ['required', Rule::in(['TH', 'PR'])],

            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_ids'   => ['required', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],

            'room_id'       => ['nullable', 'exists:rooms,id'],
            'academic_year' => ['nullable', 'string', 'max:15'],
        ]);
    }

    /**
     * Business rules applied PER single routine row (single period).
     * - TH must be group ALL
     * - PR must be group A or B and subject must have practical
     * - Section cannot have two classes in same day+period+group
     * - Teacher cannot have two classes at same day + period (across all faculties)
     */
 private function businessValidation(array $data, ?int $ignoreRoutineId = null): ?array
{
    // --- A) Period / shift must be valid ---
    $period = Period::find($data['period_id'] ?? null);
    if (!$period) {
        return ['period_id' => 'Invalid period selected.'];
    }
    $newShift = $period->shift;   // 'morning' or 'day'

    // --- B) A semester-section must use only ONE shift (morning OR day) ---
    $existingRoutineQuery = Routine::where('faculty_id', $data['faculty_id'])
        ->where('batch',      $data['batch'])
        ->where('semester',   $data['semester'])
        ->where('section_id', $data['section_id']);

    if ($ignoreRoutineId) {
        $existingRoutineQuery->where('id', '!=', $ignoreRoutineId);
    }

    $existingRoutine = $existingRoutineQuery->with('period')->first();

    if ($existingRoutine && $existingRoutine->period) {
        $existingShift = $existingRoutine->period->shift; // 'morning' or 'day'

        if ($existingShift && $existingShift !== $newShift) {
            return [
                'period_id' => 'This semester already has classes in the '
                    . ucfirst($existingShift)
                    . ' shift. You cannot add periods from the '
                    . ucfirst($newShift)
                    . ' shift.'
            ];
        }
    }

    // -------------------------------------------------------
    // 0) Section-time slot conflicts (NEW stronger logic)
    // -------------------------------------------------------
    $slotQuery = Routine::where('faculty_id', $data['faculty_id'])
        ->where('batch',      $data['batch'])
        ->where('semester',   $data['semester'])
        ->where('section_id', $data['section_id'])
        ->where('day_of_week', $data['day_of_week'])
        ->where('period_id',  $data['period_id']);

    if ($ignoreRoutineId) {
        $slotQuery->where('id', '!=', $ignoreRoutineId);
    }

    $slotRoutines = $slotQuery->get();

    // same group already in that slot (old rule – keep)
    if ($slotRoutines->where('group', $data['group'])->isNotEmpty()) {
        return [
            'period_id' => 'This section already has another class at this day and period for the selected group.'
        ];
    }

    $hasAllGroup = $slotRoutines->where('group', 'ALL')->isNotEmpty();

    // if ALL already exists, no A/B allowed
    if ($hasAllGroup && $data['group'] !== 'ALL') {
        return [
            'group' => 'This section already has a combined (ALL) class at this time. '
                     . 'You cannot schedule Group ' . $data['group'] . ' in parallel.'
        ];
    }

    // if A/B already exists, no new ALL allowed
    if (!$hasAllGroup && $data['group'] === 'ALL' && $slotRoutines->isNotEmpty()) {
        return [
            'group' => 'Some groups of this section already have a class at this time. '
                     . 'You cannot schedule a combined (ALL) class in parallel.'
        ];
    }

    // -------------------------------------------------------
    // 1) Theory/Practical vs group
    // -------------------------------------------------------
    if ($data['type'] === 'TH' && $data['group'] !== 'ALL') {
        return ['group' => 'Theory classes must be scheduled for group ALL (combined section).'];
    }
    if ($data['type'] === 'PR' && $data['group'] === 'ALL') {
        return ['group' => 'Practical classes must be scheduled for group A or B, not ALL.'];
    }

    // -------------------------------------------------------
    // 2) Practical only allowed if subject has practical
    // -------------------------------------------------------
    $subject = Subject::find($data['subject_id']);
    if (!$subject) {
        return ['subject_id' => 'Subject not found.'];
    }
    if ($data['type'] === 'PR' && !$subject->has_practical) {
        return ['subject_id' => 'Selected subject does not have practical.'];
    }

    // -------------------------------------------------------
    // 3) Teacher conflict: same teacher, same day+period
    //    (works per teacher_id for now)
    // -------------------------------------------------------
    $conflictQuery = Routine::where('teacher_id', $data['teacher_id'])
        ->where('day_of_week', $data['day_of_week'])
        ->where('period_id',   $data['period_id']);

    if ($ignoreRoutineId) {
        $conflictQuery->where('id', '!=', $ignoreRoutineId);
    }

    if ($conflictQuery->exists()) {
        return [
            'teacher_id' => 'This teacher already has a class at this day and period.'
        ];
    }

    return null;
}

}

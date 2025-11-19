<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\{
    Exam,
    Room,
    Faculty,
    Employee,
    RoomAllocation,
    ExamRegistrationSubject
};

class SeatPlanController extends Controller
{
    /**
     * Show seat-planning UI and generate plan in HTML.
     */
    public function index(Request $r)
    {
        $examId      = (int) $r->query('exam_id', 0);
        $examDate    = trim((string) $r->query('exam_date', ''));
        $batchParam  = $r->query('batch');
        $employeeIds = array_filter((array) $r->query('employee_ids', []));

        // All upcoming exams
        $exams = Exam::where('status', 0)
            ->orderByDesc('id')
            ->get(['id', 'exam_title', 'semester', 'batch']);

        // Active employees
        $employees = Employee::where('is_active', true)
            ->orderBy('employee_type', 'desc') // faculty first
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_type']);

        // Available dates for selected exam (from allocations)
        $allocatedDates = [];
        if ($examId) {
            $allocatedDates = RoomAllocation::where('exam_id', $examId)
                ->distinct()
                ->pluck('exam_date')
                ->sort()
                ->values()
                ->toArray();
        }

        // Saved invigilators for this exam + date
        $hasSavedInvigilators   = false;
        $savedAssignmentsByRoom = collect();

        if ($examId && $examDate !== '') {
            $savedAssignments = RoomAllocation::where('exam_id', $examId)
                ->where('exam_date', $examDate)
                ->whereNotNull('invigilator_assignments')
                ->get();

            if ($savedAssignments->isNotEmpty()) {
                $hasSavedInvigilators   = true;
                $savedAssignmentsByRoom = $savedAssignments->groupBy('room_id');

                $savedInvigilatorIds = $savedAssignments->flatMap(function ($a) {
                    return is_array($a->invigilator_assignments)
                        ? $a->invigilator_assignments
                        : [];
                })->filter()->unique()->values()->all();

                // Prefill filters if not provided explicitly
                if (!empty($savedInvigilatorIds) && empty($employeeIds)) {
                    $employeeIds = $savedInvigilatorIds;
                }
            }
        }

        $exam       = null;
        $batch      = null;
        $rooms      = collect();
        $seatLayout = [];
        $paperInfo  = [];
        $hasData    = false;

        if ($examId && $examDate !== '') {
            $exam = $exams->firstWhere('id', $examId);

            if ($exam) {
                $examBatchNum = $exam->batch === 'new' ? 1 : 2;
                $batch        = $batchParam ? (int) $batchParam : $examBatchNum;

                // Allocations for this exam + date
                $allocations = RoomAllocation::where('exam_id', $exam->id)
                    ->where('exam_date', $examDate)
                    ->orderBy('room_id')
                    ->get();

                if ($allocations->isNotEmpty()) {
                    $hasData = true;

                    // Rooms used that day
                    $rooms = Room::whereIn('id', $allocations->pluck('room_id')->unique())
                        ->orderBy('room_no')
                        ->get()
                        ->keyBy('id');

                    // Paper keys faculty_id|subject_code
                    $paperKeys = $allocations->map(fn($a) => $a->faculty_id . '|' . $a->subject_code)
                        ->unique()
                        ->values();

                    // Students for each paper
                    $paperStudents = [];
                    $paperOffsets  = [];

                    foreach ($paperKeys as $pKey) {
                        [$fid, $code] = explode('|', $pKey);

                        $subjects = ExamRegistrationSubject::query()
                            ->where('subject_code', $code)
                            ->whereHas('registration', function ($q) use ($exam, $batch, $fid) {
                                $q->where('exam_id', $exam->id)
                                    ->where('batch', $batch)
                                    ->where('faculty_id', (int) $fid);
                            })
                            ->where(function ($q) {
                                $q->where('th_taking', 1)
                                    ->orWhere('p_taking', 1);
                            })
                            ->with(['registration.student', 'fss.subject'])
                            ->get();

                        $sorted = $subjects->sortBy(fn($s) => (int) ($s->registration->exam_roll_no ?? 0))
                            ->values();

                        $paperStudents[$pKey] = $sorted;
                        $paperOffsets[$pKey]  = 0;

                        $subjectName = $sorted->first()?->fss?->subject?->name ?? $code;

                        $paperInfo[$pKey] = [
                            'faculty_id'   => (int) $fid,
                            'subject_code' => $code,
                            'subject_name' => $subjectName,
                        ];
                    }

                    // Decide if we should assign new invigilators now:
                    // only when there are NO saved assignments yet AND some employees are selected in the UI
                    $assignRandomInvigilators = !$hasSavedInvigilators && !empty($employeeIds);

                    $staffPool   = collect();
                    $facultyPool = collect();

                    if ($assignRandomInvigilators) {
                        $selectedEmployees = $employees->whereIn('id', $employeeIds)->values();

                        $basePool = $selectedEmployees->isNotEmpty()
                            ? $selectedEmployees->shuffle()->values()
                            : $employees->shuffle()->values();

                        $staffPool   = $basePool->where('employee_type', 'staff')->values();
                        $facultyPool = $basePool->where('employee_type', 'faculty')->values();
                    }

                    // Build layout per room
                    foreach ($rooms as $roomId => $room) {
                        $totalBenches = $room->computed_total_benches;
                        $totalSeats   = $totalBenches * 2;

                        $allocForRoom = $allocations->where('room_id', $roomId);

                        // Queues of students per paper for this room
                        $roomQueues = [];
                        foreach ($allocForRoom as $a) {
                            $pKey   = $a->faculty_id . '|' . $a->subject_code;
                            $needed = (int) $a->student_count;

                            if ($needed <= 0) {
                                continue;
                            }

                            $globalList = $paperStudents[$pKey] ?? collect();
                            $offset     = $paperOffsets[$pKey] ?? 0;

                            $slice = $globalList->slice($offset, $needed);
                            $paperOffsets[$pKey] = $offset + $slice->count();

                            if ($slice->isEmpty()) {
                                continue;
                            }

                            foreach ($slice as $s) {
                                $roomQueues[$pKey][] = [
                                    'symbol_no'    => $s->registration->exam_roll_no ?? null,
                                    'subject_key'  => $pKey,
                                    'subject_code' => $a->subject_code,
                                    'faculty_id'   => $a->faculty_id,
                                ];
                            }
                        }

                        if (empty($roomQueues)) {
                            $seatLayout[$roomId] = [
                                'room'         => $room,
                                'invigilators' => [],
                                'cols'         => [1 => [], 2 => [], 3 => []],
                            ];
                            continue;
                        }

                        // ---------- SEAT PLANNING: SAME FACULTY FIRST, THEN NEXT ----------
                        // benches: linear 0..(totalBenches-1), later mapped to C1/C2/C3
                        $benches = array_fill(0, $totalBenches, ['left' => null, 'right' => null]);

                        $remaining = function () use (&$roomQueues) {
                            $res = [];
                            foreach ($roomQueues as $key => $queue) {
                                $res[$key] = count($queue);
                            }
                            return $res;
                        };

                        // Order of papers in this room (faculty-wise)
                        $paperOrder = $allocForRoom
                            ->map(fn($a) => $a->faculty_id . '|' . $a->subject_code)
                            ->unique()
                            ->values()
                            ->all();

                        /**
                         * PASS 1: LEFT seats
                         * - For each paper in order, place 1 student per bench
                         *   continuously (same faculty/subject grouped).
                         * - Bench order: C1R1, C1R2, ..., then C2..., then C3...
                         */
                        $benchIndex = 0;

                        foreach ($paperOrder as $pKey) {
                            while (
                                $benchIndex < $totalBenches &&
                                !empty($roomQueues[$pKey])
                            ) {
                                $student = array_shift($roomQueues[$pKey]);
                                $benches[$benchIndex]['left'] = $student;
                                $benchIndex++;
                            }
                        }

                        /**
                         * PASS 2: RIGHT seats
                         * - Again go faculty/subject-wise using same $paperOrder.
                         * - For each student, first try a bench whose LEFT subject != this subject.
                         * - If not possible, use any empty right seat (so duplicate subject
                         *   ends up in "column 2" / right seat).
                         */
                        foreach ($paperOrder as $pKey) {
                            while (!empty($roomQueues[$pKey])) {
                                $rem = $remaining();
                                if (array_sum($rem) === 0) {
                                    break;
                                }

                                $targetIndex = null;

                                // 1) Prefer bench where right is empty AND left != this subject
                                for ($i = 0; $i < $totalBenches; $i++) {
                                    if ($benches[$i]['right'] !== null) {
                                        continue;
                                    }
                                    $leftKey = $benches[$i]['left']['subject_key'] ?? null;
                                    if ($leftKey !== $pKey) {
                                        $targetIndex = $i;
                                        break;
                                    }
                                }

                                // 2) If not found, take ANY bench with empty right
                                if ($targetIndex === null) {
                                    for ($i = 0; $i < $totalBenches; $i++) {
                                        if ($benches[$i]['right'] === null) {
                                            $targetIndex = $i;
                                            break;
                                        }
                                    }
                                }

                                // No more free right seats
                                if ($targetIndex === null) {
                                    break;
                                }

                                $student = array_shift($roomQueues[$pKey]);
                                $benches[$targetIndex]['right'] = $student;
                            }
                        }
                        // ---------- END SEAT PLANNING ----------

                        // Map benches to 3 columns: C1 rows, then C2, then C3
                        $cols = [1 => [], 2 => [], 3 => []];
                        $idx  = 0;

                        for ($row = 1; $row <= $room->rows_col1; $row++) {
                            if (!isset($benches[$idx])) {
                                break;
                            }
                            $cols[1][$row] = $benches[$idx];
                            $idx++;
                        }

                        for ($row = 1; $row <= $room->rows_col2; $row++) {
                            if (!isset($benches[$idx])) {
                                break;
                            }
                            $cols[2][$row] = $benches[$idx];
                            $idx++;
                        }

                        for ($row = 1; $row <= $room->rows_col3; $row++) {
                            if (!isset($benches[$idx])) {
                                break;
                            }
                            $cols[3][$row] = $benches[$idx];
                            $idx++;
                        }

                        // Assign / load invigilators for this room
                        $invigilators = [];

                        // 1) If saved in DB -> use those (do NOT reassign)
                        if ($hasSavedInvigilators && isset($savedAssignmentsByRoom[$roomId])) {
                            $ids = $savedAssignmentsByRoom[$roomId]
                                ->flatMap(function ($alloc) {
                                    return is_array($alloc->invigilator_assignments)
                                        ? $alloc->invigilator_assignments
                                        : [];
                                })
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();

                            if (!empty($ids)) {
                                $invigilators = $employees->whereIn('id', $ids)->values();
                            }

                        // 2) Else, if we decided to assign new invigilators now
                        } elseif ($assignRandomInvigilators) {
                            $neededInv = $room->faculties_per_room ?: 2;

                            if ($neededInv > 0 && $facultyPool->isNotEmpty()) {
                                $invigilators[] = $facultyPool->shift();
                                $neededInv--;
                            }

                            if ($neededInv > 0 && $staffPool->isNotEmpty()) {
                                $invigilators[] = $staffPool->shift();
                                $neededInv--;
                            }

                            while ($neededInv > 0) {
                                if ($facultyPool->isNotEmpty()) {
                                    $invigilators[] = $facultyPool->shift();
                                    $neededInv--;
                                } elseif ($staffPool->isNotEmpty()) {
                                    $invigilators[] = $staffPool->shift();
                                    $neededInv--;
                                } else {
                                    break;
                                }
                            }
                        }

                        $seatLayout[$roomId] = [
                            'room'         => $room,
                            'invigilators' => $invigilators,
                            'cols'         => $cols,
                        ];
                    }

                    // If we just created new assignments from selected employees, persist them
                    if ($assignRandomInvigilators) {
                        $this->saveInvigilatorAssignments($exam->id, $examDate, $seatLayout);
                        $hasSavedInvigilators = true;
                    }
                }
            }
        }

        return view('Backend.admin.seat_plans.index', compact(
            'exams',
            'exam',
            'examId',
            'examDate',
            'allocatedDates',
            'batch',
            'employees',
            'employeeIds',
            'rooms',
            'seatLayout',
            'paperInfo',
            'hasData',
            'hasSavedInvigilators'
        ));
    }

    /**
     * Download seat plan (landscape) PDF.
     */
    public function downloadSeatPlan(Request $r)
    {
        $examId      = (int) $r->input('exam_id');
        $examDate    = $r->input('exam_date');
        $batch       = (int) $r->input('batch');
        $employeeIds = json_decode($r->input('employee_ids_json', '[]'), true);

        $data = $this->generateSeatLayout($examId, $examDate, $batch, $employeeIds, true);

        if (!$data['hasData']) {
            return back()->with('error', 'No data available for seat plan');
        }

        // Save invigilators
        $this->saveInvigilatorAssignments($examId, $examDate, $data['seatLayout']);

        // Sanitize date for filename
        $safeDate = str_replace(['/', '\\'], '-', $examDate);

        $pdf = Pdf::loadView('Backend.admin.seat_plans.pdf_seat_plan', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("seat-plan-{$safeDate}.pdf");
    }

    /**
     * Print seat plan (landscape) PDF - opens in browser.
     */
    public function printSeatPlan(Request $r)
    {
        $examId      = (int) $r->input('exam_id');
        $examDate    = $r->input('exam_date');
        $batch       = (int) $r->input('batch');
        $employeeIds = json_decode($r->input('employee_ids_json', '[]'), true);

        $data = $this->generateSeatLayout($examId, $examDate, $batch, $employeeIds, true);

        if (!$data['hasData']) {
            return back()->with('error', 'No data available for seat plan');
        }

        // Save invigilators
        $this->saveInvigilatorAssignments($examId, $examDate, $data['seatLayout']);

        // Sanitize date for filename
        $safeDate = str_replace(['/', '\\'], '-', $examDate);

        $pdf = Pdf::loadView('Backend.admin.seat_plans.pdf_seat_plan', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->stream("seat-plan-{$safeDate}.pdf");
    }

    /**
     * Download attendance sheets (portrait) PDF.
     */
    public function downloadAttendanceSheets(Request $r)
    {
        $examId      = (int) $r->input('exam_id');
        $examDate    = $r->input('exam_date');
        $batch       = (int) $r->input('batch');
        $employeeIds = json_decode($r->input('employee_ids_json', '[]'), true);

        $data = $this->generateSeatLayout($examId, $examDate, $batch, $employeeIds, true);

        if (!$data['hasData']) {
            return back()->with('error', 'No data available for attendance sheets');
        }

        $this->saveInvigilatorAssignments($examId, $examDate, $data['seatLayout']);

        $attendanceSheets = $this->buildAttendanceSheets($data);

        $safeDate = str_replace(['/', '\\'], '-', $examDate);

        $pdf = Pdf::loadView('Backend.admin.seat_plans.pdf_attendance', [
            'sheets'   => $attendanceSheets,
            'exam'     => $data['exam'],
            'examDate' => $examDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("attendance-{$safeDate}.pdf");
    }

    /**
     * Print attendance sheets (stream – opens in browser).
     */
    public function printAttendanceSheets(Request $r)
    {
        $examId      = (int) $r->input('exam_id');
        $examDate    = $r->input('exam_date');
        $batch       = (int) $r->input('batch');
        $employeeIds = json_decode($r->input('employee_ids_json', '[]'), true);

        $data = $this->generateSeatLayout($examId, $examDate, $batch, $employeeIds, true);

        if (!$data['hasData']) {
            return back()->with('error', 'No data available for attendance sheets');
        }

        $this->saveInvigilatorAssignments($examId, $examDate, $data['seatLayout']);

        $attendanceSheets = $this->buildAttendanceSheets($data);

        $safeDate = str_replace(['/', '\\'], '-', $examDate);

        $pdf = Pdf::loadView('Backend.admin.seat_plans.pdf_attendance', [
            'sheets'   => $attendanceSheets,
            'exam'     => $data['exam'],
            'examDate' => $examDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("attendance-{$safeDate}.pdf");
    }

    /**
     * Save invigilator assignments in room_allocations table.
     */
    private function saveInvigilatorAssignments(int $examId, string $examDate, array $seatLayout): void
    {
        foreach ($seatLayout as $roomId => $data) {
            $invigilatorIds = collect($data['invigilators'] ?? [])->pluck('id')->toArray();

            RoomAllocation::where('exam_id', $examId)
                ->where('exam_date', $examDate)
                ->where('room_id', $roomId)
                ->update(['invigilator_assignments' => $invigilatorIds]);
        }
    }

    /**
     * Build attendance data grouped by room + faculty.
     */
    private function buildAttendanceSheets($data)
    {
        $sheets   = [];
        $exam     = $data['exam'];
        $examDate = $data['examDate'];

        foreach ($data['seatLayout'] as $roomId => $layoutData) {
            $room         = $layoutData['room'];
            $cols         = $layoutData['cols'];
            $invigilators = $layoutData['invigilators'];

            // Group students by faculty + subject
            $facultyGroups = [];

            foreach ($cols as $colNum => $rows) {
                foreach ($rows as $rowNum => $bench) {
                    foreach (['left', 'right'] as $side) {
                        $student = $bench[$side] ?? null;
                        if (!$student) continue;

                        $facultyId   = $student['faculty_id'];
                        $subjectCode = $student['subject_code'];
                        $key         = "{$facultyId}|{$subjectCode}";

                        if (!isset($facultyGroups[$key])) {
                            $facultyGroups[$key] = [
                                'faculty_id'   => $facultyId,
                                'subject_code' => $subjectCode,
                                'subject_name' => $data['paperInfo'][$key]['subject_name'] ?? $subjectCode,
                                'students'     => [],
                            ];
                        }

                        $facultyGroups[$key]['students'][] = [
                            'symbol_no'      => $student['symbol_no'],
                            'col'            => $colNum,
                            'row'            => $rowNum,
                            'side'           => $side,
                            'bench_position' => "C{$colNum}-R{$rowNum}-" . strtoupper(substr($side, 0, 1)),
                        ];
                    }
                }
            }

            // For each faculty+subject group, build one attendance sheet
            foreach ($facultyGroups as $key => $group) {
                $symbolNos = collect($group['students'])
                    ->pluck('symbol_no')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $studentsMap = ExamRegistrationSubject::query()
                    ->where('subject_code', $group['subject_code'])
                    ->whereHas('registration', function ($q) use ($exam, $data, $group, $symbolNos) {
                        $q->where('exam_id', $exam->id)
                          ->where('batch', $data['batch'])
                          ->where('faculty_id', $group['faculty_id'])
                          ->whereIn('exam_roll_no', $symbolNos);
                    })
                    ->with(['registration.student'])
                    ->get()
                    ->keyBy(fn($s) => $s->registration->exam_roll_no);

                $enrichedStudents = [];

                foreach ($group['students'] as $idx => $st) {
                    $regSub  = $studentsMap[$st['symbol_no']] ?? null;

                    if (!$regSub || !$regSub->registration || !$regSub->registration->student) {
                        $enrichedStudents[] = [
                            'sn'             => $idx + 1,
                            'roll_no'        => 'N/A',
                            'symbol_no'      => $st['symbol_no'],
                            'student_name'   => 'Unknown',
                            'bench_position' => $st['bench_position'],
                        ];
                        continue;
                    }

                    $student = $regSub->registration->student;
                    $rollNo  = $student->campus_roll_no ?? $regSub->registration->exam_roll_no ?? 'N/A';

                    $enrichedStudents[] = [
                        'sn'             => $idx + 1,
                        'roll_no'        => $rollNo,
                        'symbol_no'      => $st['symbol_no'],
                        'student_name'   => $student->name ?? 'Unknown',
                        'bench_position' => $st['bench_position'],
                    ];
                }

                usort($enrichedStudents, fn($a, $b) => ($a['symbol_no'] ?? 0) <=> ($b['symbol_no'] ?? 0));
                foreach ($enrichedStudents as $idx => &$st) {
                    $st['sn'] = $idx + 1;
                }
                unset($st);

                $faculty = Faculty::find($group['faculty_id']);

                $sheets[] = [
                    'room'           => $room,
                    'faculty_name'   => $faculty?->name ?? 'N/A',
                    'subject_code'   => $group['subject_code'],
                    'subject_name'   => $group['subject_name'],
                    'students'       => $enrichedStudents,
                    'total_students' => count($enrichedStudents),
                    'invigilators'   => $invigilators,
                ];
            }
        }

        return $sheets;
    }

    /**
     * Shared generator for PDFs and printing.
     */
    private function generateSeatLayout(
        int $examId,
        string $examDate,
        int $batch,
        array $employeeIds,
        bool $loadFromSaved = false
    ): array {
        $exam      = Exam::findOrFail($examId);
        $employees = Employee::where('is_active', true)->get();

        if ($loadFromSaved) {
            $savedAssignments = RoomAllocation::where('exam_id', $examId)
                ->where('exam_date', $examDate)
                ->whereNotNull('invigilator_assignments')
                ->get()
                ->keyBy('room_id');

            $allSavedIds = [];
            foreach ($savedAssignments as $sa) {
                $ids = $sa->invigilator_assignments ?? [];
                if (is_array($ids)) {
                    $allSavedIds = array_merge($allSavedIds, $ids);
                }
            }

            if (!empty($allSavedIds)) {
                $employeeIds = array_unique($allSavedIds);
            }
        }

        $allocations = RoomAllocation::where('exam_id', $exam->id)
            ->where('exam_date', $examDate)
            ->orderBy('room_id')
            ->get();

        if ($allocations->isEmpty()) {
            return ['hasData' => false];
        }

        $rooms = Room::whereIn('id', $allocations->pluck('room_id')->unique())
            ->orderBy('room_no')
            ->get()
            ->keyBy('id');

        $paperKeys = $allocations->map(fn($a) => $a->faculty_id . '|' . $a->subject_code)
            ->unique()
            ->values();

        $paperStudents = [];
        $paperOffsets  = [];
        $paperInfo     = [];

        foreach ($paperKeys as $pKey) {
            [$fid, $code] = explode('|', $pKey);

            $subjects = ExamRegistrationSubject::query()
                ->where('subject_code', $code)
                ->whereHas('registration', function ($q) use ($exam, $batch, $fid) {
                    $q->where('exam_id', $exam->id)
                        ->where('batch', $batch)
                        ->where('faculty_id', (int) $fid);
                })
                ->where(function ($q) {
                    $q->where('th_taking', 1)
                        ->orWhere('p_taking', 1);
                })
                ->with(['registration.student', 'fss.subject'])
                ->get()
                ->sortBy(fn($s) => (int) ($s->registration->exam_roll_no ?? 0))
                ->values();

            $paperStudents[$pKey] = $subjects;
            $paperOffsets[$pKey]  = 0;

            $subjectName = $subjects->first()?->fss?->subject?->name ?? $code;
            $paperInfo[$pKey] = [
                'faculty_id'   => (int) $fid,
                'subject_code' => $code,
                'subject_name' => $subjectName,
            ];
        }

        $selectedEmployees = $employees->whereIn('id', $employeeIds)->values();
        $basePool          = $selectedEmployees->isNotEmpty()
            ? $selectedEmployees->shuffle()->values()
            : $employees->shuffle()->values();

        $staffPool   = $basePool->where('employee_type', 'staff')->values();
        $facultyPool = $basePool->where('employee_type', 'faculty')->values();

        $seatLayout = [];

        foreach ($rooms as $roomId => $room) {
            $totalBenches = $room->computed_total_benches;
            $totalSeats   = $totalBenches * 2;
            $allocForRoom = $allocations->where('room_id', $roomId);

            $roomQueues = [];
            foreach ($allocForRoom as $a) {
                $pKey   = $a->faculty_id . '|' . $a->subject_code;
                $needed = (int) $a->student_count;
                if ($needed <= 0) {
                    continue;
                }

                $globalList = $paperStudents[$pKey] ?? collect();
                $offset     = $paperOffsets[$pKey] ?? 0;
                $slice      = $globalList->slice($offset, $needed);
                $paperOffsets[$pKey] = $offset + $slice->count();

                if ($slice->isEmpty()) {
                    continue;
                }

                foreach ($slice as $s) {
                    $roomQueues[$pKey][] = [
                        'symbol_no'    => $s->registration->exam_roll_no ?? null,
                        'subject_key'  => $pKey,
                        'subject_code' => $a->subject_code,
                        'faculty_id'   => $a->faculty_id,
                    ];
                }
            }

            if (empty($roomQueues)) {
                $seatLayout[$roomId] = [
                    'room'         => $room,
                    'invigilators' => [],
                    'cols'         => [1 => [], 2 => [], 3 => []],
                ];
                continue;
            }

            // ---------- SEAT PLANNING: SAME FACULTY FIRST, THEN NEXT ----------
            $benches = array_fill(0, $totalBenches, ['left' => null, 'right' => null]);

            $remaining = function () use (&$roomQueues) {
                $res = [];
                foreach ($roomQueues as $key => $queue) {
                    $res[$key] = count($queue);
                }
                return $res;
            };

            // Order of papers in this room (faculty-wise)
            $paperOrder = $allocForRoom
                ->map(fn($a) => $a->faculty_id . '|' . $a->subject_code)
                ->unique()
                ->values()
                ->all();

            // PASS 1: LEFT seats (grouped by faculty/subject)
            $benchIndex = 0;
            foreach ($paperOrder as $pKey) {
                while (
                    $benchIndex < $totalBenches &&
                    !empty($roomQueues[$pKey])
                ) {
                    $student = array_shift($roomQueues[$pKey]);
                    $benches[$benchIndex]['left'] = $student;
                    $benchIndex++;
                }
            }

            // PASS 2: RIGHT seats (avoid same subject beside each other if possible)
            foreach ($paperOrder as $pKey) {
                while (!empty($roomQueues[$pKey])) {
                    $rem = $remaining();
                    if (array_sum($rem) === 0) {
                        break;
                    }

                    $targetIndex = null;

                    // Prefer bench where right is empty AND left != this subject
                    for ($i = 0; $i < $totalBenches; $i++) {
                        if ($benches[$i]['right'] !== null) {
                            continue;
                        }
                        $leftKey = $benches[$i]['left']['subject_key'] ?? null;
                        if ($leftKey !== $pKey) {
                            $targetIndex = $i;
                            break;
                        }
                    }

                    // If not available, use any free right seat
                    if ($targetIndex === null) {
                        for ($i = 0; $i < $totalBenches; $i++) {
                            if ($benches[$i]['right'] === null) {
                                $targetIndex = $i;
                                break;
                            }
                        }
                    }

                    if ($targetIndex === null) {
                        break;
                    }

                    $student = array_shift($roomQueues[$pKey]);
                    $benches[$targetIndex]['right'] = $student;
                }
            }
            // ---------- END SEAT PLANNING ----------

            $cols = [1 => [], 2 => [], 3 => []];
            $idx  = 0;

            for ($row = 1; $row <= $room->rows_col1; $row++) {
                if (!isset($benches[$idx])) {
                    break;
                }
                $cols[1][$row] = $benches[$idx];
                $idx++;
            }

            for ($row = 1; $row <= $room->rows_col2; $row++) {
                if (!isset($benches[$idx])) {
                    break;
                }
                $cols[2][$row] = $benches[$idx];
                $idx++;
            }

            for ($row = 1; $row <= $room->rows_col3; $row++) {
                if (!isset($benches[$idx])) {
                    break;
                }
                $cols[3][$row] = $benches[$idx];
                $idx++;
            }

            $neededInv    = $room->faculties_per_room ?: 2;
            $invigilators = [];

            if ($neededInv > 0 && $facultyPool->isNotEmpty()) {
                $invigilators[] = $facultyPool->shift();
                $neededInv--;
            }

            if ($neededInv > 0 && $staffPool->isNotEmpty()) {
                $invigilators[] = $staffPool->shift();
                $neededInv--;
            }

            while ($neededInv > 0) {
                if ($facultyPool->isNotEmpty()) {
                    $invigilators[] = $facultyPool->shift();
                    $neededInv--;
                } elseif ($staffPool->isNotEmpty()) {
                    $invigilators[] = $staffPool->shift();
                    $neededInv--;
                } else {
                    break;
                }
            }

            $seatLayout[$roomId] = [
                'room'         => $room,
                'invigilators' => $invigilators,
                'cols'         => $cols,
            ];
        }

        return [
            'hasData'    => true,
            'exam'       => $exam,
            'examDate'   => $examDate,
            'batch'      => $batch,
            'rooms'      => $rooms,
            'seatLayout' => $seatLayout,
            'paperInfo'  => $paperInfo,
        ];
    }
}

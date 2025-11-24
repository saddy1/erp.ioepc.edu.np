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
    Routine,
    ExamRegistrationSubject,
    RoutineSlot
};

class SeatPlanController extends Controller
{
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

        // Saved invigilators for this exam + date (to prefill selection)
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

                // 🔁 Use the same generator as PDFs so logic is identical
                $layoutData = $this->generateSeatLayout($examId, $examDate, $batch, $employeeIds, true);

                if ($layoutData['hasData']) {
                    $hasData    = true;
                    $exam       = $layoutData['exam'];
                    $rooms      = $layoutData['rooms'];
                    $seatLayout = $layoutData['seatLayout'];
                    $paperInfo  = $layoutData['paperInfo'];

                    // If user selected invigilators here, persist mapping
                    if (!empty($employeeIds)) {
                        $this->saveInvigilatorAssignments($examId, $examDate, $seatLayout);
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

        // Save invigilators (deterministic)
        $this->saveInvigilatorAssignments($examId, $examDate, $data['seatLayout']);

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

        $this->saveInvigilatorAssignments($examId, $examDate, $data['seatLayout']);

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

    private function toRoman($num)
    {
        $map = [
            'M'  => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];
        $return = '';

        while ($num > 0) {
            foreach ($map as $roman => $int) {
                if ($num >= $int) {
                    $num -= $int;
                    $return .= $roman;
                    break;
                }
            }
        }
        return $return;
    }

    private function semToRomanYearPart($sem)
    {
        if (!$sem) return 'N/A';

        $sem = (int) $sem;
        if ($sem === 0) return 'N/A';

        $year = (int) ceil($sem / 2);
        $part = $sem % 2 == 1 ? 1 : 2;

        return $this->toRoman($year) . '/' . $this->toRoman($part);
    }

    public function printAttendanceSheets(Request $r)
    {
        $examId      = (int) $r->input('exam_id');
        $examDate    = $r->input('exam_date');
        $batch       = (int) $r->input('batch');
        $employeeIds = json_decode($r->input('employee_ids_json', '[]'), true);

        $semester = ExamRegistrationSubject::query()
            ->whereHas('registration', function ($q) use ($examId, $batch) {
                $q->where('exam_id', $examId)
                    ->where('batch', $batch)
                    ->whereNotNull('semester');
            })
            ->with('registration')
            ->first()?->registration?->semester;

        $semesterRoman = $semester ? $this->semToRomanYearPart($semester) : 'N/A';

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
            'semester' => $semesterRoman,
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
                    'faculty_code'   => $faculty?->code ?? 'N/A',
                    'subject_code'   => $group['subject_code'],
                    'subject_name'   => $group['subject_name'],
                    'students'       => $enrichedStudents,
                    'total_students' => count($enrichedStudents),
                    'invigilators'   => $invigilators,
                ];
            }
        }

        // PAGE NUMBERING PER FACULTY (e.g., BCE 1/2, BAR 1/3)
        $facultyTotals = [];
        foreach ($sheets as $sheet) {
            $code = $sheet['faculty_code'] ?? 'N/A';
            if (!isset($facultyTotals[$code])) {
                $facultyTotals[$code] = 0;
            }
            $facultyTotals[$code]++;
        }

        $facultySeen = [];
        foreach ($sheets as &$sheet) {
            $code = $sheet['faculty_code'] ?? 'N/A';

            if (!isset($facultySeen[$code])) {
                $facultySeen[$code] = 0;
            }
            $facultySeen[$code]++;

            $sheet['page_no']    = $facultySeen[$code];
            $sheet['page_total'] = $facultyTotals[$code];
        }
        unset($sheet);

        return $sheets;
    }

    /**
     * Core seat layout generator.
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

        // Load saved invigilator assignments (per room) if needed
        $savedAssignmentsByRoom = collect();
        if ($loadFromSaved) {
            $savedAssignmentsByRoom = RoomAllocation::where('exam_id', $examId)
                ->where('exam_date', $examDate)
                ->whereNotNull('invigilator_assignments')
                ->get()
                ->groupBy('room_id');
        }

        // Allocations for this exam + date
        $allocations = RoomAllocation::where('exam_id', $exam->id)
            ->where('exam_date', $examDate)
            ->orderBy('room_id')
            ->orderBy('faculty_id')
            ->orderBy('subject_code')
            ->orderBy('id')
            ->get();

        if ($allocations->isEmpty()) {
            return ['hasData' => false];
        }

        $rooms = Room::whereIn('id', $allocations->pluck('room_id')->unique())
            ->orderBy('room_no')
            ->get()
            ->keyBy('id');

        // Build global student lists per paper (faculty_id|subject_code)
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

        // Only build pools if user selected invigilators
        $assignFromSelection = !empty($employeeIds);

        $staffPool   = collect();
        $facultyPool = collect();

        if ($assignFromSelection) {
            $selectedEmployees = $employees->whereIn('id', $employeeIds)->values();

            $basePool = $selectedEmployees->isNotEmpty()
                ? $selectedEmployees->sortBy([
                    ['employee_type', 'desc'],
                    ['full_name', 'asc'],
                ])->values()
                : collect();

            $staffPool   = $basePool->where('employee_type', 'staff')->values();
            $facultyPool = $basePool->where('employee_type', 'faculty')->values();
        }

        $seatLayout = [];

        foreach ($rooms as $roomId => $room) {
            $totalBenches = $room->computed_total_benches;
            $allocForRoom = $allocations->where('room_id', $roomId);

            // --- Build subject queues for this room (ignore faculty for seating order) ---
            $subjectQueues = [];
            $subjectCounts = [];

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
                    $subjectCode = $a->subject_code;

                    if (!isset($subjectQueues[$subjectCode])) {
                        $subjectQueues[$subjectCode] = [];
                        $subjectCounts[$subjectCode] = 0;
                    }

                    $subjectQueues[$subjectCode][] = [
                        'symbol_no'    => $s->registration->exam_roll_no ?? null,
                        'subject_key'  => $pKey,
                        'subject_code' => $subjectCode,
                        'faculty_id'   => $a->faculty_id,
                    ];
                    $subjectCounts[$subjectCode]++;
                }
            }

            if (empty($subjectQueues)) {
                $seatLayout[$roomId] = [
                    'room'         => $room,
                    'invigilators' => [],
                    'cols'         => [1 => [], 2 => [], 3 => []],
                ];
                continue;
            }

            // ---------- SEAT PLANNING ----------
            $benches = array_fill(0, $totalBenches, ['left' => null, 'right' => null]);

            // Subject order: max students first, then subject_code ASC
            $subjectOrder = array_keys($subjectCounts);
            usort($subjectOrder, function ($a, $b) use ($subjectCounts) {
                if ($subjectCounts[$a] !== $subjectCounts[$b]) {
                    return $subjectCounts[$b] <=> $subjectCounts[$a]; // DESC
                }
                return strcmp($a, $b);
            });

            $rowsCol1 = (int) $room->rows_col1;
            $rowsCol2 = (int) $room->rows_col2;
            $rowsCol3 = (int) $room->rows_col3;

            // PASS 1: LEFT seats – simple linear benches 0..N-1 by subjectOrder
            $benchIndex = 0;
            foreach ($subjectOrder as $code) {
                while (
                    $benchIndex < $totalBenches &&
                    !empty($subjectQueues[$code])
                ) {
                    $student = array_shift($subjectQueues[$code]);
                    $benches[$benchIndex]['left'] = $student;
                    $benchIndex++;
                }
            }

            // Precompute bench order: Column 2 → Column 1 → Column 3
            $benchOrder = [];

            // C2 indices
            $startC2 = $rowsCol1;
            $endC2   = $rowsCol1 + $rowsCol2 - 1;
            for ($i = $startC2; $i <= $endC2 && $i < $totalBenches; $i++) {
                $benchOrder[] = $i;
            }

            // C1 indices
            for ($i = 0; $i < $rowsCol1 && $i < $totalBenches; $i++) {
                $benchOrder[] = $i;
            }

            // C3 indices
            $startC3 = $rowsCol1 + $rowsCol2;
            $endC3   = $totalBenches - 1;
            for ($i = $startC3; $i <= $endC3 && $i < $totalBenches; $i++) {
                $benchOrder[] = $i;
            }

            // PASS 2: RIGHT seats – avoid same subject; if forced, only in Column 2
            foreach ($subjectOrder as $code) {
                while (!empty($subjectQueues[$code])) {
                    $targetIndex = null;

                    // 1) Prefer benches where RIGHT empty AND LEFT.subject_code != this subject
                    foreach ($benchOrder as $i) {
                        if (!isset($benches[$i]) || $benches[$i]['right'] !== null) {
                            continue;
                        }

                        $leftCode = $benches[$i]['left']['subject_code'] ?? null;
                        if ($leftCode !== $code) {
                            $targetIndex = $i;
                            break;
                        }
                    }

                    // 2) If we must place same-subject pair → only in Column 2
                    if ($targetIndex === null) {
                        foreach ($benchOrder as $i) {
                            if (!isset($benches[$i]) || $benches[$i]['right'] !== null) {
                                continue;
                            }

                            // Column 2 indices range
                            if ($i >= $rowsCol1 && $i < $rowsCol1 + $rowsCol2) {
                                $targetIndex = $i;
                                break;
                            }
                        }
                    }

                    // 3) Final fallback: any free right seat (if even C2 is full)
                    if ($targetIndex === null) {
                        foreach ($benchOrder as $i) {
                            if (!isset($benches[$i]) || $benches[$i]['right'] !== null) {
                                continue;
                            }
                            $targetIndex = $i;
                            break;
                        }
                    }

                    if ($targetIndex === null) {
                        // Room completely full on right side
                        break 2;
                    }

                    $student = array_shift($subjectQueues[$code]);
                    $benches[$targetIndex]['right'] = $student;
                }
            }
            // ---------- END SEAT PLANNING ----------

            // Map benches to 3 columns: C1 rows, then C2, then C3
            $cols = [1 => [], 2 => [], 3 => []];
            $idx  = 0;

            for ($row = 1; $row <= $rowsCol1; $row++) {
                if (!isset($benches[$idx])) break;
                $cols[1][$row] = $benches[$idx];
                $idx++;
            }

            for ($row = 1; $row <= $rowsCol2; $row++) {
                if (!isset($benches[$idx])) break;
                $cols[2][$row] = $benches[$idx];
                $idx++;
            }

            for ($row = 1; $row <= $rowsCol3; $row++) {
                if (!isset($benches[$idx])) break;
                $cols[3][$row] = $benches[$idx];
                $idx++;
            }

            $invCount = (int) ($allocForRoom->first()->invigilator_count
                ?? $room->faculties_per_room
                ?? 2);

            // --------- INVIGILATOR ASSIGNMENT ----------
            $invigilators = [];

            if ($loadFromSaved && isset($savedAssignmentsByRoom[$roomId])) {
                // Use saved mapping if available
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
            } elseif ($assignFromSelection) {
                $neededInv = $invCount;

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

    public function printInvigilatorMap(Request $r)
    {
        $examId      = (int) $r->input('exam_id');
        $examDate    = $r->input('exam_date');
        $batch       = (int) $r->input('batch');
        $employeeIds = json_decode($r->input('employee_ids_json', '[]'), true);

        $data = $this->generateSeatLayout($examId, $examDate, $batch, $employeeIds, true);

        if (!$data['hasData']) {
            return back()->with('error', 'No data available for invigilator list.');
        }

        $exam       = $data['exam'];
        $rooms      = $data['rooms'];
        $seatLayout = $data['seatLayout'];

        // Build invigilator → rooms map
        $invigilatorMap = [];

        foreach ($seatLayout as $roomId => $layout) {
            $room         = $layout['room'];
            $invigilators = $layout['invigilators'] ?? collect();

            foreach ($invigilators as $inv) {
                $id = $inv->id;

                if (!isset($invigilatorMap[$id])) {
                    $invigilatorMap[$id] = [
                        'employee' => $inv,
                        'rooms'    => [],
                    ];
                }

                $already = collect($invigilatorMap[$id]['rooms'])
                    ->pluck('id')
                    ->contains($room->id);

                if (!$already) {
                    $invigilatorMap[$id]['rooms'][] = $room;
                }
            }
        }

        if (empty($invigilatorMap)) {
            return back()->with('error', 'No invigilators are assigned for this exam & date.');
        }

        $safeDate = str_replace(['/', '\\'], '-', $examDate);

        $pdf = Pdf::loadView('Backend.admin.seat_plans.pdf_invigilator_map', [
            'exam'           => $exam,
            'examDate'       => $examDate,
            'rooms'          => $rooms,
            'seatLayout'     => $seatLayout,
            'invigilatorMap' => $invigilatorMap,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("invigilators-{$safeDate}.pdf");
    }
}

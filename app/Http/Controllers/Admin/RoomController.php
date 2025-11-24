<?php
// app/Http/Controllers/Admin/RoomController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\RoomAllocation;
use App\Models\ExamRegistrationSubject;
use App\Models\FacultySemesterSubject;

class RoomController extends Controller
{
    // ----------------- CRUD (your original methods) -----------------

    public function index()
    {
        $rooms = Room::orderBy('room_no')->paginate(20);
        return view('Backend.admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('Backend.admin.rooms.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'room_no'            => ['required', 'string', 'max:50', 'unique:rooms,room_no'],
            'rows_col1'          => ['required', 'integer', 'min:0'],
            'rows_col2'          => ['required', 'integer', 'min:0'],
            'rows_col3'          => ['required', 'integer', 'min:0'],
            'faculties_per_room' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $data['total_benches'] = $data['rows_col1'] + $data['rows_col2'] + $data['rows_col3'];

        Room::create($data);

        return redirect()->route('rooms.index')->with('ok', 'Room added.');
    }

    public function edit(Room $room)
    {
        return view('Backend.admin.rooms.edit', compact('room'));
    }

    public function update(Request $r, Room $room)
    {
        $data = $r->validate([
            'room_no'            => ['required', 'string', 'max:50', "unique:rooms,room_no,{$room->id}"],
            'rows_col1'          => ['required', 'integer', 'min:0'],
            'rows_col2'          => ['required', 'integer', 'min:0'],
            'rows_col3'          => ['required', 'integer', 'min:0'],
            'faculties_per_room' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $data['total_benches'] = $data['rows_col1'] + $data['rows_col2'] + $data['rows_col3'];

        $room->update($data);

        return redirect()->route('rooms.index')->with('ok', 'Room updated.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return back()->with('ok', 'Room deleted.');
    }

    // ----------------- NEW: Exam seat-plan sheet -----------------

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

        $sem = (int) $sem;  // IMPORTANT FIX

        if ($sem === 0) return 'N/A';

        $year = (int) ceil($sem / 2);
        $part = $sem % 2 == 1 ? 1 : 2;

        return $this->toRoman($year) . '/' . $this->toRoman($part);
    }
    public function examSeatPlanPrint(Request $r)
    {
        $examId   = (int) $r->query('exam_id', 0);
        $examDate = trim((string) $r->query('exam_date', ''));
        $batch    = $r->query('batch');

        // All upcoming exams
        $exams = Exam::where('status', 0)
            ->orderByDesc('id')
            ->get(['id', 'exam_title', 'semester', 'batch', 'start_time', 'end_time']);

        $exam           = null;
        $allocatedDates = [];
        $roomSummaries  = [];
        $summaryRows    = [];
        $hasData        = false;

        // ✅ Validate that both exam_id AND exam_date are provided
        if (!$examId || !$examDate) {
            return view('Backend.admin.rooms.exam_seat_plan_print', compact(
                'exams',
                'exam',
                'examId',
                'examDate',
                'batch',
                'allocatedDates',
                'roomSummaries',
                'summaryRows',
                'hasData'
            ));
        }

        $exam = $exams->firstWhere('id', $examId);

        if (!$exam) {
            return view('Backend.admin.rooms.exam_seat_plan_print', compact(
                'exams',
                'exam',
                'examId',
                'examDate',
                'batch',
                'allocatedDates',
                'roomSummaries',
                'summaryRows',
                'hasData'
            ));
        }

        $examBatchNum = $exam->batch === 'new' ? 1 : 2;
        if ($batch === null || $batch === '') {
            $batch = (string) $examBatchNum;
        }

        // Get all allocated dates for this exam (for reference/validation)
        $allocatedDates = RoomAllocation::where('exam_id', $exam->id)
            ->distinct()
            ->orderBy('exam_date')
            ->pluck('exam_date')
            ->toArray();

        // ✅ Check if the provided exam_date exists in allocations
        if (!in_array($examDate, $allocatedDates, true)) {
            return view('Backend.admin.rooms.exam_seat_plan_print', compact(
                'exams',
                'exam',
                'examId',
                'examDate',
                'batch',
                'allocatedDates',
                'roomSummaries',
                'summaryRows',
                'hasData'
            ));
        }

        // ✅ ONLY get allocations for the SPECIFIC exam_date
        $allocations = RoomAllocation::where('exam_id', $exam->id)
            ->where('exam_date', $examDate)  // 🔹 Filter by specific date
            ->orderBy('room_id')
            ->get();

        if ($allocations->isEmpty()) {
            return view('Backend.admin.rooms.exam_seat_plan_print', compact(
                'exams',
                'exam',
                'examId',
                'examDate',
                'batch',
                'allocatedDates',
                'roomSummaries',
                'summaryRows',
                'hasData'
            ));
        }

        $hasData = true;

        // Get rooms only for this date's allocations
        $rooms = Room::whereIn('id', $allocations->pluck('room_id')->unique())
            ->orderBy('room_no')
            ->get()
            ->keyBy('id');

      $faculties = Faculty::codeOrder()->get()->keyBy('id');   // 🔹 use scope here


        // ✅ All distinct paper keys for THIS SPECIFIC exam date only
        $paperKeys = $allocations->map(fn($a) => $a->faculty_id . '|' . $a->subject_code)
            ->unique()
            ->values();

        $paperStudents = [];   // pKey => collection of ExamRegistrationSubject
        $paperOffsets  = [];   // pKey => int
        $paperMeta     = [];   // pKey => meta for top summary

        foreach ($paperKeys as $pKey) {
            [$fid, $code] = explode('|', $pKey);
            $fid  = (int) $fid;

            // Get students for this specific subject/faculty combination
            $subjects = ExamRegistrationSubject::query()
                ->where('subject_code', $code)
                ->whereHas('registration', function ($q) use ($exam, $batch, $fid) {
                    $q->where('exam_id', $exam->id)
                        ->where('batch', (int) $batch)
                        ->where('faculty_id', $fid);
                })
                ->where(function ($q) {
                    $q->where('th_taking', 1)
                        ->orWhere('p_taking', 1);
                })
                ->with(['registration'])
                ->get()
                ->sortBy(fn($s) => (int) ($s->registration->exam_roll_no ?? 0))
                ->values();

            $paperStudents[$pKey] = $subjects;
            $paperOffsets[$pKey]  = 0;

            // Meta for top summary table
            $firstReg = $subjects->first();
            $semester = $firstReg?->registration?->semester ?? null;
            $semesterRoman = $semester ? $this->semToRomanYearPart($semester) : 'N/A';

            // Resolve subject name
            $subjectName = $code;
            if ($semester !== null) {
                $fss = FacultySemesterSubject::where('faculty_id', $fid)
                    ->where('semester', $semester)
                    ->where('batch', (int) $batch)
                    ->where('subject_code', $code)
                    ->with('subject')
                    ->first();

                if ($fss && $fss->subject) {
                    $subjectName = $fss->subject->name;
                }
            }

            $faculty = $faculties[$fid] ?? null;

            $totalCount = $subjects->count();
            // If you have back examinee logic, modify here
            $regularCount = $totalCount;
            $backCount    = 0;

            $paperMeta[$pKey] = [
                'programme' => $faculty?->name ?? 'N/A',
                    'code'      => $faculty?->code ?? null,  

                'semester'  =>  $semesterRoman,
                'subject'   => $subjectName,
                'regular'   => $regularCount,
                'back'      => $backCount,
                'total'     => $totalCount,
                'remarks'   => '',
            ];
        }

        // ✅ Build room-wise symbol list (bottom table) - ONLY for this date
        $roomSummaries = [];

        foreach ($rooms as $roomId => $room) {
            $allocForRoom = $allocations->where('room_id', $roomId);
            $roomStudents = []; // faculty_id => [rolls]

            foreach ($allocForRoom as $a) {
                $pKey   = $a->faculty_id . '|' . $a->subject_code;
                $needed = (int) $a->student_count;
                if ($needed <= 0) continue;

                $globalList = $paperStudents[$pKey] ?? collect();
                $offset     = $paperOffsets[$pKey] ?? 0;

                $slice = $globalList->slice($offset, $needed);
                $paperOffsets[$pKey] = $offset + $slice->count();

                foreach ($slice as $s) {
                    $symbol = $s->registration->exam_roll_no ?? null;
                    if (!$symbol) continue;

                    $fid = (int) $a->faculty_id;
                    if (!isset($roomStudents[$fid])) {
                        $roomStudents[$fid] = [];
                    }
                    $roomStudents[$fid][] = $symbol;
                }
            }

            $rows      = [];
            $roomTotal = 0;

            // Go through faculties in your custom order
            foreach ($faculties as $fid => $faculty) {
                // If this faculty has no students in this room, skip
                if (!isset($roomStudents[$fid])) {
                    continue;
                }

                $rolls = $roomStudents[$fid];
                sort($rolls, SORT_NUMERIC);

                $rows[] = [
                    'faculty' => $faculty,
                    'rolls'   => $rolls,
                    'total'   => count($rolls),
                ];

                $roomTotal += count($rolls);
            }

        


            $roomSummaries[$roomId] = [
                'room'       => $room,
                'rows'       => $rows,
                'room_total' => $roomTotal,
            ];
        }

        // Build top summary rows in custom faculty order
if (!empty($paperMeta)) {

    $order = ['BCE', 'BEL', 'BEI', 'BCT', 'BEX', 'BME', 'BAG', 'BAR'];

    usort($paperMeta, function ($a, $b) use ($order) {

        $codeA = $a['code'] ?? null;
        $codeB = $b['code'] ?? null;

        // Find position of each faculty code in custom order
        $posA = array_search($codeA, $order);
        $posB = array_search($codeB, $order);

        // Put codes not in order at the end
        $posA = $posA === false ? PHP_INT_MAX : $posA;
        $posB = $posB === false ? PHP_INT_MAX : $posB;

        // If same faculty, sort by subject name
        if ($posA === $posB) {
            return strcmp($a['subject'] ?? '', $b['subject'] ?? '');
        }

        return $posA <=> $posB;
    });

    $summaryRows = array_values($paperMeta);
}


        // Print-friendly view
        return view('Backend.admin.rooms.exam_seat_plan_print', compact(
            'exams',
            'exam',
            'examId',
            'examDate',
            'batch',
            'allocatedDates',
            'roomSummaries',
            'summaryRows',
            'hasData'
        ));
    }
}

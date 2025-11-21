<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    Exam,
    Room,
    Faculty,
    RoutineSlot,
    RoutineSubject,
    FacultySemesterSubject,
    ExamRegistration,
    ExamRegistrationSubject,
    RoomAllocation
};
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;   // ✅ add this

use Illuminate\Validation\ValidationException;

class RoomAllocationController extends Controller
{
    /**
     * Show room allocation grid for an exam + date.
     */
    public function index(Request $r)
    {
        $examId   = (int) $r->query('exam_id', 0);
        $examDate = trim((string) $r->query('exam_date', '')); // same format as RoutineSlot.exam_date
        $batch    = $r->query('batch'); // 1 or 2 (optional if you want to restrict)

        // Exams (you can choose status=0 or 1; here I use status=0: upcoming)
        $exams = Exam::where('status', 0)
            ->orderByDesc('id')
            ->get(['id','exam_title','semester','batch']);

        $rooms     = Room::orderBy('room_no')->get();
$faculties = Faculty::codeOrder()->get(['id','name','code']);

        $exam           = null;
        $examDates      = []; // Available exam dates for selected exam
        $papers         = []; // keyed by paperKey => ['faculty_id','subject_code','subject_name','total_students']
        $totalStudents  = 0;  // total students appearing in this exam (all faculties)
        $allocations    = collect(); // existing allocations for this exam+date
        $allocByRoom    = []; // [room_id][paperKey] = count
        $totalsByRoom   = []; // [room_id] => sum across subjects
        $totalsByPaper  = []; // [paperKey] => sum across rooms

        if ($examId) {
            $exam = $exams->firstWhere('id', $examId);
            if ($exam) {
                $batchNum = $exam->batch === 'new' ? 1 : 2;
                if ($batch === null || $batch === '') {
                    $batch = (string) $batchNum;
                }

                // ✅ Get all available exam dates for this exam from routine slots
                $examDates = RoutineSlot::where('exam_title', $exam->exam_title)
                    ->where('batch', (int) $batch)
                    ->distinct()
                    ->orderBy('exam_date')
                    ->pluck('exam_date')
                    ->toArray();

                if ($examDate !== '' && in_array($examDate, $examDates)) {
                    // 1) Find all routine slots on that date for this exam & batch
                    $slots = RoutineSlot::where('exam_title', $exam->exam_title)
                        ->where('batch', (int) $batch)
                        ->where('exam_date', $examDate)
                        ->with('subjects') // RoutineSubject
                        ->get();

                    if ($slots->isNotEmpty()) {
                        // 2) Build list of "papers" (faculty + subject_code) for that day
                        $paperMap = []; // faculty_id|code => ['faculty_id'=>..,'subject_code'=>..,'semester'=>..]
                        foreach ($slots as $slot) {
                            foreach ($slot->subjects as $sub) {
                                $key = $sub->faculty_id.'|'.$sub->subject_code;
                                if (!isset($paperMap[$key])) {
                                    $paperMap[$key] = [
                                        'faculty_id'   => $sub->faculty_id,
                                        'subject_code' => $sub->subject_code,
                                        'semester'     => $slot->semester,
                                    ];
                                }
                            }
                        }
                        

                        // 3) For each paper, get subject_name and total students registered
                        //    (TH or P; here I count if either th_taking or p_taking is true)
                        foreach ($paperMap as $key => $info) {
                            $fid  = $info['faculty_id'];
                            $code = $info['subject_code'];
                            $sem  = $info['semester'];

                            // Find subject name via FacultySemesterSubject -> Subject (master)
                            $fss = FacultySemesterSubject::where('faculty_id', $fid)
                                ->where('semester', $sem)
                                ->where('batch', $batchNum)
                                ->where('subject_code', $code)
                                ->with('subject')
                                ->first();

                            $subjectName = $fss?->subject?->name ?? $code;

                            // Count students taking this paper
                            $totalForPaper = ExamRegistrationSubject::query()
                                ->where('subject_code', $code)
                                ->whereHas('registration', function ($q) use ($exam, $sem, $batchNum, $fid) {
                                    $q->where('exam_id', $exam->id)
                                      ->where('semester', $sem)
                                      ->where('batch', $batchNum)
                                      ->where('faculty_id', $fid);
                                })
                                ->where(function ($q) {
                                    $q->where('th_taking', 1)
                                      ->orWhere('p_taking', 1);
                                })
                                ->count();

                            $papers[$key] = [
                                'faculty_id'     => $fid,
                                'subject_code'   => $code,
                                'subject_name'   => $subjectName,
                                'semester'       => $sem,
                                'total_students' => $totalForPaper,
                            ];
                        }

                        // 4) Total students in exam (all faculties, all semesters)
                           $totalStudents = array_sum(
        array_column($papers, 'total_students')
    );

                        // 5) Existing allocations for this exam + date
                        $allocations = RoomAllocation::where('exam_id', $exam->id)
                            ->where('exam_date', $examDate)
                            ->get();

                        foreach ($allocations as $a) {
                            $pKey = $a->faculty_id.'|'.$a->subject_code;
                            $allocByRoom[$a->room_id][$pKey] = (int) $a->student_count;

                            if (!isset($totalsByRoom[$a->room_id])) {
                                $totalsByRoom[$a->room_id] = 0;
                            }
                            $totalsByRoom[$a->room_id] += (int) $a->student_count;

                            if (!isset($totalsByPaper[$pKey])) {
                                $totalsByPaper[$pKey] = 0;
                            }
                            $totalsByPaper[$pKey] += (int) $a->student_count;
                        }
                    }
                }
            }
        }

        // sort papers by faculty code then subject code
     // sort papers by faculty custom order, then by subject_code
if (!empty($papers)) {
    // $faculties is already in correct order from codeOrder()
    $facOrder = $faculties->pluck('id')->values()->flip(); 
    // example: [3 => 0, 5 => 1, 2 => 2, ...]

    uasort($papers, function ($a, $b) use ($facOrder) {
        $posA = $facOrder[$a['faculty_id']] ?? 999;
        $posB = $facOrder[$b['faculty_id']] ?? 999;

        if ($posA === $posB) {
            return strcmp($a['subject_code'], $b['subject_code']);
        }
        return $posA <=> $posB;
    });
}


        return view('Backend.admin.room_allocations.index', compact(
            'exams',
            'exam',
            'examId',
            'examDate',
            'examDates',
            'batch',
            'rooms',
            'faculties',
            'papers',
            'totalStudents',
            'allocByRoom',
            'totalsByRoom',
            'totalsByPaper'
        ));
    }

    /**
     * Store/update allocations.
     *
     * Constraints:
     *  - For each subject (paper): sum(all rooms) ≤ total_students for that subject.
     *  - For each room: sum(all subjects) ≤ room.capacity.
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'exam_id'   => 'required|integer|exists:exams,id',
            'exam_date' => 'required|string',
            'batch'     => 'required|in:1,2',
            'alloc'     => 'nullable|array', // [room_id][paperKey] => int
        ]);

        $examId   = (int) $data['exam_id'];
        $examDate = trim($data['exam_date']);
        $batch    = (int) $data['batch'];

        $exam = Exam::findOrFail($examId);
        $batchNum = $exam->batch === 'new' ? 1 : 2;
        if ($batch !== $batchNum) {
            throw ValidationException::withMessages([
                'batch' => ['Batch mismatch for selected exam.'],
            ]);
        }

        // ✅ Validate that exam_date exists in routine for this exam
        $dateExists = RoutineSlot::where('exam_title', $exam->exam_title)
            ->where('batch', $batch)
            ->where('exam_date', $examDate)
            ->exists();

        if (!$dateExists) {
            throw ValidationException::withMessages([
                'exam_date' => ['Invalid exam date. Please select a valid date from the routine.'],
            ]);
        }

        // Rebuild the "papers" list as in index()
        $slots = RoutineSlot::where('exam_title', $exam->exam_title)
            ->where('batch', $batch)
            ->where('exam_date', $examDate)
            ->with('subjects')
            ->get();

        if ($slots->isEmpty()) {
            throw ValidationException::withMessages([
                'exam_date' => ['No routine slots found for this exam & date.'],
            ]);
        }

        $paperMap = []; // key => ['faculty_id','subject_code','semester','total_students']
        foreach ($slots as $slot) {
            foreach ($slot->subjects as $sub) {
                $key = $sub->faculty_id.'|'.$sub->subject_code;
                if (!isset($paperMap[$key])) {
                    $sem = $slot->semester;
                    $totalForPaper = ExamRegistrationSubject::query()
                        ->where('subject_code', $sub->subject_code)
                        ->whereHas('registration', function ($q) use ($exam, $sem, $batch) {
                            $q->where('exam_id', $exam->id)
                              ->where('semester', $sem)
                              ->where('batch', $batch)
                              ->whereNotNull('faculty_id');
                        })
                        ->where(function ($q) {
                            $q->where('th_taking', 1)
                              ->orWhere('p_taking', 1);
                        })
                        ->count();

                    $paperMap[$key] = [
                        'faculty_id'     => $sub->faculty_id,
                        'subject_code'   => $sub->subject_code,
                        'semester'       => $sem,
                        'total_students' => $totalForPaper,
                    ];
                }
            }
        }

        // Room capacities
        $rooms      = Room::all()->keyBy('id');
        $allocInput = $data['alloc'] ?? [];

        // Prepare sums
        $sumByRoom  = []; // [room_id] => total
        $sumByPaper = []; // [paperKey] => total

        // Normalize & validate each cell
        foreach ($allocInput as $roomId => $paperRow) {
            $roomId = (int) $roomId;
            if (!$rooms->has($roomId)) {
                continue; // ignore unknown room
            }

            foreach ($paperRow as $paperKey => $value) {
                $value = (int) $value;
                if ($value < 0) {
                    throw ValidationException::withMessages([
                        "alloc.$roomId.$paperKey" => ['Cannot allocate negative students.'],
                    ]);
                }
                if ($value === 0) {
                    continue;
                }

                if (!isset($paperMap[$paperKey])) {
                    throw ValidationException::withMessages([
                        "alloc.$roomId.$paperKey" => ['Invalid subject for this exam/date.'],
                    ]);
                }

                if (!isset($sumByRoom[$roomId])) {
                    $sumByRoom[$roomId] = 0;
                }
                if (!isset($sumByPaper[$paperKey])) {
                    $sumByPaper[$paperKey] = 0;
                }

                $sumByRoom[$roomId]  += $value;
                $sumByPaper[$paperKey] += $value;
            }
        }

        // Check room capacities
        foreach ($sumByRoom as $roomId => $total) {
            $room       = $rooms[$roomId];
            $capacity   = $room->computed_total_seats; // benches * 2
            if ($total > $capacity) {
                throw ValidationException::withMessages([
                    "alloc.$roomId" => ["Total allocated ($total) exceeds room capacity ($capacity)."],
                ]);
            }
        }

        // Check subject totals
        foreach ($sumByPaper as $paperKey => $total) {
            $totalAllowed = $paperMap[$paperKey]['total_students'] ?? 0;
            if ($total > $totalAllowed) {
                $s = $paperMap[$paperKey]['subject_code'];
                throw ValidationException::withMessages([
                    "alloc.*.$paperKey" => [
                        "Total allocated for subject $s ($total) exceeds total registered students ($totalAllowed)."
                    ],
                ]);
            }
        }

        // Passed all validations → write to DB
        DB::beginTransaction();
        try {
            // Simplest: delete old allocations for this exam+date and reinsert
            RoomAllocation::where('exam_id', $exam->id)
                ->where('exam_date', $examDate)
                ->delete();

            $rowsToInsert = [];
            foreach ($allocInput as $roomId => $paperRow) {
                $roomId = (int) $roomId;
                if (!$rooms->has($roomId)) {
                    continue;
                }
                foreach ($paperRow as $paperKey => $value) {
                    $value = (int) $value;
                    if ($value <= 0) continue;

                    if (!isset($paperMap[$paperKey])) {
                        continue;
                    }
                    $paper = $paperMap[$paperKey];

                    $rowsToInsert[] = [
                        'exam_id'       => $exam->id,
                        'exam_date'     => $examDate,
                        'room_id'       => $roomId,
                        'faculty_id'    => $paper['faculty_id'],
                        'subject_code'  => $paper['subject_code'],
                        'student_count' => $value,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
            }

            if (!empty($rowsToInsert)) {
                RoomAllocation::insert($rowsToInsert);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            throw ValidationException::withMessages([
                'alloc' => ['Failed to save room allocations: '.$e->getMessage()],
            ]);
        }

        return redirect()->route('room_allocations.index', [
            'exam_id'   => $exam->id,
            'exam_date' => $examDate,
            'batch'     => $batch,
        ])->with('ok', 'Room allocation saved successfully.');
    }
    public function printPdf(Request $r)
{
    $data = $r->validate([
        'exam_id'   => 'required|integer|exists:exams,id',
        'exam_date' => 'required|string',
        'batch'     => 'required|in:1,2',
    ]);

    $examId   = (int) $data['exam_id'];
    $examDate = trim($data['exam_date']);
    $batch    = (int) $data['batch'];

    $exam = Exam::findOrFail($examId);
    $batchNum = $exam->batch === 'new' ? 1 : 2;
    if ($batch !== $batchNum) {
        throw ValidationException::withMessages([
            'batch' => ['Batch mismatch for selected exam.'],
        ]);
    }

    // Ensure this date is valid for this exam
    $dateExists = RoutineSlot::where('exam_title', $exam->exam_title)
        ->where('batch', $batch)
        ->where('exam_date', $examDate)
        ->exists();

    if (! $dateExists) {
        throw ValidationException::withMessages([
            'exam_date' => ['Invalid exam date for this exam.'],
        ]);
    }

    // Faculties (for code/name display)
    $faculties = Faculty::codeOrder()->get(['id','name','code'])->keyBy('id');

    // Rebuild "papers" and allocations (almost same as index())
    $slots = RoutineSlot::where('exam_title', $exam->exam_title)
        ->where('batch', $batch)
        ->where('exam_date', $examDate)
        ->with('subjects')
        ->get();

    if ($slots->isEmpty()) {
        throw ValidationException::withMessages([
            'exam_date' => ['No routine slots found for this exam & date.'],
        ]);
    }

    // 1) Build paper map
    $paperMap = []; // key => ['faculty_id','subject_code','semester']
    foreach ($slots as $slot) {
        foreach ($slot->subjects as $sub) {
            $key = $sub->faculty_id.'|'.$sub->subject_code;
            if (!isset($paperMap[$key])) {
                $paperMap[$key] = [
                    'faculty_id'   => $sub->faculty_id,
                    'subject_code' => $sub->subject_code,
                    'semester'     => $slot->semester,
                ];
            }
        }
    }

    // 2) Build papers with subject_name + total_students
    $papers = [];
    $totalStudents = 0;
    $batchNum = $batch; // alias

    foreach ($paperMap as $key => $info) {
        $fid  = $info['faculty_id'];
        $code = $info['subject_code'];
        $sem  = $info['semester'];

        $fss = FacultySemesterSubject::where('faculty_id', $fid)
            ->where('semester', $sem)
            ->where('batch', $batchNum)
            ->where('subject_code', $code)
            ->with('subject')
            ->first();

        $subjectName = $fss?->subject?->name ?? $code;

        $totalForPaper = ExamRegistrationSubject::query()
            ->where('subject_code', $code)
            ->whereHas('registration', function ($q) use ($exam, $sem, $batchNum, $fid) {
                $q->where('exam_id', $exam->id)
                  ->where('semester', $sem)
                  ->where('batch', $batchNum)
                  ->where('faculty_id', $fid);
            })
            ->where(function ($q) {
                $q->where('th_taking', 1)
                  ->orWhere('p_taking', 1);
            })
            ->count();

        $papers[$key] = [
            'faculty_id'     => $fid,
            'subject_code'   => $code,
            'subject_name'   => $subjectName,
            'semester'       => $sem,
            'total_students' => $totalForPaper,
        ];

        $totalStudents += $totalForPaper;
    }

    // 3) Existing allocations
    $allocations = RoomAllocation::where('exam_id', $exam->id)
        ->where('exam_date', $examDate)
        ->get();

    $allocByRoom   = []; // [room_id][paperKey] = count
    $totalsByRoom  = []; // [room_id] => total
    $totalsByPaper = []; // [paperKey] => total

    foreach ($allocations as $a) {
        $pKey = $a->faculty_id.'|'.$a->subject_code;

        if (!isset($allocByRoom[$a->room_id])) {
            $allocByRoom[$a->room_id] = [];
        }
        $allocByRoom[$a->room_id][$pKey] = (int) $a->student_count;

        if (!isset($totalsByRoom[$a->room_id])) {
            $totalsByRoom[$a->room_id] = 0;
        }
        $totalsByRoom[$a->room_id] += (int) $a->student_count;

        if (!isset($totalsByPaper[$pKey])) {
            $totalsByPaper[$pKey] = 0;
        }
        $totalsByPaper[$pKey] += (int) $a->student_count;
    }

    // 4) Only rooms that actually have allocations
    $roomIds = array_keys($allocByRoom);
    $rooms = Room::whereIn('id', $roomIds)
        ->orderBy('room_no')
        ->get();

    // 5) Sort papers by faculty custom order, then subject_code
    if (!empty($papers)) {
        $facOrder = $faculties->pluck('id')->values()->flip();
        uasort($papers, function ($a, $b) use ($facOrder) {
            $posA = $facOrder[$a['faculty_id']] ?? 999;
            $posB = $facOrder[$b['faculty_id']] ?? 999;

            if ($posA === $posB) {
                return strcmp($a['subject_code'], $b['subject_code']);
            }
            return $posA <=> $posB;
        });
    }

    // 6) Render PDF
   // 6) Render PDF
$pdf = Pdf::loadView('Backend.admin.room_allocations.print', [
    'exam'          => $exam,
    'examDate'      => $examDate,    // keep original for display
    'batch'         => $batch,
    'rooms'         => $rooms,
    'faculties'     => $faculties,
    'papers'        => $papers,
    'allocByRoom'   => $allocByRoom,
    'totalsByRoom'  => $totalsByRoom,
    'totalsByPaper' => $totalsByPaper,
    'totalStudents' => $totalStudents,
])->setPaper('A4', 'landscape');
$safeDate  = str_replace(['/', '\\'], '-', $examDate);
$fileName = 'room-plan-'.$exam->id.'-'.$safeDate.'.pdf';


return $pdf->stream($fileName);

}

}
<?php
// app/Http/Controllers/Admin/ExamAttendanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\{Exam, Faculty, ExamSeat, ExamAttendance,Room, Subject};

class ExamAttendanceController extends Controller
{
    /**
     * Show filter + attendance form (for one exam/date/faculty).
     */
   public function index(Request $r)
{
    $examId    = (int) $r->query('exam_id', 0);
    $examDate  = (string) $r->query('exam_date', '');
    $facultyId = (int) $r->query('faculty_id', 0);

    // All active exams
    $exams = Exam::where('status', 0)
        ->orderByDesc('id')
        ->get();

    $exam           = null;
    $batch          = null;          // derived from exam
    $examDates      = collect();     // dates from ExamSeat for this exam+batch
    $faculties      = collect();     // faculties that actually have seats
    $roomsMap       = collect();
    $subjectsByRoom = [];

    // ----------------- EXAM + DATES + FACULTIES FILTER -----------------
    if ($examId) {
        $exam = $exams->firstWhere('id', $examId);

        if ($exam) {
            // Auto derive batch from exam (no batch dropdown in UI)
            $batch = $exam->batch === 'new' ? 1 : 2;

            // Get all dates that have seat plan for this exam+batch
            // ❗ do NOT change order or format
            $examDates = ExamSeat::where('exam_id', $examId)
                ->where('batch', $batch)
                ->distinct()
                ->pluck('exam_date');

            // When a date is chosen, load only faculties that appear in ExamSeat
            if ($examDate !== '') {

                $facultyIds = ExamSeat::where('exam_id', $examId)
                    ->where('exam_date', $examDate)
                    ->where('batch', $batch)
                    ->distinct()
                    ->pluck('faculty_id');

                if ($facultyIds->isNotEmpty()) {
                    $faculties = Faculty::whereIn('id', $facultyIds)
                        ->codeOrder()      // your custom BCE, BEL, ... order
                        ->get();
                }
            }
        }
    }

    // ----------------- LOAD SEATS + ATTENDANCE -----------------
    if ($examId && $examDate !== '' && $batch && $facultyId) {

        // All seats for this exam/date/batch/faculty
        $seats = ExamSeat::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->where('batch', $batch)
            ->where('faculty_id', $facultyId)
            ->orderBy('subject_code')
            ->orderBy('room_id')
            ->orderBy('column_no')
            ->orderBy('row_no')
            ->orderBy('side')
            ->get();

        // Room map (id → Room) so we can show room_no
        $roomIds  = $seats->pluck('room_id')->unique()->values();
        $roomsMap = Room::whereIn('id', $roomIds)->get()->keyBy('id');

        // Subject map (code → Subject) so we can show subject name
        $subjectCodes = $seats->pluck('subject_code')->unique()->values();
        $subjectsMap  = Subject::whereIn('code', $subjectCodes)->get()->keyBy('code');

        // Existing attendance to pre-mark absentees
        $attendance = ExamAttendance::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->where('batch', $batch)
            ->where('faculty_id', $facultyId)
            ->get()
            ->groupBy('subject_code');

        // Group seats: subject → room → symbols
        $subjectsByRoom = $seats
            ->groupBy('subject_code')
            ->map(function ($bySubject) use ($attendance, $subjectsMap) {
                return $bySubject->groupBy('room_id')->map(function ($byRoom) use ($attendance, $subjectsMap) {

                    $subject = $byRoom->first()->subject_code;
                    $roomId  = $byRoom->first()->room_id;

                    // symbols in THIS room (sorted ascending)
                    $symbols = $byRoom->pluck('symbol_no')
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();

                    // ----- FIX: per-room absentees -----
                    // global absentees for this subject (all rooms)
                    $globalAbsent = isset($attendance[$subject])
                        ? $attendance[$subject]
                            ->where('status', 'absent')
                            ->pluck('symbol_no')
                            ->unique()
                            ->values()
                            ->all()
                        : [];

                    // only those absent who actually sit in THIS room
                    $absentSymbols = array_values(array_intersect($symbols, $globalAbsent));

                    return [
                        'room_id'        => $roomId,
                        'symbols'        => $symbols,
                        'absent_symbols' => $absentSymbols,
                        'subject_name'   => $subjectsMap[$subject]->name ?? $subject,
                    ];
                });
            })
            ->toArray();
    }

    // ----------------- VIEW -----------------
    return view('Backend.admin.exam_attendance.index', compact(
        'exams',
        'faculties',
        'exam',
        'examId',
        'examDate',
        'batch',
        'facultyId',
        'examDates',
        'subjectsByRoom',
        'roomsMap'
    ));
}


    /**
     * Save attendance for chosen exam/date/faculty (all subjects).
     * Request structure:
     *  absent[subject_code][room_id][] = [symbol_no, ...]
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'exam_id'    => 'required|integer',
            'exam_date'  => 'required|string',   // keep as string (dd/mm/yyyy etc.)
            'batch'      => 'required|integer',
            'faculty_id' => 'required|integer',
            'absent'     => 'array',             // subject_code => room_id => [symbol...]
        ]);

        $examId    = (int) $data['exam_id'];
        $examDate  = $data['exam_date'];   // keep raw
        $batch     = (int) $data['batch'];
        $facultyId = (int) $data['faculty_id'];
        $absentArr = $data['absent'] ?? [];

        // First get all symbols for this faculty (from saved seats)
        $allSeats = ExamSeat::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->where('batch', $batch)
            ->where('faculty_id', $facultyId)
            ->get()
            ->groupBy('subject_code');

        // Remove existing attendance for this faculty/exam/date
        ExamAttendance::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->where('batch', $batch)
            ->where('faculty_id', $facultyId)
            ->delete();

        $rows = [];

        foreach ($allSeats as $subjectCode => $seats) {
            $allSymbols = $seats->pluck('symbol_no')->unique()->values()->all();

            // Flatten absent symbols for this subject from all rooms
            $subjectAbsent = [];
            if (isset($absentArr[$subjectCode])) {
                foreach ($absentArr[$subjectCode] as $roomId => $symbols) {
                    foreach ((array) $symbols as $sym) {
                        $subjectAbsent[] = $sym;
                    }
                }
            }
            $subjectAbsent = array_values(array_unique($subjectAbsent));

            $subjectPresent = array_values(array_diff($allSymbols, $subjectAbsent));

            foreach ($subjectPresent as $sym) {
                $rows[] = [
                    'exam_id'      => $examId,
                    'exam_date'    => $examDate,
                    'batch'        => $batch,
                    'faculty_id'   => $facultyId,
                    'subject_code' => $subjectCode,
                    'symbol_no'    => $sym,
                    'status'       => 'present',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            foreach ($subjectAbsent as $sym) {
                $rows[] = [
                    'exam_id'      => $examId,
                    'exam_date'    => $examDate,
                    'batch'        => $batch,
                    'faculty_id'   => $facultyId,
                    'subject_code' => $subjectCode,
                    'symbol_no'    => $sym,
                    'status'       => 'absent',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 1000) as $chunk) {
                ExamAttendance::insert($chunk);
            }
        }

        return back()->with('success', 'Attendance saved successfully.');
    }
       public function storeRoom(Request $r)
{
    $data = $r->validate([
        'exam_id'     => 'required|integer',
        'exam_date'   => 'required|string',
        'batch'       => 'required|integer',
        'faculty_id'  => 'required|integer',
        'subject_code'=> 'required|string',
        'room_id'     => 'required|integer',
        'absent'      => 'array',
    ]);

    $examId     = $data['exam_id'];
    $examDate   = $data['exam_date'];
    $batch      = $data['batch'];
    $facultyId  = $data['faculty_id'];
    $subject    = $data['subject_code'];
    $roomId     = $data['room_id'];
    $absentArr  = $data['absent'] ?? [];

    // Get all seats for THIS ROOM only
    $seats = ExamSeat::where('exam_id', $examId)
        ->where('exam_date', $examDate)
        ->where('batch', $batch)
        ->where('faculty_id', $facultyId)
        ->where('subject_code', $subject)
        ->where('room_id', $roomId)
        ->pluck('symbol_no')
        ->unique()
        ->values()
        ->all();

    // Delete old attendance for JUST THIS ROOM
    ExamAttendance::where('exam_id', $examId)
        ->where('exam_date', $examDate)
        ->where('batch', $batch)
        ->where('faculty_id', $facultyId)
        ->where('subject_code', $subject)
        ->where('room_id', $roomId)
        ->delete();

    $absentSymbols = $absentArr[$roomId] ?? [];
    $presentSymbols = array_values(array_diff($seats, $absentSymbols));

    // Insert new attendance
    $rows = [];

    foreach ($presentSymbols as $sym) {
        $rows[] = [
            'exam_id'     => $examId,
            'exam_date'   => $examDate,
            'batch'       => $batch,
            'faculty_id'  => $facultyId,
            'subject_code'=> $subject,
            'room_id'     => $roomId,
            'symbol_no'   => $sym,
            'status'      => 'present',
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    foreach ($absentSymbols as $sym) {
        $rows[] = [
            'exam_id'     => $examId,
            'exam_date'   => $examDate,
            'batch'       => $batch,
            'faculty_id'  => $facultyId,
            'subject_code'=> $subject,
            'room_id'     => $roomId,
            'symbol_no'   => $sym,
            'status'      => 'absent',
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

    ExamAttendance::insert($rows);

    return back()->with('success', "Room $roomId attendance saved successfully.");
}

    /**
     * Download packet form PDF for ALL faculties & subjects
     * for a given exam/date/batch.
     */
    public function downloadAllPackets(Request $r)
    {
        $data = $r->validate([
            'exam_id'   => 'required|integer',
            'exam_date' => 'required|string',   // keep as string
            'batch'     => 'required|integer',
        ]);

        $examId   = (int) $data['exam_id'];
        $examDate = $data['exam_date'];
        $batch    = (int) $data['batch'];

        $exam = Exam::findOrFail($examId);

        // Get attendance grouped by faculty & subject
        $attendance = ExamAttendance::where('exam_id', $examId)
            ->where('exam_date', $examDate)
            ->where('batch', $batch)
            ->orderBy('faculty_id')
            ->orderBy('subject_code')
            ->orderBy('symbol_no')
            ->get()
            ->groupBy(fn($a) => $a->faculty_id . '|' . $a->subject_code);

        // Build packets array
        $packets = [];

        foreach ($attendance as $key => $items) {
            [$facultyId, $subjectCode] = explode('|', $key);

            $faculty = Faculty::find($facultyId);

            $present = $items->where('status', 'present')
                ->pluck('symbol_no')
                ->unique()
                ->values()
                ->all();

            $absent = $items->where('status', 'absent')
                ->pluck('symbol_no')
                ->unique()
                ->values()
                ->all();

            $packets[] = [
                'faculty'       => $faculty,
                'subject_code'  => $subjectCode,
                'exam'          => $exam,
                'exam_date'     => $examDate,
                'batch'         => $batch,
                'present'       => $present,
                'absent'        => $absent,
                'present_total' => count($present),
                'absent_total'  => count($absent),
            ];
        }

        if (empty($packets)) {
            return back()->with('error', 'No attendance data found. Save attendance first.');
        }

        $pdf = Pdf::loadView('Backend.admin.exam_attendance.pdf_packets', [
            'packets'  => $packets,
            'exam'     => $exam,
            'examDate' => $examDate,
        ])->setPaper('a4', 'portrait');

        $safeDate = str_replace(['/', '\\'], '-', $examDate);
        return $pdf->stream("exam-packets-{$safeDate}.pdf");
    }
}

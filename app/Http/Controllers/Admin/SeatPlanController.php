<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Generate seating plan for an exam date based on room allocations.
     */
    public function index(Request $r)
    {
        $examId      = (int) $r->query('exam_id', 0);
        $examDate    = trim((string) $r->query('exam_date', ''));
        $batchParam  = $r->query('batch');
        $employeeIds = array_filter((array) $r->query('employee_ids', []));

        // Get all exams
        $exams = Exam::where('status', 0)
            ->orderByDesc('id')
            ->get(['id', 'exam_title', 'semester', 'batch']);

        // Get all active employees
        $employees = Employee::where('is_active', true)
            ->orderBy('employee_type', 'desc') // faculty first
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_type']);

        // Get allocated dates for selected exam
        $allocatedDates = [];
        if ($examId) {
            $allocatedDates = RoomAllocation::where('exam_id', $examId)
                ->distinct()
                ->pluck('exam_date')
                ->sort()
                ->values()
                ->toArray();
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
                // Determine batch
                $examBatchNum = $exam->batch === 'new' ? 1 : 2;
                $batch        = $batchParam ? (int) $batchParam : $examBatchNum;

                // Get all allocations for this exam + date
                $allocations = RoomAllocation::where('exam_id', $exam->id)
                    ->where('exam_date', $examDate)
                    ->orderBy('room_id')
                    ->get();

                if ($allocations->isNotEmpty()) {
                    $hasData = true;

                    // Get rooms
                    $rooms = Room::whereIn('id', $allocations->pluck('room_id')->unique())
                        ->orderBy('room_no')
                        ->get()
                        ->keyBy('id');

                    // Build paper keys
                    $paperKeys = $allocations->map(function ($a) {
                        return $a->faculty_id . '|' . $a->subject_code;
                    })->unique()->values();

                    // Get students for each paper
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

                        // Sort by symbol number
                        $sorted = $subjects->sortBy(function ($s) {
                            return (int) ($s->registration->exam_roll_no ?? 0);
                        })->values();

                        $paperStudents[$pKey] = $sorted;
                        $paperOffsets[$pKey]  = 0;

                        $subjectName = $sorted->first()?->fss?->subject?->name ?? $code;

                        $paperInfo[$pKey] = [
                            'faculty_id'   => (int) $fid,
                            'subject_code' => $code,
                            'subject_name' => $subjectName,
                        ];
                    }

                    // Build employee pools for invigilator assignment
                    $selectedEmployees = $employees->whereIn('id', $employeeIds)->values();
                    
                    $basePool = $selectedEmployees->isNotEmpty()
                        ? $selectedEmployees->shuffle()->values()
                        : $employees->shuffle()->values();

                    $staffPool   = $basePool->where('employee_type', 'staff')->values();
                    $facultyPool = $basePool->where('employee_type', 'faculty')->values();

                    // Build seat layout for each room
                    foreach ($rooms as $roomId => $room) {
                        $totalBenches = $room->computed_total_benches;
                        $totalSeats   = $totalBenches * 2;

                        // Get allocations for this room
                        $allocForRoom = $allocations->where('room_id', $roomId);

                        // Build queues of students per paper
                        $roomQueues = [];
                        foreach ($allocForRoom as $a) {
                            $pKey   = $a->faculty_id . '|' . $a->subject_code;
                            $needed = (int) $a->student_count;
                            
                            if ($needed <= 0) continue;

                            $globalList = $paperStudents[$pKey] ?? collect();
                            $offset     = $paperOffsets[$pKey] ?? 0;

                            $slice = $globalList->slice($offset, $needed);
                            $paperOffsets[$pKey] = $offset + $slice->count();

                            if ($slice->isEmpty()) continue;

                            foreach ($slice as $s) {
                                $roomQueues[$pKey][] = [
                                    'symbol_no'    => $s->registration->exam_roll_no ?? null,
                                    'subject_key'  => $pKey,
                                    'subject_code' => $a->subject_code,
                                    'faculty_id'   => $a->faculty_id,
                                ];
                            }
                        }

                        // Skip if no students
                        if (empty($roomQueues)) {
                            $seatLayout[$roomId] = [
                                'room'         => $room,
                                'invigilators' => [],
                                'cols'         => [1 => [], 2 => [], 3 => []],
                            ];
                            continue;
                        }

                        // Helper to get remaining students per subject
                        $remaining = function () use (&$roomQueues) {
                            $res = [];
                            foreach ($roomQueues as $key => $queue) {
                                $res[$key] = count($queue);
                            }
                            return $res;
                        };

                        // Build linear seat list with alternation
                        // Strategy: alternate subjects, place same faculty at opposite corners
                        $seatList = array_fill(0, $totalSeats, null);
                        $prevSubjectKey = null;

                        for ($i = 0; $i < $totalSeats; $i++) {
                            $rem = $remaining();
                            if (array_sum($rem) === 0) break;

                            // Find subjects different from previous
                            $candidates = [];
                            foreach ($rem as $key => $cnt) {
                                if ($cnt > 0 && $key !== $prevSubjectKey) {
                                    $candidates[$key] = $cnt;
                                }
                            }

                            // If only one subject left, allow it
                            if (empty($candidates)) {
                                foreach ($rem as $key => $cnt) {
                                    if ($cnt > 0) {
                                        $candidates[$key] = $cnt;
                                    }
                                }
                            }

                            if (empty($candidates)) break;

                            // Pick subject with most remaining students
                            arsort($candidates);
                            $chosenKey = array_key_first($candidates);

                            if (!empty($roomQueues[$chosenKey])) {
                                $student = array_shift($roomQueues[$chosenKey]);
                                $seatList[$i] = $student;
                                $prevSubjectKey = $chosenKey;
                            }
                        }

                        // Convert to benches (2 seats per bench)
                        // Place students at opposite corners when from same faculty
                        $benches = array_fill(0, $totalBenches, ['left' => null, 'right' => null]);
                        
                        for ($b = 0; $b < $totalBenches; $b++) {
                            $leftIndex  = $b * 2;
                            $rightIndex = $leftIndex + 1;

                            $benches[$b]['left']  = $seatList[$leftIndex] ?? null;
                            $benches[$b]['right'] = $seatList[$rightIndex] ?? null;
                        }

                        // Map benches into 3 columns
                        $cols = [1 => [], 2 => [], 3 => []];
                        $idx = 0;

                        // Column 1
                        for ($row = 1; $row <= $room->rows_col1; $row++) {
                            if (!isset($benches[$idx])) break;
                            $cols[1][$row] = $benches[$idx];
                            $idx++;
                        }
                        
                        // Column 2
                        for ($row = 1; $row <= $room->rows_col2; $row++) {
                            if (!isset($benches[$idx])) break;
                            $cols[2][$row] = $benches[$idx];
                            $idx++;
                        }
                        
                        // Column 3
                        for ($row = 1; $row <= $room->rows_col3; $row++) {
                            if (!isset($benches[$idx])) break;
                            $cols[3][$row] = $benches[$idx];
                            $idx++;
                        }

                        // Assign invigilators (1 faculty + 1 staff preferred)
                        $neededInv = $room->faculties_per_room ?: 2;
                        $invigilators = [];

                        // Try one from each type
                        if ($neededInv > 0 && $facultyPool->isNotEmpty()) {
                            $invigilators[] = $facultyPool->shift();
                            $neededInv--;
                        }
                        
                        if ($neededInv > 0 && $staffPool->isNotEmpty()) {
                            $invigilators[] = $staffPool->shift();
                            $neededInv--;
                        }

                        // Fill remaining from either pool
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
            'hasData'
        ));
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\FacultySemesterSubject;
use App\Models\RoutineSlot;
use App\Models\RoutineSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoutineBuilderController extends Controller
{
    /**
     * Create page:
     * - Exams (status=0)
     * - After exam + semester selected:
     *   * Show current routine (matrix)
     *   * Build "remaining subjects" per faculty (exclude already scheduled)
     */
    public function create(Request $r)
    {
        $examId           = (int) $r->query('exam_id', 0);
        $selectedSemester = (int) $r->query('semester', 0);

        // Only exams with status = 0
        $exams = Exam::where('status', 0)
            ->latest()
            ->get(['id','exam_title','semester','batch','start_time','end_time']);

        $exam        = null;
        $batchNum    = null;
        $allowedSems = [];
        $faculties   = collect();
        $presets     = [];   // [faculty_id] => [ ['code'=>..., 'name'=>...], ... ]  (remaining only)
        $slots       = collect(); // existing RoutineSlot for the chosen exam+semester+batch with eager subjects

        if ($examId) {
            $exam = $exams->firstWhere('id', $examId);
            if (!$exam) {
                abort(404, 'Exam not found or not eligible (status != 0).');
            }

            $batchNum    = $exam->batch === 'new' ? 1 : 2;
            $allowedSems = Exam::semesterNumbers($exam->semester);
            $faculties   = Faculty::orderBy('code')->get(['id','name','code']);

            if ($selectedSemester && in_array($selectedSemester, $allowedSems, true)) {
                // Load existing slots and their subjects for this exam_title + batch + semester
                $slots = RoutineSlot::where('exam_title', $exam->exam_title)
                    ->where('batch', $batchNum)
                    ->where('semester', $selectedSemester)
                    ->orderBy('exam_date')           // exam_date is BS string; sorting will be lexicographic
                    ->with(['subjects' => function($q){
                        $q->select('id','routine_slot_id','faculty_id','subject_code');
                    }])
                    ->get(['id','exam_date','start_time','end_time','semester','exam_title','batch']);

                // Build per-faculty set of already used subject codes
                $usedByFaculty = []; // [faculty_id] => [ 'SUB1' => true, ... ]
                foreach ($slots as $slot) {
                    foreach ($slot->subjects as $sub) {
                        $usedByFaculty[$sub->faculty_id][strtoupper($sub->subject_code)] = true;
                    }
                }

                // All possible subjects per faculty for this semester/batch
                $all = FacultySemesterSubject::where('semester', $selectedSemester)
                    ->where('batch', $batchNum)
                    ->orderBy('faculty_id')
                    ->orderBy('subject_code')
                    ->get(['faculty_id','subject_code','subject_name'])
                    ->groupBy('faculty_id');

                // presets = remainingSubjects = all - used
                foreach ($all as $fid => $items) {
                    $remain = [];
                    foreach ($items as $it) {
                        $code = strtoupper($it->subject_code);
                        if (empty($usedByFaculty[$fid][$code])) {
                            $remain[] = ['code' => $it->subject_code, 'name' => $it->subject_name];
                        }
                    }
                    $presets[$fid] = $remain; // may be empty => show "No Subjects"
                }
            }
        }

        return view('Backend.admin.routine_builder.create', compact(
            'exams', 'exam', 'batchNum', 'allowedSems', 'selectedSemester', 'faculties', 'presets', 'slots'
        ));
    }

    /**
     * Store:
     * - BS date string kept as-is
     * - One radio per faculty per day
     * - Validates: subject belongs to (semester,batch,faculty) AND is not already scheduled
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'exam_id'          => 'required|integer|exists:exams,id',
            'semester'         => 'required|integer',
            'days'             => 'required|array|min:1',
            'days.*.date'      => 'required|string', // BS dd/mm/yyyy
            'days.*.subjects'  => 'nullable|array',
        ]);

        $exam = Exam::where('id', $data['exam_id'])
            ->where('status', 0)
            ->firstOrFail();

        $batchNum = $exam->batch === 'new' ? 1 : 2;
        $semester = (int) $data['semester'];

        // Check odd/even rule
        if (!in_array($semester, Exam::semesterNumbers($exam->semester), true)) {
            throw ValidationException::withMessages([
                'semester' => ['Selected semester is not allowed for this exam (odd/even mismatch).']
            ]);
        }

        // Build catalog of valid subjects per faculty for (semester,batch)
        $catalog = FacultySemesterSubject::where('semester', $semester)
            ->where('batch', $batchNum)
            ->get(['faculty_id','subject_code'])
            ->groupBy('faculty_id')
            ->map(fn($g) => $g->pluck('subject_code')->unique()->values()->all());

        // Build current set of already used subject codes per faculty for this exam/sem/batch
        $usedNow = RoutineSlot::where('exam_title', $exam->exam_title)
            ->where('batch', $batchNum)
            ->where('semester', $semester)
            ->with('subjects:id,routine_slot_id,faculty_id,subject_code')
            ->get(['id']);

        $usedByFaculty = []; // [faculty_id] => [ 'SUB1' => true, ... ]
        foreach ($usedNow as $slot) {
            foreach ($slot->subjects as $sub) {
                $usedByFaculty[$sub->faculty_id][strtoupper($sub->subject_code)] = true;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($data['days'] as $i => $day) {
                $bsDate = trim((string)($day['date'] ?? ''));
                if ($bsDate === '') {
                    throw ValidationException::withMessages([
                        "days.$i.date" => ['BS date (dd/mm/yyyy) is required.']
                    ]);
                }

                $slot = RoutineSlot::create([
                    'exam_date'  => $bsDate,             // store BS string
                    'start_time' => $exam->start_time,
                    'end_time'   => $exam->end_time,
                    'semester'   => $semester,
                    'exam_title' => $exam->exam_title,
                    'batch'      => $batchNum,
                ]);

                $subs = $day['subjects'] ?? [];
                foreach ($subs as $facultyId => $value) {
                    // enforce single selection (radio)
                    if (is_array($value)) {
                        throw ValidationException::withMessages([
                            "days.$i.subjects.$facultyId" => ['Select only one subject per faculty per day.']
                        ]);
                    }

                    $facultyId   = (int) $facultyId;
                    $subjectCode = trim((string) $value);
                    if ($subjectCode === '') continue;

                    // Validate subject is valid for this faculty in (semester,batch)
                    $validForFaculty = $catalog->get($facultyId, []);
                    if (!in_array($subjectCode, $validForFaculty, true)) {
                        throw ValidationException::withMessages([
                            "days.$i.subjects.$facultyId" => ["Invalid subject '$subjectCode' for this faculty (semester/batch)."]
                        ]);
                    }

                    // Ensure not already scheduled (race-safe)
                    if (!empty($usedByFaculty[$facultyId][strtoupper($subjectCode)])) {
                        throw ValidationException::withMessages([
                            "days.$i.subjects.$facultyId" => ["Subject '$subjectCode' is already scheduled for this exam/semester/batch."]
                        ]);
                    }

                    RoutineSubject::create([
                        'routine_slot_id' => $slot->id,
                        'faculty_id'      => $facultyId,
                        'subject_code'    => $subjectCode,
                    ]);

                    // Mark as used for subsequent inserts in the same request
                    $usedByFaculty[$facultyId][strtoupper($subjectCode)] = true;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            if ($e instanceof ValidationException) {
                throw $e;
            }
            return back()->withErrors(['error' => 'Failed to save routine: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('routine.builder.create', [
            'exam_title' => $exam->exam_title,
            'batch'      => (string) $batchNum,
            'semester'   => $semester,
        ])->with('ok', 'Routine saved successfully!');
    }
}

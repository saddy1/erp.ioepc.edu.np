<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{
    Exam,
    Faculty,
    Student,
    ExamRegistration,
    ExamRegistrationSubject,
    FacultySemesterSubject
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentImportController extends Controller
{
    /**
     * Show import form
     */
    public function create(Request $r)
    {
        // Only exams with status = 0 (scheduled / not completed)
        $exams = Exam::where('status', 0)
            ->orderByDesc('id')
            ->get(['id', 'exam_title', 'semester', 'batch']);

        $exam        = null;
        $allowedSems = [];

        if ($r->filled('exam_id')) {
            $exam = $exams->firstWhere('id', (int) $r->exam_id);
            if ($exam) {
                $allowedSems = Exam::semesterNumbers($exam->semester); // odd/even semesters
            }
        }

        $faculties = Faculty::orderBy('code')->get(['id', 'code', 'name']);

        return view('Backend.admin.exam_import.create', compact(
            'exams',
            'exam',
            'allowedSems',
            'faculties'
        ));
    }

    /**
     * Store imported students + their subject choices
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'exam_id'   => 'required|exists:exams,id',
            'semester'  => 'required|integer|min:1|max:12',
            'faculty_id'=> 'nullable|exists:faculties,id',
            'file'      => 'required|file|mimes:csv,txt',
            'assume_practical_from_columns' => 'nullable|boolean',
        ]);

        $exam = Exam::findOrFail($data['exam_id']);
        $batchNum = $exam->batch === 'new' ? 1 : 2;
        $batchLabel = $exam->batch === 'new' ? 'New' : 'Old';

        // Odd/even guard
        if (!in_array((int) $data['semester'], Exam::semesterNumbers($exam->semester), true)) {
            throw ValidationException::withMessages([
                'semester' => ['Selected semester is not allowed for this exam (odd/even mismatch).']
            ]);
        }

        $semester          = (int) $data['semester'];
        $facultyIdFromForm = $data['faculty_id'] ?? null;

        // Build subject catalog for this (semester, batch), grouped by faculty_id AND subject_code
        $fss = FacultySemesterSubject::where('semester', $semester)
            ->where('batch', $batchNum)
            ->with('subject:id,has_practical')
            ->get(['id', 'faculty_id', 'subject_id', 'subject_code', 'semester']);

        // Create lookup map: "faculty_id:subject_code" => FacultySemesterSubject
        $fssLookup = $fss->mapWithKeys(function($item) {
            return [$item->faculty_id . ':' . $item->subject_code => $item];
        });

        // Read CSV rows
        $rows = [];
        if (($handle = fopen($r->file('file')->getRealPath(), 'r')) !== false) {
            while (($line = fgetcsv($handle)) !== false) {
                $rows[] = $line;
            }
            fclose($handle);
        }

        if (count($rows) < 3) {
            throw ValidationException::withMessages([
                'file' => ['No data rows found in CSV (need header + TH/P row + data).']
            ]);
        }

        /**
         * 1) Find header row (SN, Campus Roll No, Name, etc.)
         */
        $hdrIdx = null;
        foreach ($rows as $i => $cols) {
            $normalized = array_map(fn($v) => trim(mb_strtolower((string) $v)), $cols);
            if (in_array('campus roll no', $normalized) && in_array('name', $normalized)) {
                $hdrIdx = $i;
                break;
            }
        }
        if ($hdrIdx === null) {
            throw ValidationException::withMessages([
                'file' => ['Header row not found. CSV must have a row with "Campus Roll No" and "Name".']
            ]);
        }

        $headers = $rows[$hdrIdx];

        // Column indices for base fields
        $idx = [
            'campus_roll' => null,
            'exam_roll'   => null,
            'token'       => null,
            'name'        => null,
            'amount'      => null,
        ];

        foreach ($headers as $c => $h) {
            $hL = trim(mb_strtolower((string) $h));
            if ($hL === 'campus roll no')                       $idx['campus_roll'] = $c;
            if ($hL === 'exam rollno' || $hL === 'exam roll no') $idx['exam_roll']  = $c;
            if ($hL === 'token no'   || $hL === 'token')         $idx['token']      = $c;
            if ($hL === 'name')                                  $idx['name']       = $c;
            if ($hL === 'amount')                                $idx['amount']     = $c;
        }

        foreach ($idx as $k => $v) {
            if ($v === null && $k !== 'amount') {
                throw ValidationException::withMessages([
                    'file' => ["Required column missing: $k"]
                ]);
            }
        }

        /**
         * 2) Subject column discovery
         * Row: $hdrIdx      → subject labels with codes "(ENSH151)"
         * Row: $hdrIdx + 1  → TH / P under those subjects
         */
        $subjectCols = [];
        $codeRx = '/\(([A-Z]{2,}\d{3})\)/i';

        $thpRow = $rows[$hdrIdx + 1] ?? [];

        for ($c = 0; $c < count($headers); $c++) {
            $h = (string) $headers[$c];

            if (preg_match($codeRx, $h, $m)) {
                $code  = strtoupper($m[1]);
                $label = trim(preg_replace($codeRx, '', $h));

                $thCol = null;
                $pCol  = null;

                // Scan in TH/P row near this column
                for ($k = $c; $k <= min($c + 3, count($thpRow) - 1); $k++) {
                    $cell = trim(mb_strtolower((string) ($thpRow[$k] ?? '')));
                    if ($cell === 'th' && $thCol === null) {
                        $thCol = $k;
                    } elseif ($cell === 'p' && $pCol === null) {
                        $pCol = $k;
                    }
                }

                $subjectCols[] = [
                    'label' => $label,
                    'code'  => $code,
                    'th'    => $thCol,
                    'p'     => $pCol,
                ];
            }
        }

        if (empty($subjectCols)) {
            throw ValidationException::withMessages([
                'file' => ['Could not detect any subject columns (subject headers + TH/P row).']
            ]);
        }

        /**
         * 3) Program → faculty_id mapping
         */
        $programToFacultyId = Faculty::pluck('id', 'code')
            ->mapWithKeys(fn($id, $code) => [strtoupper($code) => $id]);

        $assumeP = (bool) ($data['assume_practical_from_columns'] ?? false);

        // Data starts AFTER: header row + TH/P row
        $startRow = $hdrIdx + 2;

        /**
         * ✅ PRE-VALIDATION: Check all subjects exist in the selected semester/batch
         */
        $missingSubjects = [];
        $availableFaculties = $fss->pluck('faculty_id')->unique();

        // Get all unique subject codes from CSV
        $csvSubjectCodes = collect($subjectCols)->pluck('code')->unique();

        foreach ($csvSubjectCodes as $code) {
            // Check if this subject exists for ANY faculty in this semester/batch
            $existsInSemester = $fss->where('subject_code', $code)->isNotEmpty();
            
            if (!$existsInSemester) {
                $missingSubjects[] = $code;
            }
        }

        if (!empty($missingSubjects)) {
            $missingList = implode(', ', $missingSubjects);
            throw ValidationException::withMessages([
                'file' => [
                    "The following subjects are NOT configured for Semester {$semester} (Batch: {$batchLabel}): {$missingList}",
                    "Please ensure all subjects in the CSV are added to the Faculty-Semester-Subject mapping for Semester {$semester} before importing."
                ]
            ]);
        }

        // ✅ Track import statistics (only new imports)
        $stats = [
            'imported' => [],     // ['exam_roll' => ['name' => '...', 'campus_roll' => '...'], ...]
            'total_processed' => 0,
        ];

        DB::beginTransaction();
        try {
            for ($ridx = $startRow; $ridx < count($rows); $ridx++) {
                $row = $rows[$ridx];

                // Skip empty lines
                if (!isset($row[$idx['campus_roll']]) || trim((string) $row[$idx['campus_roll']]) === '') {
                    continue;
                }

                $stats['total_processed']++;

                $campusRoll = trim((string) $row[$idx['campus_roll']]);
                $name       = trim((string) ($row[$idx['name']] ?? ''));
                $examRoll   = trim((string) ($row[$idx['exam_roll']] ?? ''));
                $token      = trim((string) ($row[$idx['token']] ?? ''));
                $amount     = (int) trim((string) ($row[$idx['amount']] ?? '0'));

                // Parse campus roll
                $campusCode  = null;
                $batchCode   = null;
                $programCode = null;

                if (preg_match('/^([A-Z]{3})(\d{3})([A-Z]{3})/i', $campusRoll, $m)) {
                    $campusCode  = strtoupper($m[1]);
                    $batchCode   = $m[2];
                    $programCode = strtoupper($m[3]);
                }

                // Resolve faculty_id
                $facultyId = $facultyIdFromForm
                    ?: ($programCode && isset($programToFacultyId[$programCode])
                        ? $programToFacultyId[$programCode]
                        : null);

                /**
                 * Upsert Student
                 */
                $student = Student::updateOrCreate(
                    ['campus_roll_no' => $campusRoll],
                    [
                        'name'         => $name ?: $campusRoll,
                        'campus_code'  => $campusCode,
                        'batch_code'   => $batchCode,
                        'program_code' => $programCode,
                        'faculty_id'   => $facultyId,
                    ]
                );

                /**
                 * Check if already registered (skip if exists)
                 */
                $existingReg = ExamRegistration::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->where('semester', $semester)
                    ->first();

                if ($existingReg) {
                    // Skip - already registered, don't track
                    continue;
                }

                /**
                 * Create NEW ExamRegistration
                 */
                $reg = ExamRegistration::create([
                    'exam_id'      => $exam->id,
                    'student_id'   => $student->id,
                    'semester'     => $semester,
                    'faculty_id'   => $facultyId,
                    'batch'        => $batchNum,
                    'exam_roll_no' => $examRoll,
                    'token_no'     => $token,
                    'amount'       => $amount,
                ]);

                // ✅ Track newly imported student
                $stats['imported'][$examRoll ?: $campusRoll] = [
                    'name'        => $name ?: $campusRoll,
                    'campus_roll' => $campusRoll,
                ];

                /**
                 * Per-row subject TH/P flags → ExamRegistrationSubject
                 */
                foreach ($subjectCols as $def) {
                    $code = $def['code'];

                    $takeTH = false;
                    $takeP  = false;

                    if ($def['th'] !== null && isset($row[$def['th']])) {
                        $takeTH = trim((string) $row[$def['th']]) === '1';
                    }
                    if ($def['p'] !== null && isset($row[$def['p']])) {
                        $takeP = trim((string) $row[$def['p']]) === '1';
                    } elseif ($assumeP) {
                        $takeP = $def['p'] !== null;
                    }

                    if (!$takeTH && !$takeP) {
                        continue;
                    }

                    // Look up FSS using BOTH faculty_id AND subject_code
                    $fssId        = null;
                    $hasPractical = false;
                    
                    if ($facultyId) {
                        $lookupKey = $facultyId . ':' . $code;
                        $found = $fssLookup->get($lookupKey);
                        
                        if ($found) {
                            $fssId        = $found->id;
                            $hasPractical = (bool) optional($found->subject)->has_practical;
                        } else {
                            // Subject not found for this faculty - validation should have caught this
                            throw new \Exception("Subject {$code} not found for the student's faculty in Semester {$semester}");
                        }
                    }

                    // If P chosen but subject has no practical, drop P
                    if ($takeP && !$hasPractical) {
                        $takeP = false;
                    }

                    ExamRegistrationSubject::create([
                        'exam_registration_id'        => $reg->id,
                        'subject_code'                => $code,
                        'faculty_id'                  => $facultyId ?? ($student->faculty_id ?? null),
                        'faculty_semester_subject_id' => $fssId,
                        'th_taking'                   => $takeTH,
                        'p_taking'                    => $takeP,
                    ]);
                }
            }

            DB::commit();

            // ✅ Build success message (only imported students)
            $message = $this->buildImportSummary($stats, $exam->exam_title, $semester, $batchLabel);

            return back()->with('ok', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            
            // Don't show SQL errors to user
            \Log::error('Student Import Failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            throw ValidationException::withMessages([
                'file' => ['Import failed. Please check that all subjects are properly configured for this semester and batch, and that your CSV format is correct.']
            ]);
        }
    }

    /**
     * Build human-readable import summary (only NEW imports)
     */
    private function buildImportSummary(array $stats, string $examTitle, int $semester, string $batch): string
    {
        $imported = $stats['imported'];
        $count = count($imported);

        if ($count === 0) {
            return "No new students were imported. All students in the CSV are already registered for this exam and semester.";
        }

        $lines = [];
        $lines[] = "✓ Successfully imported {$count} student" . ($count !== 1 ? 's' : '');
        $lines[] = "Exam: {$examTitle}";
        $lines[] = "Semester: {$semester} | Batch: {$batch}";
        $lines[] = "";
        $lines[] = "IMPORTED STUDENTS:";
        
        foreach ($imported as $examRoll => $info) {
            $lines[] = "  • {$examRoll} — {$info['name']} (Campus: {$info['campus_roll']})";
        }

        return implode("\n", $lines);
    }
}
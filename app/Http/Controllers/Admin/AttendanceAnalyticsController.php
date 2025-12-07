<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\RoutineFeedback;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceAnalyticsController extends Controller
{
    /**
     * Analytics dashboard (Blade view with charts)
     */
    public function index(Request $request)
    {
        $faculties = Faculty::orderBy('name')->get();
        $teachers  = Teacher::orderBy('name')->get();

        // we will load students dynamically, so just empty now
        $students  = collect();

        // Default filter (daily – today)
        $mode      = $request->get('mode', 'daily');
        $today     = Carbon::today();
        $from      = $request->get('from');
        $to        = $request->get('to');

        if (!$from || !$to) {
            [$from, $to] = $this->defaultRange($mode, $today);
        }

        return view('Backend.dashboard.index', [
            'faculties'   => $faculties,
            'teachers'    => $teachers,
            'students'    => $students,
            'defaultMode' => $mode,
            'defaultFrom' => $from,
            'defaultTo'   => $to,
        ]);
    }

    /**
     * Return JSON data for graphs
     */
    public function data(Request $request)
    {
        $mode       = $request->get('mode', 'daily');
        $today      = Carbon::today();
        $from       = $request->get('from');
        $to         = $request->get('to');

        if (!$from || !$to) {
            [$from, $to] = $this->defaultRange($mode, $today);
        }

        // Filters
        $facultyId  = $request->get('faculty_id');
        $teacherId  = $request->get('teacher_id');
        $studentId  = $request->get('student_id');
        $sectionId  = $request->get('section_id');
        $semester   = $request->get('semester');
        $batch      = $request->get('batch');
        $subjectId  = $request->get('subject_id');
        $groupId    = $request->get('group_id');

        // -------------------- BASE ATTENDANCE QUERY --------------------
        $baseAttendance = Attendance::query()
            ->betweenDates($from, $to)
            ->forFaculty($facultyId)
            ->forTeacher($teacherId)
            ->forStudent($studentId)
            ->forSection($sectionId)
            ->forSemester($semester)
            ->forBatch($batch)
            ->forSubject($subjectId)
            ->forGroup($groupId);

        // Global counts: total / present / absent
        $global = (clone $baseAttendance)
            ->selectRaw('COUNT(*) as total,
                         SUM(status = "P") as present,
                         SUM(status = "A") as absent')
            ->first();

        $total   = (int) ($global->total ?? 0);
        $present = (int) ($global->present ?? 0);
        $absent  = (int) ($global->absent ?? 0);
        $presentRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $absentRate  = $total > 0 ? round(($absent / $total) * 100, 1) : 0;

        // -------------------- DAILY / WEEKLY / MONTHLY TREND --------------------
        $trendByDate = (clone $baseAttendance)
            ->selectRaw('date as day,
                         COUNT(*) as total,
                         SUM(status = "P") as present,
                         SUM(status = "A") as absent')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // -------------------- BY FACULTY --------------------
        $byFaculty = (clone $baseAttendance)
            ->join('routines', 'attendances.routine_id', '=', 'routines.id')
            ->join('faculties', 'routines.faculty_id', '=', 'faculties.id')
            ->selectRaw('faculties.id as faculty_id,
                         faculties.code as faculty_code,
                         COUNT(*) as total,
                         SUM(status = "P") as present,
                         SUM(status = "A") as absent')
            ->groupBy('faculties.id', 'faculties.code')
            ->orderBy('faculties.code')
            ->get();

        // -------------------- BY TEACHER --------------------
        $byTeacher = (clone $baseAttendance)
            ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
            ->selectRaw('teachers.id as teacher_id,
                         teachers.name as teacher_name,
                         COUNT(*) as total,
                         SUM(status = "P") as present,
                         SUM(status = "A") as absent')
            ->groupBy('teachers.id', 'teachers.name')
            ->orderBy('teachers.name')
            ->get();

        // -------------------- BY STUDENT (for charts / table) --------------------
        $byStudent = (clone $baseAttendance)
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->selectRaw('students.id as student_id,
                         students.symbol_no as symbol_no,
                         students.name as student_name,
                         COUNT(*) as total,
                         SUM(status = "P") as present,
                         SUM(status = "A") as absent')
            ->groupBy('students.id', 'students.symbol_no', 'students.name')
            ->orderBy('students.symbol_no')
            ->get();

        // -------------------- TAUGHT / NOT TAUGHT (RoutineFeedback) --------------------
        $baseFeedback = RoutineFeedback::query()
            ->betweenDates($from, $to)
            ->forFaculty($facultyId)
            ->forTeacher($teacherId)
            ->forSection($sectionId)
            ->forSemester($semester);

        $globalFeedback = (clone $baseFeedback)
            ->selectRaw('COUNT(*) as total,
                         SUM(status = "taught") as taught,
                         SUM(status = "not_taught") as not_taught')
            ->first();

        $totalClasses = (int) ($globalFeedback->total ?? 0);
        $taught       = (int) ($globalFeedback->taught ?? 0);
        $notTaught    = (int) ($globalFeedback->not_taught ?? 0);
        $taughtRate   = $totalClasses > 0 ? round(($taught / $totalClasses) * 100, 1) : 0;

        // -------------------- STUDENT-SPECIFIC TIMELINE (optional) --------------------
        $studentTimeline = [];
        if ($studentId) {
            $studentTimeline = Attendance::query()
                ->betweenDates($from, $to)
                ->forStudent($studentId)
                ->forFaculty($facultyId)
                ->forSection($sectionId)
                ->forSemester($semester)
                ->forBatch($batch)
                ->forSubject($subjectId)
                ->forGroup($groupId)
                ->selectRaw('date as day,
                             SUM(status = "P") as present,
                             SUM(status = "A") as absent,
                             COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->get();
        }

        return response()->json([
            'filters' => [
                'from'      => $from,
                'to'        => $to,
                'mode'      => $mode,
                'facultyId' => $facultyId,
                'teacherId' => $teacherId,
                'studentId' => $studentId,
                'sectionId' => $sectionId,
                'semester'  => $semester,
                'batch'     => $batch,
                'subjectId' => $subjectId,
                'groupId'   => $groupId,
            ],
            'global' => [
                'total'       => $total,
                'present'     => $present,
                'absent'      => $absent,
                'presentRate' => $presentRate,
                'absentRate'  => $absentRate,
            ],
            'trendByDate'    => $trendByDate,
            'byFaculty'      => $byFaculty,
            'byTeacher'      => $byTeacher,
            'byStudent'      => $byStudent,
            'taughtStats'    => [
                'totalClasses' => $totalClasses,
                'taught'       => $taught,
                'notTaught'    => $notTaught,
                'taughtRate'   => $taughtRate,
            ],
            'studentTimeline' => $studentTimeline,
        ]);
    }

    /**
     * Export detailed CSV report (all rows in the filtered range)
     */
    public function export(Request $request)
    {
        $mode   = $request->get('mode', 'daily');
        $today  = Carbon::today();
        $from   = $request->get('from');
        $to     = $request->get('to');

        if (!$from || !$to) {
            [$from, $to] = $this->defaultRange($mode, $today);
        }

        $facultyId  = $request->get('faculty_id');
        $teacherId  = $request->get('teacher_id');
        $studentId  = $request->get('student_id');
        $sectionId  = $request->get('section_id');
        $semester   = $request->get('semester');
        $batch      = $request->get('batch');
        $subjectId  = $request->get('subject_id');
        $groupId    = $request->get('group_id');

        $rows = Attendance::query()
            ->with(['student.faculty', 'student.section', 'teacher', 'routine.subject', 'routine.faculty'])
            ->betweenDates($from, $to)
            ->forFaculty($facultyId)
            ->forTeacher($teacherId)
            ->forStudent($studentId)
            ->forSection($sectionId)
            ->forSemester($semester)
            ->forBatch($batch)
            ->forSubject($subjectId)
            ->forGroup($groupId)
            ->orderBy('date')
            ->orderBy('teacher_id')
            ->orderBy('student_id')
            ->get();

        $filename = "attendance-report-{$from}-to-{$to}.csv";

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // Header
            fputcsv($handle, [
                'Date',
                'Faculty Code',
                'Semester',
                'Batch',
                'Section',
                'Subject Code',
                'Subject Name',
                'Teacher Name',
                'Student Symbol No',
                'Student Name',
                'Status (P/A)',
            ]);

            foreach ($rows as $att) {
                $r = $att->routine;
                $s = $att->student;
                $t = $att->teacher;
                $f = $r?->faculty;

                fputcsv($handle, [
                    $att->date->toDateString(),
                    $f?->code ?? '',
                    $r?->semester ?? '',
                    $r?->batch ?? '',
                    $r?->section?->name ?? '',
                    $r?->subject?->code ?? '',
                    $r?->subject?->name ?? '',
                    $t?->name ?? '',
                    $s?->symbol_no ?? '',
                    $s?->name ?? '',
                    $att->status,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * AJAX: sections for a faculty
     */
    public function sections(Request $request)
    {
        $facultyId = $request->get('faculty_id');

        if (!$facultyId) {
            return response()->json([]);
        }

        $sections = Section::where('faculty_id', $facultyId)
            ->orderBy('section_name')
            ->get(['id', 'section_name as name']);

        return response()->json($sections);
    }

    /**
     * AJAX: subjects for faculty + semester
     */
    public function subjects(Request $request)
    {
        $facultyId = $request->get('faculty_id');
        $semester  = $request->get('semester');

        if (!$facultyId || !$semester) {
            return response()->json([]);
        }

        $subjects = Subject::where('faculty_id', $facultyId)
            ->where('semester', $semester)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json($subjects);
    }

    /**
     * AJAX: students by faculty + section + group
     * (adjust conditions to match your schema)
     */
    public function students(Request $request)
    {
        $facultyId = $request->get('faculty_id');
        $sectionId = $request->get('section_id');
        $groupId   = $request->get('group_id');

        $q = Student::query();

        if ($facultyId) {
            $q->where('faculty_id', $facultyId);
        }
        if ($sectionId) {
            $q->where('section_id', $sectionId);
        }
        if ($groupId) {
            $q->where('group_id', $groupId);
        }

        $students = $q->orderBy('symbol_no')
            ->get(['id', 'symbol_no', 'name']);

        return response()->json($students);
    }

    /**
     * Helper: default date ranges for daily / weekly / monthly
     */
    protected function defaultRange(string $mode, Carbon $today): array
    {
        switch ($mode) {
            case 'weekly':
                $from = $today->copy()->subDays(6)->toDateString();
                $to   = $today->toDateString();
                break;

            case 'monthly':
                $from = $today->copy()->firstOfMonth()->toDateString();
                $to   = $today->toDateString();
                break;

            case 'custom':
                // fallback if user did not send from/to
                $from = $today->toDateString();
                $to   = $today->toDateString();
                break;

            case 'daily':
            default:
                $from = $today->toDateString();
                $to   = $today->toDateString();
                break;
        }

        return [$from, $to];
    }
}

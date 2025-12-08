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
use App\Models\Routine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $faculties = Faculty::orderBy('name')->get();
        $teachers  = Teacher::orderBy('name')->get();

        $mode  = $request->get('mode', 'daily');
        $today = Carbon::today();
        $from  = $request->get('from');
        $to    = $request->get('to');

        if (!$from || !$to) {
            [$from, $to] = $this->defaultRange($mode, $today);
        }

        return view('Backend.dashboard.index', [
            'faculties'   => $faculties,
            'teachers'    => $teachers,
            'defaultMode' => $mode,
            'defaultFrom' => $from,
            'defaultTo'   => $to,
        ]);
    }

    public function data(Request $request)
    {
        try {
            Log::info('Analytics data request started', ['params' => $request->all()]);

            $mode  = $request->get('mode', 'daily');
            $today = Carbon::today();
            $from  = $request->get('from');
            $to    = $request->get('to');

            if (!$from || !$to) {
                [$from, $to] = $this->defaultRange($mode, $today);
            }

            // Filters
            $facultyId = $request->get('faculty_id');
            $teacherId = $request->get('teacher_id');
            $studentId = $request->get('student_id');
            $sectionId = $request->get('section_id');
            $semester  = $request->get('semester');
            $batch     = $request->get('batch');
            $subjectId = $request->get('subject_id');
            $groupId   = $request->get('group_id');

            Log::info('Date range calculated', ['from' => $from, 'to' => $to]);

            // Base attendance query
            $baseAttendance = $this->buildAttendanceQuery(
                $from, $to, $facultyId, $teacherId, $studentId, 
                $sectionId, $semester, $batch, $subjectId, $groupId
            );

            Log::info('Base query built');

            // Global statistics
   $global = (clone $baseAttendance)
    ->selectRaw('
        COUNT(DISTINCT attendances.student_id, attendances.date, attendances.routine_id) as total_classes_marked,
        COUNT(DISTINCT CASE WHEN attendances.status = "P" THEN CONCAT(attendances.student_id, "-", attendances.date, "-", attendances.routine_id) END) as present_count,
        COUNT(DISTINCT CASE WHEN attendances.status = "A" THEN CONCAT(attendances.student_id, "-", attendances.date, "-", attendances.routine_id) END) as absent_count
    ')
    ->first();


            Log::info('Global stats retrieved', ['global' => $global]);

            $total   = (int) ($global->total ?? 0);
            $present = (int) ($global->present ?? 0);
            $absent  = (int) ($global->absent ?? 0);
            $presentRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
            $absentRate  = $total > 0 ? round(($absent / $total) * 100, 1) : 0;

            // Trend by date
            $trendByDate = (clone $baseAttendance)
                ->selectRaw('attendances.date as day,
                             COUNT(*) as total,
                             SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                             SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            Log::info('Trend data retrieved', ['count' => $trendByDate->count()]);

            // By Faculty
            $byFaculty = $this->getAttendanceByFaculty($from, $to, $facultyId, $sectionId, $semester, $subjectId, $groupId);

            // By Teacher
            $byTeacher = $this->getAttendanceByTeacher($from, $to, $facultyId, $teacherId, $sectionId, $semester, $subjectId);

            // By Subject
            $bySubject = $this->getAttendanceBySubject($from, $to, $facultyId, $sectionId, $semester, $subjectId);

            // By Section
            $bySection = $this->getAttendanceBySection($from, $to, $facultyId, $sectionId, $semester);

            // By Student
            $byStudent = $this->getAttendanceByStudent($from, $to, $facultyId, $studentId, $sectionId, $semester, $batch, $subjectId, $groupId);

            // Taught statistics
            $taughtStats = $this->getTaughtStatistics($from, $to, $facultyId, $teacherId, $sectionId, $semester, $subjectId);

            // Contradictions
            $contradictions = $this->findContradictions($from, $to, $facultyId, $sectionId, $semester, $subjectId);

            // Student timeline
            $studentTimeline = [];
            if ($studentId) {
                $studentTimeline = $this->getStudentTimeline($studentId, $from, $to, $facultyId, $sectionId, $semester, $subjectId);
            }

            Log::info('All data retrieved successfully');

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
                'trendByDate'     => $trendByDate,
                'byFaculty'       => $byFaculty,
                'byTeacher'       => $byTeacher,
                'bySubject'       => $bySubject,
                'bySection'       => $bySection,
                'byStudent'       => $byStudent,
                'taughtStats'     => $taughtStats,
                'contradictions'  => $contradictions,
                'studentTimeline' => $studentTimeline,
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics data error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    protected function buildAttendanceQuery($from, $to, $facultyId, $teacherId, $studentId, $sectionId, $semester, $batch, $subjectId, $groupId)
    {
        $query = DB::table('attendances')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($teacherId) {
            $query->where('attendances.teacher_id', $teacherId);
        }

        if ($studentId) {
            $query->where('attendances.student_id', $studentId);
        }

        // Join with students for faculty/section/group filtering
        if ($facultyId || $sectionId || $groupId || $batch) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');
            
            if ($facultyId) {
                $query->where('students.faculty_id', $facultyId);
            }
            if ($sectionId) {
                $query->where('students.section_id', $sectionId);
            }
            if ($groupId) {
                $query->where('students.group_id', $groupId);
            }
            if ($batch) {
                $query->where('students.batch', $batch);
            }
        }

        // Join with routines for subject/semester filtering
        if ($subjectId || $semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            
            if ($subjectId) {
                $query->where('routines.subject_id', $subjectId);
            }
            if ($semester) {
                $query->where('routines.semester', $semester);
            }
        }

        return $query;
    }

    protected function getAttendanceByFaculty($from, $to, $facultyId, $sectionId, $semester, $subjectId, $groupId)
    {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('faculties', 'students.faculty_id', '=', 'faculties.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($facultyId) $query->where('faculties.id', $facultyId);
        if ($sectionId) $query->where('students.section_id', $sectionId);
        if ($groupId) $query->where('students.group_id', $groupId);

        return $query->selectRaw('
                faculties.id as faculty_id,
                faculties.code as faculty_code,
                faculties.name as faculty_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                ROUND((SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as present_rate
            ')
            ->groupBy('faculties.id', 'faculties.code', 'faculties.name')
            ->orderBy('faculties.code')
            ->get();
    }

    protected function getAttendanceByTeacher($from, $to, $facultyId, $teacherId, $sectionId, $semester, $subjectId)
    {
        $query = DB::table('attendances')
            ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($teacherId) $query->where('teachers.id', $teacherId);

        if ($facultyId || $sectionId) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');
            if ($facultyId) $query->where('students.faculty_id', $facultyId);
            if ($sectionId) $query->where('students.section_id', $sectionId);
        }

        if ($subjectId || $semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
            if ($semester) $query->where('routines.semester', $semester);
        }

        return $query->selectRaw('
                teachers.id as teacher_id,
                teachers.name as teacher_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                ROUND((SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as present_rate
            ')
            ->groupBy('teachers.id', 'teachers.name')
            ->orderBy('teachers.name')
            ->get();
    }

    protected function getAttendanceBySubject($from, $to, $facultyId, $sectionId, $semester, $subjectId)
    {
        $query = DB::table('attendances')
            ->join('routines', 'attendances.routine_id', '=', 'routines.id')
            ->join('subjects', 'routines.subject_id', '=', 'subjects.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($subjectId) $query->where('subjects.id', $subjectId);
        if ($semester) $query->where('routines.semester', $semester);

        if ($facultyId || $sectionId) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');
            if ($facultyId) $query->where('students.faculty_id', $facultyId);
            if ($sectionId) $query->where('students.section_id', $sectionId);
        }

        return $query->selectRaw('
                subjects.id as subject_id,
                subjects.code as subject_code,
                subjects.name as subject_name,
                routines.semester,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                COUNT(DISTINCT attendances.student_id) as unique_students,
                ROUND((SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as absent_rate
            ')
            ->groupBy('subjects.id', 'subjects.code', 'subjects.name', 'routines.semester')
            ->orderBy('subjects.code')
            ->get();
    }

    protected function getAttendanceBySection($from, $to, $facultyId, $sectionId, $semester)
    {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($facultyId) $query->where('students.faculty_id', $facultyId);
        if ($sectionId) $query->where('sections.id', $sectionId);

        if ($semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id')
                  ->where('routines.semester', $semester);
        }

      return $query->selectRaw('
        sections.id as section_id,
        sections.name as section_name,
        COUNT(*) as total,
        SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
        COUNT(DISTINCT students.id) as unique_students,
        ROUND((SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as present_rate
    ')
    ->groupBy('sections.id', 'sections.name')
    ->orderBy('sections.name')
    ->get();

    }

    protected function getAttendanceByStudent($from, $to, $facultyId, $studentId, $sectionId, $semester, $batch, $subjectId, $groupId)
    {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($studentId) $query->where('students.id', $studentId);
        if ($facultyId) $query->where('students.faculty_id', $facultyId);
        if ($sectionId) $query->where('students.section_id', $sectionId);
        if ($groupId) $query->where('students.group_id', $groupId);
        if ($batch) $query->where('students.batch', $batch);

        if ($subjectId || $semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
            if ($semester) $query->where('routines.semester', $semester);
        }

        return $query->selectRaw('
                students.id as student_id,
                students.symbol_no,
                students.name as student_name,
sections.name as section_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                ROUND((SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as present_rate,
                ROUND((SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as absent_rate
            ')
->groupBy('students.id', 'students.symbol_no', 'students.name', 'sections.name')
            ->orderBy('students.symbol_no')
            ->limit(100)
            ->get();
    }

  protected function getTaughtStatistics($from, $to, $facultyId, $teacherId, $sectionId, $semester, $subjectId)
{
    $query = DB::table('routine_feedback as rf')
        ->join('routines as r', 'rf.routine_id', '=', 'r.id')
        ->whereBetween('rf.class_date', [$from, $to]);

    // filters
    if ($teacherId) {
        // assuming routines table has teacher_id
        $query->where('r.teacher_id', $teacherId);
    }
    if ($facultyId) {
        $query->where('r.faculty_id', $facultyId);
    }
    if ($sectionId) {
        $query->where('r.section_id', $sectionId);
    }
    if ($semester) {
        $query->where('r.semester', $semester);
    }
    if ($subjectId) {
        $query->where('r.subject_id', $subjectId);
    }

    $result = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN rf.status = "taught" THEN 1 ELSE 0 END) as taught,
            SUM(CASE WHEN rf.status = "not_taught" THEN 1 ELSE 0 END) as not_taught
        ')
        ->first();

    $total      = (int) ($result->total ?? 0);
    $taught     = (int) ($result->taught ?? 0);
    $notTaught  = (int) ($result->not_taught ?? 0);
    $taughtRate = $total > 0 ? round(($taught / $total) * 100, 1) : 0;

    return [
        'totalClasses' => $total,
        'taught'       => $taught,
        'notTaught'    => $notTaught,
        'taughtRate'   => $taughtRate,
    ];
}


protected function findContradictions($from, $to, $facultyId, $sectionId, $semester, $subjectId)
{
    $query = DB::table('routine_feedback as rf')
        ->join('routines as r', 'rf.routine_id', '=', 'r.id')
        ->leftJoin('subjects', 'r.subject_id', '=', 'subjects.id')
        ->leftJoin('teachers', 'r.teacher_id', '=', 'teachers.id')
        ->leftJoin('sections', 'r.section_id', '=', 'sections.id')
        ->leftJoin('attendances as a', function ($join) {
            $join->on('rf.routine_id', '=', 'a.routine_id')
                 ->on('rf.class_date', '=', 'a.date');
                 // if you also want to match teacher:
                 // ->on('r.teacher_id', '=', 'a.teacher_id');
        })
        ->whereBetween('rf.class_date', [$from, $to])
        ->where('rf.status', 'taught');

    if ($facultyId) {
        $query->where('r.faculty_id', $facultyId);
    }
    if ($sectionId) {
        $query->where('r.section_id', $sectionId);
    }
    if ($semester) {
        $query->where('r.semester', $semester);
    }
    if ($subjectId) {
        $query->where('r.subject_id', $subjectId);
    }

    return $query->selectRaw('
            rf.class_date as class_date,
            subjects.code as subject_code,
            subjects.name as subject_name,
            teachers.name as teacher_name,
            sections.name as section_name,
            r.semester,
            COUNT(DISTINCT a.id) as attendance_count,
            CASE 
                WHEN COUNT(DISTINCT a.id) = 0 THEN "No attendance recorded"
                WHEN COUNT(DISTINCT a.id) < 5 THEN "Very low attendance records"
                ELSE "Normal"
            END as issue_type
        ')
        ->groupBy(
            'rf.class_date',
            'subjects.code',
            'subjects.name',
            'teachers.name',
            'sections.name',
            'r.semester',
            'rf.routine_id'
        )
        ->having('attendance_count', '<', 5)
        ->orderBy('rf.class_date', 'desc')
        ->limit(50)
        ->get();
}

    protected function getStudentTimeline($studentId, $from, $to, $facultyId, $sectionId, $semester, $subjectId)
    {
        $query = DB::table('attendances')
            ->where('student_id', $studentId)
            ->whereBetween('date', [$from, $to]);

        if ($facultyId || $sectionId || $semester || $subjectId) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            
            if ($facultyId) $query->where('routines.faculty_id', $facultyId);
            if ($sectionId) $query->where('routines.section_id', $sectionId);
            if ($semester) $query->where('routines.semester', $semester);
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
        }

        return $query->selectRaw('
                date as day,
                SUM(CASE WHEN status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as absent,
                COUNT(*) as total
            ')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    public function export(Request $request)
    {
        $mode = $request->get('mode', 'daily');
        $today = Carbon::today();
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$from || !$to) {
            [$from, $to] = $this->defaultRange($mode, $today);
        }

        $rows = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
            ->join('routines', 'attendances.routine_id', '=', 'routines.id')
            ->leftJoin('subjects', 'routines.subject_id', '=', 'subjects.id')
            ->leftJoin('faculties', 'students.faculty_id', '=', 'faculties.id')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to])
            ->select([
                'attendances.date',
                'faculties.code as faculty_code',
                'routines.semester',
                'students.batch',
'sections.name as section_name',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'teachers.name as teacher_name',
                'students.symbol_no',
                'students.name as student_name',
                'attendances.status',
            ])
            ->orderBy('attendances.date')
            ->orderBy('students.symbol_no')
            ->get();

        $filename = "attendance-report-{$from}-to-{$to}.csv";

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Date', 'Faculty', 'Semester', 'Batch', 'Section',
                'Subject Code', 'Subject Name', 'Teacher', 
                'Symbol No', 'Student Name', 'Status'
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function sections(Request $request)
    {
        $facultyId = $request->get('faculty_id');

        if (!$facultyId) {
            return response()->json([]);
        }

      $sections = Section::where('faculty_id', $facultyId)
    ->orderBy('name')
    ->get(['id', 'name']);

        return response()->json($sections);
    }

public function subjects(Request $request)
{
    $facultyId = $request->get('faculty_id');
    $semester  = $request->get('semester');
    $batch     = $request->get('batch');   // 1 = new, 2 = old (from your migration)

    if (!$facultyId || !$semester) {
        return response()->json([]);
    }

    // Allow UI to send either "NEW"/"OLD" or 1/2
    if (is_string($batch)) {
        $upper = strtoupper($batch);
        if ($upper === 'NEW') {
            $batch = 1;
        } elseif ($upper === 'OLD') {
            $batch = 2;
        }
    }

    $query = DB::table('faculty_semester_subjects as fss')
        ->join('subjects', 'fss.subject_id', '=', 'subjects.id')
        ->where('fss.faculty_id', $facultyId)
        ->where('fss.semester', $semester);

    if (!empty($batch)) {
        $query->where('fss.batch', $batch);
    }

    $subjects = $query
        ->orderBy('subjects.code')
        ->get([
            'subjects.id',
            'subjects.code',
            'subjects.name',
        ]);

    return response()->json($subjects);
}


    public function students(Request $request)
    {
        $facultyId = $request->get('faculty_id');
        $sectionId = $request->get('section_id');

        $query = Student::query();

        if ($facultyId) $query->where('faculty_id', $facultyId);
        if ($sectionId) $query->where('section_id', $sectionId);

        $students = $query->orderBy('symbol_no')
            ->get(['id', 'symbol_no', 'name']);

        return response()->json($students);
    }

    protected function defaultRange(string $mode, Carbon $today): array
    {
        switch ($mode) {
            case 'weekly':
                return [
                    $today->copy()->subDays(6)->toDateString(),
                    $today->toDateString()
                ];
            case 'monthly':
                return [
                    $today->copy()->firstOfMonth()->toDateString(),
                    $today->toDateString()
                ];
            case 'daily':
            default:
                return [
                    $today->toDateString(),
                    $today->toDateString()
                ];
        }
    }
    public function teachers(Request $request)
{
    $facultyId = $request->get('faculty_id');
    $sectionId = $request->get('section_id');
    $subjectId = $request->get('subject_id');

    // We assume "routines" table has teacher_id, faculty_id, section_id, subject_id
    $query = DB::table('routines')
        ->join('teachers', 'routines.teacher_id', '=', 'teachers.id')
        ->select('teachers.id', 'teachers.name')
        ->groupBy('teachers.id', 'teachers.name')
        ->orderBy('teachers.name');

    if ($facultyId) {
        $query->where('routines.faculty_id', $facultyId);
    }
    if ($sectionId) {
        $query->where('routines.section_id', $sectionId);
    }
    if ($subjectId) {
        $query->where('routines.subject_id', $subjectId);
    }

    $teachers = $query->get();

    return response()->json($teachers);
}

}
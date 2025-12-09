<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceAnalyticsController extends Controller
{
    /* ==============================
       MAIN DASHBOARD PAGE
       ============================== */
    public function index(Request $request)
    {
        $faculties = Faculty::orderBy('name')->get();

        // You can also load all teachers for the initial teacher dropdown if you want:
        $teachers  = DB::table('teachers')
            ->orderBy('name')
            ->get();

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

    /* ==============================
       JSON DATA FOR DASHBOARD
       ============================== */
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

            // Base attendance query (for global + trend)
            $baseAttendance = $this->buildAttendanceQuery(
                $from,
                $to,
                $facultyId,
                $teacherId,
                $studentId,
                $sectionId,
                $semester,
                $batch,
                $subjectId,
                $groupId
            );

            Log::info('Base query built');

            // ---------- Global statistics ----------
            $globalRaw = (clone $baseAttendance)
                ->selectRaw('
                    COUNT(*) as total_slots,
                    COUNT(DISTINCT attendances.student_id) as unique_students,
                    SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present_slots,
                    SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent_slots
                ')
                ->first();

            $totalSlots   = (int) ($globalRaw->total_slots ?? 0);
            $uniqueSt     = (int) ($globalRaw->unique_students ?? 0);
            $presentSlots = (int) ($globalRaw->present_slots ?? 0);
            $absentSlots  = (int) ($globalRaw->absent_slots ?? 0);

            $presentRate = $totalSlots > 0
                ? round(($presentSlots / $totalSlots) * 100, 1)
                : 0;

            $absentRate  = $totalSlots > 0
                ? round(($absentSlots / $totalSlots) * 100, 1)
                : 0;

            Log::info('Global stats retrieved', ['global' => $globalRaw]);

            // ---------- Trend by date ----------
            $trendByDate = (clone $baseAttendance)
                ->selectRaw('
                    attendances.date as day,
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent
                ')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            Log::info('Trend data retrieved', ['count' => $trendByDate->count()]);

            // ---------- Breakdowns ----------
            $byFaculty  = $this->getAttendanceByFaculty($from, $to, $facultyId, $sectionId, $semester, $subjectId, $groupId, $batch);
            $byTeacher  = $this->getAttendanceByTeacher($from, $to, $facultyId, $teacherId, $sectionId, $semester, $subjectId, $batch, $groupId);
            $bySubject  = $this->getAttendanceBySubject($from, $to, $facultyId, $sectionId, $semester, $subjectId, $batch, $groupId);
            $bySection  = $this->getAttendanceBySection($from, $to, $facultyId, $sectionId, $semester, $batch, $groupId);
            $byStudent  = $this->getAttendanceByStudent($from, $to, $facultyId, $studentId, $sectionId, $semester, $batch, $subjectId, $groupId);

            // ---------- Class taught/not_taught ----------
            $taughtStats = $this->getTaughtStatistics(
                $from,
                $to,
                $facultyId,
                $teacherId,
                $sectionId,
                $semester,
                $subjectId
            );

            // ---------- Contradictions ----------
            $contradictions = $this->findContradictions(
                $from,
                $to,
                $facultyId,
                $sectionId,
                $semester,
                $subjectId
            );

            // ---------- Student timeline (if specific student selected) ----------
            $studentTimeline = [];
            if ($studentId) {
                $studentTimeline = $this->getStudentTimeline(
                    $studentId,
                    $from,
                    $to,
                    $facultyId,
                    $sectionId,
                    $semester,
                    $subjectId
                );
            }

            // ---------- Subject cross-contrast (present in one, absent in another) ----------
            $subjectContrast = $this->getSubjectContrast(
                $from,
                $to,
                $facultyId,
                $sectionId,
                $semester,
                $batch
            );

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
                    'totalSlots'     => $totalSlots,
                    'uniqueStudents' => $uniqueSt,
                    'present'        => $presentSlots,
                    'absent'         => $absentSlots,
                    'presentRate'    => $presentRate,
                    'absentRate'     => $absentRate,
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
                'subjectContrast' => $subjectContrast,
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics data error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    /* ==============================
       BASE QUERY (NO DUPLICATES)
       ============================== */
    protected function buildAttendanceQuery(
        $from,
        $to,
        $facultyId,
        $teacherId,
        $studentId,
        $sectionId,
        $semester,
        $batch,
        $subjectId,
        $groupId
    ) {
        $query = DB::table('attendances')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($teacherId) {
            $query->where('attendances.teacher_id', $teacherId);
        }

        if ($studentId) {
            $query->where('attendances.student_id', $studentId);
        }

        // Join students when needed
        if ($facultyId || $sectionId || $batch || $groupId) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');

            if ($facultyId) {
                $query->where('students.faculty_id', $facultyId);
            }
            if ($sectionId) {
                $query->where('students.section_id', $sectionId);
            }
            if ($batch) {
                $query->where('students.batch', $batch);
            }
            if ($groupId) {
                $query->where('students.group_id', $groupId);
            }
        }

        // Join routines when needed
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

    /* ==============================
       BREAKDOWN HELPERS
       ============================== */

    protected function getAttendanceByFaculty(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $subjectId,
        $groupId,
        $batch
    ) {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('faculties', 'students.faculty_id', '=', 'faculties.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($facultyId) $query->where('faculties.id', $facultyId);
        if ($sectionId) $query->where('students.section_id', $sectionId);
        if ($groupId)  $query->where('students.group_id', $groupId);
        if ($batch)    $query->where('students.batch', $batch);

        if ($subjectId || $semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
            if ($semester)  $query->where('routines.semester', $semester);
        }

        return $query
            ->selectRaw('
                faculties.id   as faculty_id,
                faculties.code as faculty_code,
                faculties.name as faculty_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                ROUND(
                    (SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    1
                ) as present_rate
            ')
            ->groupBy('faculties.id', 'faculties.code', 'faculties.name')
            ->orderBy('faculties.code')
            ->get();
    }

    protected function getAttendanceByTeacher(
        $from,
        $to,
        $facultyId,
        $teacherId,
        $sectionId,
        $semester,
        $subjectId,
        $batch,
        $groupId
    ) {
        $query = DB::table('attendances')
            ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($teacherId) $query->where('teachers.id', $teacherId);

        if ($facultyId || $sectionId || $batch || $groupId) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');
            if ($facultyId) $query->where('students.faculty_id', $facultyId);
            if ($sectionId) $query->where('students.section_id', $sectionId);
            if ($batch)    $query->where('students.batch', $batch);
            if ($groupId)  $query->where('students.group_id', $groupId);
        }

        if ($subjectId || $semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
            if ($semester)  $query->where('routines.semester', $semester);
        }

        return $query
            ->selectRaw('
                teachers.id   as teacher_id,
                teachers.name as teacher_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                ROUND(
                    (SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    1
                ) as present_rate
            ')
            ->groupBy('teachers.id', 'teachers.name')
            ->orderBy('teachers.name')
            ->get();
    }

    protected function getAttendanceBySubject(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $subjectId,
        $batch,
        $groupId
    ) {
        $query = DB::table('attendances')
            ->join('routines', 'attendances.routine_id', '=', 'routines.id')
            ->join('subjects', 'routines.subject_id', '=', 'subjects.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($subjectId) $query->where('subjects.id', $subjectId);
        if ($semester)  $query->where('routines.semester', $semester);

        if ($facultyId || $sectionId || $batch || $groupId) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');
            if ($facultyId) $query->where('students.faculty_id', $facultyId);
            if ($sectionId) $query->where('students.section_id', $sectionId);
            if ($batch)    $query->where('students.batch', $batch);
            if ($groupId)  $query->where('students.group_id', $groupId);
        }

        return $query
            ->selectRaw('
                subjects.id   as subject_id,
                subjects.code as subject_code,
                subjects.name as subject_name,
                routines.semester,
                routines.type as class_type,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                COUNT(DISTINCT attendances.student_id) as unique_students,
                ROUND(
                    (SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    1
                ) as absent_rate
            ')
            ->groupBy('subjects.id', 'subjects.code', 'subjects.name', 'routines.semester', 'routines.type')
            ->orderBy('subjects.code')
            ->get();
    }

    protected function getAttendanceBySection(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $batch,
        $groupId
    ) {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($facultyId) $query->where('students.faculty_id', $facultyId);
        if ($sectionId) $query->where('sections.id', $sectionId);
        if ($batch)     $query->where('students.batch', $batch);
        if ($groupId)   $query->where('students.group_id', $groupId);

        if ($semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id')
                  ->where('routines.semester', $semester);
        }

        return $query
            ->selectRaw('
                sections.id   as section_id,
                sections.name as section_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                COUNT(DISTINCT students.id) as unique_students,
                ROUND(
                    (SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    1
                ) as present_rate
            ')
            ->groupBy('sections.id', 'sections.name')
            ->orderBy('sections.name')
            ->get();
    }

    protected function getAttendanceByStudent(
        $from,
        $to,
        $facultyId,
        $studentId,
        $sectionId,
        $semester,
        $batch,
        $subjectId,
        $groupId
    ) {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($studentId) $query->where('students.id', $studentId);
        if ($facultyId) $query->where('students.faculty_id', $facultyId);
        if ($sectionId) $query->where('students.section_id', $sectionId);
        if ($batch)     $query->where('students.batch', $batch);
        if ($groupId)   $query->where('students.group_id', $groupId);

        if ($subjectId || $semester) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
            if ($semester)  $query->where('routines.semester', $semester);
        }

        return $query
            ->selectRaw('
                students.id as student_id,
                students.symbol_no,
                students.name as student_name,
                sections.name as section_name,
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) as absent,
                ROUND(
                    (SUM(CASE WHEN attendances.status = "P" THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    1
                ) as present_rate,
                ROUND(
                    (SUM(CASE WHEN attendances.status = "A" THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    1
                ) as absent_rate
            ')
            ->groupBy('students.id', 'students.symbol_no', 'students.name', 'sections.name')
            ->orderBy('students.symbol_no')
            ->limit(100)
            ->get();
    }

    protected function getTaughtStatistics(
        $from,
        $to,
        $facultyId,
        $teacherId,
        $sectionId,
        $semester,
        $subjectId
    ) {
        $query = DB::table('routine_feedback as rf')
            ->join('routines as r', 'rf.routine_id', '=', 'r.id')
            ->whereBetween('rf.class_date', [$from, $to]);

        if ($teacherId) $query->where('r.teacher_id', $teacherId);
        if ($facultyId) $query->where('r.faculty_id', $facultyId);
        if ($sectionId) $query->where('r.section_id', $sectionId);
        if ($semester)  $query->where('r.semester', $semester);
        if ($subjectId) $query->where('r.subject_id', $subjectId);

        $result = $query
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN rf.status = "taught" THEN 1 ELSE 0 END) as taught,
                SUM(CASE WHEN rf.status = "not_taught" THEN 1 ELSE 0 END) as not_taught
            ')
            ->first();

        $total     = (int) ($result->total ?? 0);
        $taught    = (int) ($result->taught ?? 0);
        $notTaught = (int) ($result->not_taught ?? 0);

        $taughtRate = $total > 0
            ? round(($taught / $total) * 100, 1)
            : 0;

        return [
            'totalClasses' => $total,
            'taught'       => $taught,
            'notTaught'    => $notTaught,
            'taughtRate'   => $taughtRate,
        ];
    }

    protected function findContradictions(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $subjectId
    ) {
        $query = DB::table('routine_feedback as rf')
            ->join('routines as r', 'rf.routine_id', '=', 'r.id')
            ->leftJoin('subjects', 'r.subject_id', '=', 'subjects.id')
            ->leftJoin('teachers', 'r.teacher_id', '=', 'teachers.id')
            ->leftJoin('sections', 'r.section_id', '=', 'sections.id')
            ->leftJoin('attendances as a', function ($join) {
                $join->on('rf.routine_id', '=', 'a.routine_id')
                     ->on('rf.class_date', '=', 'a.date');
            })
            ->whereBetween('rf.class_date', [$from, $to])
            ->where('rf.status', 'taught');

        if ($facultyId) $query->where('r.faculty_id', $facultyId);
        if ($sectionId) $query->where('r.section_id', $sectionId);
        if ($semester)  $query->where('r.semester', $semester);
        if ($subjectId) $query->where('r.subject_id', $subjectId);

        return $query
            ->selectRaw('
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

    protected function getStudentTimeline(
        $studentId,
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $subjectId
    ) {
        $query = DB::table('attendances')
            ->where('student_id', $studentId)
            ->whereBetween('date', [$from, $to]);

        if ($facultyId || $sectionId || $semester || $subjectId) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');

            if ($facultyId) $query->where('routines.faculty_id', $facultyId);
            if ($sectionId) $query->where('routines.section_id', $sectionId);
            if ($semester)  $query->where('routines.semester', $semester);
            if ($subjectId) $query->where('routines.subject_id', $subjectId);
        }

        return $query
            ->selectRaw('
                date as day,
                SUM(CASE WHEN status = "P" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as absent,
                COUNT(*) as total
            ')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * Subject contrast:
     * Count cases where a student is ABSENT in Subject X
     * but PRESENT in at least one other subject on the same day.
     */
    protected function getSubjectContrast(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $batch
    ) {
        $query = DB::table('attendances as a_abs')
            ->join('routines as r_abs', 'a_abs.routine_id', '=', 'r_abs.id')
            ->join('subjects as s', 'r_abs.subject_id', '=', 's.id')
            ->join('attendances as a_pres', function ($join) {
                $join->on('a_abs.student_id', '=', 'a_pres.student_id')
                     ->on('a_abs.date', '=', 'a_pres.date');
            })
            ->join('routines as r_pres', 'a_pres.routine_id', '=', 'r_pres.id')
            ->whereBetween('a_abs.date', [$from, $to])
            ->where('a_abs.status', 'A')
            ->where('a_pres.status', 'P')
            ->whereColumn('r_abs.subject_id', '!=', 'r_pres.subject_id');

        if ($facultyId) $query->where('r_abs.faculty_id', $facultyId);
        if ($sectionId) $query->where('r_abs.section_id', $sectionId);
        if ($semester)  $query->where('r_abs.semester', $semester);

        if ($batch) {
            $query->join('students', 'a_abs.student_id', '=', 'students.id')
                  ->where('students.batch', $batch);
        }

        return $query
            ->selectRaw('
                s.id   as subject_id,
                s.code as subject_code,
                s.name as subject_name,
                COUNT(DISTINCT CONCAT(a_abs.student_id, "|", a_abs.date)) as mismatch_count
            ')
            ->groupBy('s.id', 's.code', 's.name')
            ->orderByDesc('mismatch_count')
            ->limit(20)
            ->get();
    }

    /* ==============================
       EXPORT CSV
       ============================== */
    public function export(Request $request)
    {
        $mode  = $request->get('mode', 'daily');
        $today = Carbon::today();
        $from  = $request->get('from');
        $to    = $request->get('to');

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
                'Symbol No', 'Student Name', 'Status',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /* ==============================
       SMALL JSON ENDPOINTS
       ============================== */

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
        $batch     = $request->get('batch'); // 1 = new, 2 = old, etc.

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
        $groupId   = $request->get('group_id');

        $query = Student::query();

        if ($facultyId) $query->where('faculty_id', $facultyId);
        if ($sectionId) $query->where('section_id', $sectionId);
        if ($groupId)   $query->where('group_id', $groupId);

        $students = $query
            ->orderBy('symbol_no')
            ->get(['id', 'symbol_no', 'name']);

        return response()->json($students);
    }

    public function teachers(Request $request)
    {
        $facultyId = $request->get('faculty_id');
        $sectionId = $request->get('section_id');
        $semester  = $request->get('semester');
        $subjectId = $request->get('subject_id');

        $query = DB::table('teachers')
            ->join('routines', 'routines.teacher_id', '=', 'teachers.id');

        if ($facultyId) $query->where('routines.faculty_id', $facultyId);
        if ($sectionId) $query->where('routines.section_id', $sectionId);
        if ($semester)  $query->where('routines.semester', $semester);
        if ($subjectId) $query->where('routines.subject_id', $subjectId);

        $teachers = $query
            ->groupBy('teachers.id', 'teachers.name')
            ->orderBy('teachers.name')
            ->get(['teachers.id', 'teachers.name']);

        return response()->json($teachers);
    }

    /* ==============================
       DATE RANGE HELPER
       ============================== */
    protected function defaultRange(string $mode, Carbon $today): array
    {
        switch ($mode) {
            case 'weekly':
                return [
                    $today->copy()->subDays(6)->toDateString(),
                    $today->toDateString(),
                ];
            case 'monthly':
                return [
                    $today->copy()->firstOfMonth()->toDateString(),
                    $today->toDateString(),
                ];
            case 'daily':
            default:
                return [
                    $today->toDateString(),
                    $today->toDateString(),
                ];
        }
    }
}

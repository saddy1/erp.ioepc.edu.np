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
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        // Faculties visible in dropdown
        $facultiesQuery = Faculty::orderBy('name');

        if ($admin && $admin->isDepartmentAdmin()) {
            if (!empty($managedFacultyIds)) {
                $facultiesQuery->whereIn('id', $managedFacultyIds);
            } else {
                // No faculties mapped => show none (safe)
                $facultiesQuery->whereRaw('0 = 1');
            }
        }

        $faculties = $facultiesQuery->get();

        // Sections (only from allowed faculties for HOD)
        $sectionsQuery = Section::orderBy('name');

        if ($admin && $admin->isDepartmentAdmin()) {
            if (!empty($managedFacultyIds)) {
                $sectionsQuery->whereIn('faculty_id', $managedFacultyIds);
            } else {
                $sectionsQuery->whereRaw('0 = 1');
            }
        }

        $sections = $sectionsQuery->get();

        // Teachers dropdown (only from allowed faculties for HOD)
        $teachersQuery = DB::table('teachers')->orderBy('name');

        if ($admin && $admin->isDepartmentAdmin()) {
            if (!empty($managedFacultyIds)) {
                $teachersQuery->whereIn('faculty_id', $managedFacultyIds);
            } else {
                $teachersQuery->whereRaw('0 = 1');
            }
        }

        $teachers = $teachersQuery->get();

        $mode  = $request->get('mode', 'daily');
        $today = Carbon::today();
        $from  = $request->get('from');
        $to    = $request->get('to');

        if (!$from || !$to) {
            [$from, $to] = $this->defaultRange($mode, $today);
        }

        return view('Backend.dashboard.index', [
            'faculties'   => $faculties,
            'sections'    => $sections,
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

            /** @var \App\Models\Admin|null $admin */
            $admin = $request->attributes->get('admin');
            $facultyScopeIds = ($admin && $admin->isDepartmentAdmin())
                ? $admin->managedFacultyIds()
                : [];

            $mode  = $request->get('mode', 'daily');
            $today = Carbon::today();
            $from  = $request->get('from');
            $to    = $request->get('to');

            if (!$from || !$to) {
                [$from, $to] = $this->defaultRange($mode, $today);
            }

            // Filters from UI
            $facultyId = $request->get('faculty_id');
            $teacherId = $request->get('teacher_id');
            $studentId = $request->get('student_id');
            $sectionId = $request->get('section_id');
            $semester  = $request->get('semester');
            $batch     = $request->get('batch');
            $subjectId = $request->get('subject_id');
            $groupId   = $request->get('group_id');

            // 🔐 If HOD: make sure selected faculty is within allowed faculties
            if ($admin && $admin->isDepartmentAdmin()) {
                if (!empty($facultyId) && !in_array($facultyId, $facultyScopeIds)) {
                    // Invalid faculty selected → ignore that filter
                    $facultyId = null;
                }
            }

            Log::info('Date range calculated', ['from' => $from, 'to' => $to]);

            // ---------- Base attendance query ----------
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
                $groupId,
                $facultyScopeIds   // 🔐 DEPT SCOPE
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

            // ---------- Breakdowns (all dept-scoped) ----------
            $byFaculty  = $this->getAttendanceByFaculty($from, $to, $facultyId, $sectionId, $semester, $subjectId, $groupId, $batch, $facultyScopeIds);
            $byTeacher  = $this->getAttendanceByTeacher($from, $to, $facultyId, $teacherId, $sectionId, $semester, $subjectId, $batch, $groupId, $facultyScopeIds);
            $bySubject  = $this->getAttendanceBySubject($from, $to, $facultyId, $sectionId, $semester, $subjectId, $batch, $groupId, $facultyScopeIds);
            $bySection  = $this->getAttendanceBySection($from, $to, $facultyId, $sectionId, $semester, $batch, $groupId, $facultyScopeIds);
            $byStudent  = $this->getAttendanceByStudent($from, $to, $facultyId, $studentId, $sectionId, $semester, $batch, $subjectId, $groupId, $facultyScopeIds);

            // ---------- Class taught/not_taught ----------
            $taughtStats = $this->getTaughtStatistics(
                $from,
                $to,
                $facultyId,
                $teacherId,
                $sectionId,
                $semester,
                $subjectId,
                $facultyScopeIds  // 🔐
            );

            // ---------- Contradictions ----------
            $contradictions = $this->findContradictions(
                $from,
                $to,
                $facultyId,
                $sectionId,
                $semester,
                $subjectId,
                $facultyScopeIds  // 🔐
            );

            // ---------- Not taught details ----------
            $notTaughtDetails = $this->getNotTaughtDetails(
                $from,
                $to,
                $facultyId,
                $teacherId,
                $sectionId,
                $semester,
                $subjectId,
                $facultyScopeIds  // 🔐
            );

            // ---------- Student timeline ----------
            $studentTimeline = [];
            if ($studentId) {
                $studentTimeline = $this->getStudentTimeline(
                    $studentId,
                    $from,
                    $to,
                    $facultyId,
                    $sectionId,
                    $semester,
                    $subjectId,
                    $facultyScopeIds  // 🔐
                );
            }

            // ---------- Subject cross-contrast ----------
            $subjectContrast = $this->getSubjectContrast(
                $from,
                $to,
                $facultyId,
                $sectionId,
                $semester,
                $batch,
                $facultyScopeIds  // 🔐
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
                'trendByDate'      => $trendByDate,
                'byFaculty'        => $byFaculty,
                'byTeacher'        => $byTeacher,
                'bySubject'        => $bySubject,
                'bySection'        => $bySection,
                'byStudent'        => $byStudent,
                'taughtStats'      => $taughtStats,
                'contradictions'   => $contradictions,
                'studentTimeline'  => $studentTimeline,
                'subjectContrast'  => $subjectContrast,
                'notTaughtDetails' => $notTaughtDetails,
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
        $groupId,
        array $facultyScopeIds = []   // 🔐 NEW
    ) {
        $query = DB::table('attendances')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($teacherId) {
            $query->where('attendances.teacher_id', $teacherId);
        }

        if ($studentId) {
            $query->where('attendances.student_id', $studentId);
        }

        // Need students join if we filter by student faculty/section/batch/group
        // or dept scope (HOD)
        $needsStudentsJoin = $facultyId || $sectionId || $batch || $groupId || !empty($facultyScopeIds);

        if ($needsStudentsJoin) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');

            if ($facultyId) {
                $query->where('students.faculty_id', $facultyId);
            }

            if (!empty($facultyScopeIds)) {
                $query->whereIn('students.faculty_id', $facultyScopeIds);
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
        $batch,
        array $facultyScopeIds = []  // 🔐
    ) {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('faculties', 'students.faculty_id', '=', 'faculties.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if (!empty($facultyScopeIds)) {
            $query->whereIn('students.faculty_id', $facultyScopeIds);
        }

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
        $groupId,
        array $facultyScopeIds = []  // 🔐
    ) {
        $query = DB::table('attendances')
            ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($teacherId) $query->where('teachers.id', $teacherId);

        if ($facultyId || $sectionId || $batch || $groupId || !empty($facultyScopeIds)) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');

            if (!empty($facultyScopeIds)) {
                $query->whereIn('students.faculty_id', $facultyScopeIds);
            }

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
        $groupId,
        array $facultyScopeIds = []  // 🔐
    ) {
        $query = DB::table('attendances')
            ->join('routines', 'attendances.routine_id', '=', 'routines.id')
            ->join('subjects', 'routines.subject_id', '=', 'subjects.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if ($subjectId) $query->where('subjects.id', $subjectId);
        if ($semester)  $query->where('routines.semester', $semester);

        if ($facultyId || $sectionId || $batch || $groupId || !empty($facultyScopeIds)) {
            $query->join('students', 'attendances.student_id', '=', 'students.id');

            if (!empty($facultyScopeIds)) {
                $query->whereIn('students.faculty_id', $facultyScopeIds);
            }

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
        $groupId,
        array $facultyScopeIds = []  // 🔐
    ) {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if (!empty($facultyScopeIds)) {
            $query->whereIn('students.faculty_id', $facultyScopeIds);
        }

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
        $groupId,
        array $facultyScopeIds = []  // 🔐
    ) {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if (!empty($facultyScopeIds)) {
            $query->whereIn('students.faculty_id', $facultyScopeIds);
        }

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

    /* ==============================
       TAUGHT / NOT TAUGHT
       ============================== */
    protected function getTaughtStatistics(
        $from,
        $to,
        $facultyId,
        $teacherId,
        $sectionId,
        $semester,
        $subjectId,
        array $facultyScopeIds = []   // 🔐
    ) {
        $query = DB::table('routine_feedback as rf')
            ->join('routines as r', 'rf.routine_id', '=', 'r.id')
            ->whereBetween('rf.class_date', [$from, $to]);

        // Dept scope
        if (!empty($facultyScopeIds)) {
            $query->whereIn('r.faculty_id', $facultyScopeIds);
        }

        // Apply filters
        if ($teacherId) $query->where('r.teacher_id', $teacherId);
        if ($facultyId) $query->where('r.faculty_id', $facultyId);
        if ($sectionId) $query->where('r.section_id', $sectionId);
        if ($semester)  $query->where('r.semester', $semester);
        if ($subjectId) $query->where('r.subject_id', $subjectId);

        // Unique session signature
        $sessionSignature = "CONCAT(
            rf.class_date, '_', 
            r.subject_id, '_', 
            r.section_id, '_', 
            COALESCE(r.`group`, 'ALL')
        )";

        $result = $query
            ->selectRaw("
                COUNT(DISTINCT $sessionSignature) as total,
                COUNT(DISTINCT CASE WHEN rf.status = 'taught' THEN $sessionSignature END) as taught,
                COUNT(DISTINCT CASE WHEN rf.status = 'not_taught' THEN $sessionSignature END) as not_taught
            ")
            ->first();

        $total     = (int) ($result->total ?? 0);
        $taught    = (int) ($result->taught ?? 0);
        $notTaught = (int) ($result->not_taught ?? 0);

        $taughtRate = $total > 0 ? round(($taught / $total) * 100, 1) : 0;

        return [
            'totalClasses' => $total,
            'taught'       => $taught,
            'notTaught'    => $notTaught,
            'taughtRate'   => $taughtRate,
        ];
    }

    /* ==============================
       CONTRADICTIONS
       ============================== */
    protected function findContradictions(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $subjectId,
        array $facultyScopeIds = []   // 🔐
    ) {
        // Common attendance aggregate: per routine + date
        $attendanceAgg = DB::table('attendances')
            ->selectRaw('routine_id, date, COUNT(*) as attendance_count')
            ->whereBetween('date', [$from, $to])
            ->groupBy('routine_id', 'date');

        // 1) Rows where CR feedback exists
        $q1 = DB::table('routine_feedback as rf')
            ->join('routines as r', 'rf.routine_id', '=', 'r.id')
            ->leftJoinSub($attendanceAgg, 'ac', function ($join) {
                $join->on('ac.routine_id', '=', 'r.id')
                     ->on('ac.date', '=', 'rf.class_date');
            })
            ->leftJoin('subjects', 'r.subject_id', '=', 'subjects.id')
            ->leftJoin('teachers', 'r.teacher_id', '=', 'teachers.id')
            ->leftJoin('sections', 'r.section_id', '=', 'sections.id')
            ->leftJoin('faculties', 'r.faculty_id', '=', 'faculties.id')
            ->whereBetween('rf.class_date', [$from, $to]);

        if (!empty($facultyScopeIds)) {
            $q1->whereIn('r.faculty_id', $facultyScopeIds);
        }

        if ($facultyId) $q1->where('r.faculty_id', $facultyId);
        if ($sectionId) $q1->where('r.section_id', $sectionId);
        if ($semester)  $q1->where('r.semester', $semester);
        if ($subjectId) $q1->where('r.subject_id', $subjectId);

        $q1 = $q1->selectRaw('
            rf.routine_id,
            rf.class_date,
            rf.status as feedback_status,

            subjects.code as subject_code,
            subjects.name as subject_name,
            teachers.name as teacher_name,
            sections.name as section_name,
            faculties.code as faculty_code,
            faculties.name as faculty_name,

            r.semester,
            COALESCE(r.`group`, "ALL") as group_name,
            CASE 
                WHEN COALESCE(r.`group`, "ALL") = "ALL" 
                    THEN "Theory"
                ELSE CONCAT("Practical – Group ", r.`group`)
            END as class_label,
            COALESCE(ac.attendance_count, 0) as attendance_count
        ')->get();

        // 2) Rows where attendance exists but NO CR feedback
        $q2 = DB::table('routines as r')
            ->joinSub($attendanceAgg, 'ac', function ($join) {
                $join->on('ac.routine_id', '=', 'r.id');
            })
            ->leftJoin('routine_feedback as rf', function ($join) {
                $join->on('rf.routine_id', '=', 'r.id')
                     ->on('rf.class_date', '=', 'ac.date');
            })
            ->leftJoin('subjects', 'r.subject_id', '=', 'subjects.id')
            ->leftJoin('teachers', 'r.teacher_id', '=', 'teachers.id')
            ->leftJoin('sections', 'r.section_id', '=', 'sections.id')
            ->leftJoin('faculties', 'r.faculty_id', '=', 'faculties.id')
            ->whereBetween('ac.date', [$from, $to])
            ->whereNull('rf.id'); // attendance but no CR feedback

        if (!empty($facultyScopeIds)) {
            $q2->whereIn('r.faculty_id', $facultyScopeIds);
        }

        if ($facultyId) $q2->where('r.faculty_id', $facultyId);
        if ($sectionId) $q2->where('r.section_id', $sectionId);
        if ($semester)  $q2->where('r.semester', $semester);
        if ($subjectId) $q2->where('r.subject_id', $subjectId);

        $q2 = $q2->selectRaw('
            r.id as routine_id,
            ac.date as class_date,
            NULL as feedback_status,

            subjects.code as subject_code,
            subjects.name as subject_name,
            teachers.name as teacher_name,
            sections.name as section_name,
            faculties.code as faculty_code,
            faculties.name as faculty_name,

            r.semester,
            COALESCE(r.`group`, "ALL") as group_name,
            CASE 
                WHEN COALESCE(r.`group`, "ALL") = "ALL" 
                    THEN "Theory"
                ELSE CONCAT("Practical – Group ", r.`group`)
            END as class_label,
            ac.attendance_count as attendance_count
        ')->get();

        // Merge both sets
        $classes = $q1->concat($q2);

        // Determine issue type
        foreach ($classes as $c) {
            $att = (int) ($c->attendance_count ?? 0);
            $status = $c->feedback_status;

            if ($status === 'taught' && $att == 0) {
                $c->issue_type = 'Marked "taught" but no attendance recorded';
            } elseif ($status === 'taught' && $att > 0 && $att < 5) {
                $c->issue_type = 'Marked "taught" but very low attendance';
            } elseif ($status === 'not_taught' && $att > 0) {
                $c->issue_type = 'Marked "not taught" but attendance exists';
            } elseif (is_null($status) && $att > 0) {
                $c->issue_type = 'Attendance recorded but CR/VCR did not set taught/not_taught';
            }
        }

        // Collapse duplicates
        $contradictions = collect($classes)
            ->filter(fn ($c) => isset($c->issue_type));

        $grouped = $contradictions->groupBy(function ($c) {
            return implode('|', [
                $c->class_date,
                $c->subject_code,
                $c->teacher_name,
                $c->section_name,
                $c->semester,
                $c->group_name,
            ]);
        });

        $collapsed = $grouped->map(function ($items) {
            $base = $items->first();
            $base->attendance_count = $items->sum('attendance_count');
            $base->issue_type = $items->pluck('issue_type')->unique()->implode(' / ');
            return $base;
        });

        return $collapsed
            ->values()
            ->take(150);
    }

    /* ==============================
       NOT TAUGHT DETAILS
       ============================== */
    protected function getNotTaughtDetails(
        $from,
        $to,
        $facultyId,
        $teacherId,
        $sectionId,
        $semester,
        $subjectId,
        array $facultyScopeIds = []   // 🔐
    ) {
        $query = DB::table('routine_feedback as rf')
            ->join('routines as r', 'rf.routine_id', '=', 'r.id')
            ->leftJoin('subjects', 'r.subject_id', '=', 'subjects.id')
            ->leftJoin('teachers', 'r.teacher_id', '=', 'teachers.id')
            ->leftJoin('faculties', 'r.faculty_id', '=', 'faculties.id')
            ->leftJoin('sections', 'r.section_id', '=', 'sections.id')
            ->whereBetween('rf.class_date', [$from, $to])
            ->where('rf.status', 'not_taught');

        if (!empty($facultyScopeIds)) {
            $query->whereIn('r.faculty_id', $facultyScopeIds);
        }

        if ($facultyId) $query->where('r.faculty_id', $facultyId);
        if ($teacherId) $query->where('r.teacher_id', $teacherId);
        if ($sectionId) $query->where('r.section_id', $sectionId);
        if ($semester)  $query->where('r.semester', $semester);
        if ($subjectId) $query->where('r.subject_id', $subjectId);

        return $query
            ->selectRaw('
                rf.class_date,
                faculties.code  as faculty_code,
                faculties.name  as faculty_name,
                sections.name   as section_name,
                r.semester,
                subjects.code   as subject_code,
                subjects.name   as subject_name,
                teachers.name   as teacher_name,
                COALESCE(r.`group`, "ALL") as group_name,
                CASE 
                    WHEN COALESCE(r.`group`, "ALL") = "ALL" 
                        THEN "Theory"
                    ELSE CONCAT("Practical – Group ", COALESCE(r.`group`, ""))
                END as class_label
            ')
            ->groupBy(
                'rf.class_date',
                'faculties.code',
                'faculties.name',
                'sections.name',
                'r.semester',
                'subjects.code',
                'subjects.name',
                'teachers.name',
                DB::raw('r.`group`')
            )
            ->orderBy('rf.class_date', 'desc')
            ->limit(150)
            ->get();
    }

    /* ==============================
       STUDENT TIMELINE
       ============================== */
    protected function getStudentTimeline(
        $studentId,
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $subjectId,
        array $facultyScopeIds = []   // 🔐
    ) {
        $query = DB::table('attendances')
            ->where('student_id', $studentId)
            ->whereBetween('date', [$from, $to]);

        if ($facultyId || $sectionId || $semester || $subjectId || !empty($facultyScopeIds)) {
            $query->join('routines', 'attendances.routine_id', '=', 'routines.id');

            if (!empty($facultyScopeIds)) {
                $query->whereIn('routines.faculty_id', $facultyScopeIds);
            }

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

    /* ==============================
       CROSS-SUBJECT CONTRAST
       ============================== */
    protected function getSubjectContrast(
        $from,
        $to,
        $facultyId,
        $sectionId,
        $semester,
        $batch,
        array $facultyScopeIds = []   // 🔐
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
            ->where('a_abs.status', 'A')      // Absent here
            ->where('a_pres.status', 'P')     // Present somewhere else
            ->whereColumn('r_abs.subject_id', '!=', 'r_pres.subject_id');

        // Dept scope via students
        if ($facultyId || !empty($facultyScopeIds) || $sectionId || $batch) {
            $query->join('students', 'a_abs.student_id', '=', 'students.id');

            if (!empty($facultyScopeIds)) {
                $query->whereIn('students.faculty_id', $facultyScopeIds);
            }

            if ($facultyId) $query->where('students.faculty_id', $facultyId);
            if ($sectionId) $query->where('students.section_id', $sectionId);
            if ($batch)     $query->where('students.batch', $batch);
        }

        if ($semester) $query->where('r_abs.semester', $semester);

        return $query
            ->selectRaw('
                s.code as subject_code,
                s.name as subject_name,
                COUNT(DISTINCT a_abs.student_id) as skipping_students,
                COUNT(*) as total_skip_instances
            ')
            ->groupBy('s.id', 's.code', 's.name')
            ->orderByDesc('total_skip_instances')
            ->limit(10)
            ->get();
    }

    /* ==============================
       HELPER: Default Date Range
       ============================== */
    protected function defaultRange($mode, $today)
    {
        $to = $today->format('Y-m-d');

        switch ($mode) {
            case 'weekly':
                $from = $today->copy()->startOfWeek()->format('Y-m-d');
                break;
            case 'monthly':
                $from = $today->copy()->startOfMonth()->format('Y-m-d');
                break;
            case 'daily':
            default:
                $from = $today->format('Y-m-d');
                break;
        }

        return [$from, $to];
    }

    /* ==============================
       EXPORT CSV (also HOD-scoped)
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

        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $facultyScopeIds = ($admin && $admin->isDepartmentAdmin())
            ? $admin->managedFacultyIds()
            : [];

        $rowsQuery = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
            ->join('routines', 'attendances.routine_id', '=', 'routines.id')
            ->leftJoin('subjects', 'routines.subject_id', '=', 'subjects.id')
            ->leftJoin('faculties', 'students.faculty_id', '=', 'faculties.id')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->whereBetween('attendances.date', [$from, $to]);

        if (!empty($facultyScopeIds)) {
            $rowsQuery->whereIn('students.faculty_id', $facultyScopeIds);
        }

        $rows = $rowsQuery
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
       SMALL JSON ENDPOINTS (HOD-safe)
       ============================== */

    public function sections(Request $request)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        $facultyId = $request->get('faculty_id');

        if ($admin && $admin->isDepartmentAdmin()) {
            // HOD: limit to dept faculties
            if (!empty($facultyId) && !in_array($facultyId, $managedFacultyIds)) {
                return response()->json([]); // not allowed
            }

            $query = Section::orderBy('name');

            if (!empty($facultyId)) {
                $query->where('faculty_id', $facultyId);
            } else {
                $query->whereIn('faculty_id', $managedFacultyIds);
            }
        } else {
            // Super admin: original behaviour
            if (!$facultyId) {
                return response()->json([]);
            }

            $query = Section::where('faculty_id', $facultyId)
                ->orderBy('name');
        }

        $sections = $query->get(['id', 'name']);

        return response()->json($sections);
    }

    public function subjects(Request $request)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        $facultyId = $request->get('faculty_id');
        $semester  = $request->get('semester');
        $batch     = $request->get('batch'); // NEW/OLD or 1/2

        if (!$facultyId || !$semester) {
            return response()->json([]);
        }

        // HOD protection
        if ($admin && $admin->isDepartmentAdmin() && !in_array($facultyId, $managedFacultyIds)) {
            return response()->json([]);
        }

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
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        $facultyId = $request->get('faculty_id');
        $sectionId = $request->get('section_id');
        $groupId   = $request->get('group_id');

        $query = Student::query();

        if ($admin && $admin->isDepartmentAdmin()) {
            if (!empty($managedFacultyIds)) {
                $query->whereIn('faculty_id', $managedFacultyIds);
            } else {
                // no faculties => nothing
                return response()->json([]);
            }

            if ($facultyId && in_array($facultyId, $managedFacultyIds)) {
                $query->where('faculty_id', $facultyId);
            }
        } else {
            // super admin
            if ($facultyId) $query->where('faculty_id', $facultyId);
        }

        if ($sectionId) $query->where('section_id', $sectionId);
        if ($groupId)   $query->where('group_id', $groupId);

        $students = $query
            ->orderBy('symbol_no')
            ->get(['id', 'symbol_no', 'name']);

        return response()->json($students);
    }

    public function teachers(Request $request)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        $facultyId = $request->get('faculty_id');
        $sectionId = $request->get('section_id');
        $semester  = $request->get('semester');
        $subjectId = $request->get('subject_id');

        $query = DB::table('teachers')
            ->join('routines', 'routines.teacher_id', '=', 'teachers.id');

        if ($admin && $admin->isDepartmentAdmin()) {
            if (!empty($managedFacultyIds)) {
                $query->whereIn('routines.faculty_id', $managedFacultyIds);
            } else {
                return response()->json([]);
            }

            if ($facultyId && in_array($facultyId, $managedFacultyIds)) {
                $query->where('routines.faculty_id', $facultyId);
            }
        } else {
            if ($facultyId) $query->where('routines.faculty_id', $facultyId);
        }

        if ($sectionId) $query->where('routines.section_id', $sectionId);
        if ($semester)  $query->where('routines.semester', $semester);
        if ($subjectId) $query->where('routines.subject_id', $subjectId);

        $teachers = $query
            ->groupBy('teachers.id', 'teachers.name')
            ->orderBy('teachers.name')
            ->get(['teachers.id', 'teachers.name']);

        return response()->json($teachers);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    Exam,
    Faculty,
    ExamRegistration
};

class StudentController extends Controller
{
    public function index(Request $r)
    {
        $examId    = $r->query('exam_id');
        $semester  = $r->query('semester');
        $batch     = $r->query('batch');
        $facultyId = $r->query('faculty_id');

        // Exams for filter
        $exams = Exam::orderByDesc('id')
            ->get(['id', 'exam_title', 'semester', 'batch']);

        // Faculties for filter + grouping
        $faculties = Faculty::orderBy('code')->get();

        // Base query: registrations + related data
        $q = ExamRegistration::with([
                'student',              // Student (campus_roll_no, name, etc.)
                'faculty',              // Faculty (code, name)
                'exam',                 // Exam (title, etc.)
                'subjects.fss.subject', // ExamRegistrationSubject → FacultySemesterSubject → Subject (for name + practical)
            ])
            ->when($examId,   fn($q) => $q->where('exam_id', $examId))
            ->when($semester, fn($q) => $q->where('semester', $semester))
            ->when($batch,    fn($q) => $q->where('batch', $batch))
            ->when($facultyId,fn($q) => $q->where('faculty_id', $facultyId))
            ->orderBy('faculty_id')
            ->orderBy('exam_roll_no');

        // Paginate so page does not explode
        $registrations = $q->paginate(100)->withQueryString();

        $collection = $registrations->getCollection(); // current page data

        // Group by faculty for collapsible cards
        $byFaculty = $collection->groupBy('faculty_id');

        // Total students (all filtered, not just this page)
        $totalStudents = $registrations->total();

        // Subject-wise student counts (current page only) + names
        $subjectCounts = [];
        $subjectNames  = []; // ['ENSH151' => 'Engineering Mathematics II', ...]

        foreach ($collection as $reg) {
            foreach ($reg->subjects as $sub) {
                if (!($sub->th_taking || $sub->p_taking)) {
                    continue;
                }

                $code = $sub->subject_code;
                if (!$code) {
                    continue;
                }

                $subjectCounts[$code] = ($subjectCounts[$code] ?? 0) + 1;

                // Try to capture subject name from master subjects via relation
                if (!isset($subjectNames[$code])) {
                    $subjectNames[$code] = optional(optional($sub->fss)->subject)->name;
                }
            }
        }

        return view('Backend.admin.exam_import.index', compact(
            'registrations',
            'byFaculty',
            'faculties',
            'exams',
            'subjectCounts',
            'subjectNames',
            'totalStudents',
            'examId',
            'semester',
            'batch',
            'facultyId'
        ));
    }
}

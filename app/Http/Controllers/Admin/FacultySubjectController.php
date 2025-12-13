<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\FacultySemesterSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacultySubjectController extends Controller
{
    public function index(Request $r)
    {
        $semester = (int) $r->query('semester', 1);
        $batch    = (int) $r->query('batch', 0); // 0 = not chosen

        /** @var \App\Models\Admin|null $admin */
        $admin = $r->attributes->get('admin');   // from EnsureAdmin middleware

        // Base query for faculties
        $facQuery = Faculty::orderBy('code')
            ->with(['semesterSubjects' => function ($q) use ($semester, $batch) {
                $q->where('semester', $semester);
                if ($batch) {
                    $q->where('batch', $batch);
                }
                $q->orderBy('subject_code');
            }]);

        // 🔐 If HOD / department admin → show only their managed faculties
        if ($admin && $admin->isDepartmentAdmin()) {
            $managedFacultyIds = $admin->managedFacultyIds();

            if (empty($managedFacultyIds)) {
                $faculties = collect(); // nothing to show
            } else {
                $faculties = $facQuery->whereIn('id', $managedFacultyIds)->get();
            }
        } else {
            // Super Admin or other global admin can see all
            $faculties = $facQuery->get();
        }

        // Master SUBJECTS (for auto-fill + has_practical)
        $existingSubjects = Subject::query()
            ->select('code', 'name', 'has_practical')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function ($s) {
                // normalize code: remove spaces + uppercase
                $cleanCode = strtoupper(preg_replace('/\s+/', '', $s->code ?? ''));
                return [
                    $cleanCode => [
                        'name'          => $s->name,
                        'has_practical' => (bool) $s->has_practical,
                    ],
                ];
            });

        return view('Backend.admin.faculty_subjects.index_semester', compact(
            'faculties',
            'semester',
            'batch',
            'existingSubjects'
        ));
    }

    public function store(Request $r)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $r->attributes->get('admin');

        $data = $r->validate([
            'faculty_id'    => 'required|exists:faculties,id',
            'semester'      => 'required|integer|min:1|max:12',
            'batch'         => 'required|integer|in:1,2',
            'subject_code'  => 'required|string|max:80',
            'subject_name'  => 'required|string|max:191',
            'has_practical' => 'nullable|boolean',
        ]);

        // 🔐 Permission check:
        // - Super admin: can touch any faculty
        // - Dept admin (HOD): only faculties in their department
        if ($admin && !$admin->is_super_admin && $admin->isDepartmentAdmin()) {
            $allowed = $admin->managedFacultyIds();   // array of faculty_ids
            if (!in_array((int)$data['faculty_id'], $allowed, true)) {
                abort(403, 'You can only manage subjects for your department faculties.');
            }
        }

        $code = preg_replace('/\s+/', '', strtoupper($data['subject_code']));

        // 1) Upsert master subject
        $subject = Subject::updateOrCreate(
            ['code' => $code],
            [
                'name'          => $data['subject_name'],
                'has_practical' => $r->boolean('has_practical'),
            ]
        );

        // 2) Bind to faculty + sem + batch
        FacultySemesterSubject::firstOrCreate([
            'faculty_id'   => $data['faculty_id'],
            'semester'     => $data['semester'],
            'batch'        => $data['batch'],
            'subject_id'   => $subject->id,
            'subject_code' => $subject->code,
        ]);

        return back()->with('ok', 'Subject added.');
    }

    public function update(Request $r, FacultySemesterSubject $subject)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $r->attributes->get('admin');

        // 🔐 Permission check on existing row’s faculty_id
        if ($admin && !$admin->is_super_admin && $admin->isDepartmentAdmin()) {
            $allowed = $admin->managedFacultyIds();
            if (!in_array((int)$subject->faculty_id, $allowed, true)) {
                abort(403, 'You can only manage subjects for your department faculties.');
            }
        }

        $data = $r->validate([
            'subject_code'  => 'required|string|max:80',
            'subject_name'  => 'required|string|max:191',
            'has_practical' => 'nullable|boolean',
        ]);

        $code = preg_replace('/\s+/', '', strtoupper($data['subject_code']));

        // Get or create master subject
        $master = $subject->subject; // current subject (may be shared with others)

        if ($master && $master->code === $code) {
            // Update existing master subject
            $master->update([
                'name'          => $data['subject_name'],
                'has_practical' => $r->boolean('has_practical'),
            ]);
        } else {
            // Different code → use/update another master record
            $master = Subject::updateOrCreate(
                ['code' => $code],
                [
                    'name'          => $data['subject_name'],
                    'has_practical' => $r->boolean('has_practical'),
                ]
            );
        }

        // Update binding row
        $subject->update([
            'subject_id'   => $master->id,
            'subject_code' => $master->code,
        ]);

        return back()->with('ok', 'Subject updated.');
    }

    public function destroy(Request $r, FacultySemesterSubject $subject)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $r->attributes->get('admin');

        // 🔐 Permission check on existing row’s faculty_id
        if ($admin && !$admin->is_super_admin && $admin->isDepartmentAdmin()) {
            $allowed = $admin->managedFacultyIds();
            if (!in_array((int)$subject->faculty_id, $allowed, true)) {
                abort(403, 'You can only manage subjects for your department faculties.');
            }
        }

        $subject->delete();
        return back()->with('ok', 'Subject deleted.');
    }
}

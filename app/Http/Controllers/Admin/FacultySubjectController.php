<?php
// app/Http/Controllers/Admin/FacultySubjectController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\FacultySemesterSubject;
use Illuminate\Http\Request;
use App\Models\Subject;

use Illuminate\Support\Facades\DB;

class FacultySubjectController extends Controller
{
public function index(Request $r)
{
    $semester = (int) $r->query('semester', 1);
    $batch    = (int) $r->query('batch', 0); // 0 = not chosen

    $faculties = Faculty::orderBy('code')
        ->with(['semesterSubjects' => function ($q) use ($semester, $batch) {
            $q->where('semester', $semester);
            if ($batch) $q->where('batch', $batch);
            $q->orderBy('subject_code');
        }])->get();

    // Now from SUBJECTS table (master)
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
    $data = $r->validate([
        'faculty_id'    => 'required|exists:faculties,id',
        'semester'      => 'required|integer|min:1|max:12',
        'batch'         => 'required|integer|in:1,2',
        'subject_code'  => 'required|string|max:80',
        'subject_name'  => 'required|string|max:191',
        'has_practical' => 'nullable|boolean',
    ]);

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

public function destroy(FacultySemesterSubject $subject)
{
    $subject->delete();
    return back()->with('ok', 'Subject deleted.');
}

}

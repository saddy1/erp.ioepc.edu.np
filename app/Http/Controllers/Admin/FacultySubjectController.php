<?php
// app/Http/Controllers/Admin/FacultySubjectController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\FacultySemesterSubject;
use Illuminate\Http\Request;
use Illumanate\Support\Facades\DB;


class FacultySubjectController extends Controller
{
public function index(Request $r){
  $semester = (int) $r->query('semester', 1);
  $batch    = (int) $r->query('batch', 0); // 0 = not chosen

  $faculties = Faculty::orderBy('code')
    ->with(['semesterSubjects' => function($q) use ($semester, $batch) {
      $q->where('semester', $semester);
      if ($batch) $q->where('batch', $batch);
      $q->orderBy('subject_code');
    }])->get();

  // Get all unique subject codes and names for auto-fill
  $existingSubjects = \DB::table('faculty_semester_subjects')
    ->select('subject_code', 'subject_name')
    ->whereNotNull('subject_code')
    ->whereNotNull('subject_name')
    ->groupBy('subject_code', 'subject_name')
    ->orderBy('subject_code')
    ->get()
    ->groupBy('subject_code')
    ->map(function($group) {
      return $group->first()->subject_name; // Take first name for each code
    });

  return view('Backend.admin.faculty_subjects.index_semester', compact('faculties','semester','batch','existingSubjects'));
}
public function store(Request $r){
  $data = $r->validate([
    'faculty_id'   => 'required|exists:faculties,id',
    'semester'     => 'required|integer|min:1|max:12',
    'batch'        => 'required|integer|in:1,2',
    'subject_code' => 'required|string|max:80',
    'subject_name' => 'required|string|max:191',
  ]);
  FacultySemesterSubject::firstOrCreate($data);
  return back()->with('ok','Subject added.');
}

    public function update(Request $r, FacultySemesterSubject $subject)
    {
        $data = $r->validate([
            'subject_code' => 'required|string|max:80',
            'subject_name' => 'required|string|max:191',
        ]);

        $subject->update($data);

        return back()->with('ok', 'Subject updated.');
    }

    public function destroy(FacultySemesterSubject $subject)
    {
        $subject->delete();

        return back()->with('ok', 'Subject deleted.');
    }
}

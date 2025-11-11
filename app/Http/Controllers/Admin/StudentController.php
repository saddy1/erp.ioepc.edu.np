<?php
// app/Http/Controllers/Admin/StudentController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, Faculty};
use Illuminate\Http\Request;

class StudentController extends Controller
{
  public function index(Request $r){
    $q = Student::with('faculty')->orderBy('semester')->orderBy('symbol_no');
    if ($r->filled('faculty_id')) $q->where('faculty_id', $r->integer('faculty_id'));
    if ($r->filled('semester')) $q->where('semester', $r->integer('semester'));
    if ($r->filled('search')) {
      $s = $r->string('search');
      $q->where(function($w) use($s){
        $w->where('name','like',"%$s%")->orWhere('symbol_no','like',"%$s%");
      });
    }
    $students = $q->paginate(25)->withQueryString();
    $faculties = Faculty::orderBy('code')->get();
    return view('Backend.admin.students.index', compact('students','faculties'));
  }

  public function create(){
    $faculties = Faculty::orderBy('code')->get();
    return view('Backend.admin.students.create', compact('faculties'));
  }

  public function store(Request $r){
    $data = $r->validate([
      'name' => ['required','string','max:120'],
      'symbol_no' => ['required','string','max:50','unique:students,symbol_no'],
      'faculty_id' => ['required','exists:faculties,id'],
      'semester' => ['required','integer','min:1','max:12'],
    ]);
    Student::create($data);
    return redirect()->route('students.index')->with('ok','Student added.');
  }

  public function edit(Student $student){
    $faculties = Faculty::orderBy('code')->get();
    return view('Backend.admin.students.edit', compact('student','faculties'));
  }

  public function update(Request $r, Student $student){
    $data = $r->validate([
      'name' => ['required','string','max:120'],
      'symbol_no' => ['required','string','max:50',"unique:students,symbol_no,{$student->id}"],
      'faculty_id' => ['required','exists:faculties,id'],
      'semester' => ['required','integer','min:1','max:12'],
    ]);
    $student->update($data);
    return redirect()->route('students.index')->with('ok','Student updated.');
  }

  public function destroy(Student $student){
    $student->delete();
    return back()->with('ok','Student deleted.');
  }
}

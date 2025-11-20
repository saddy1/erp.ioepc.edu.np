<?php
// app/Http/Controllers/Admin/FacultyController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
  public function index(){
    $faculties = Faculty::codeOrder()->paginate(20);
    return view('Backend.admin.faculties.index', compact('faculties'));
  }
  public function create(){ return view('Backend.admin.faculties.create'); }
  public function store(Request $r){
    $data = $r->validate([
      'name' => ['required','string','max:120'],
      'code' => ['required','string','max:20','unique:faculties,code'],
    ]);
    Faculty::create($data);
    return redirect()->route('faculties.index')->with('ok','Faculty added.');
  }
  public function edit(Faculty $faculty){ return view('Backend.admin.faculties.edit', compact('faculty')); }
  public function update(Request $r, Faculty $faculty){
    $data = $r->validate([
      'name' => ['required','string','max:120'],
      'code' => ['required','string','max:20',"unique:faculties,code,{$faculty->id}"],
    ]);
    $faculty->update($data);
    return redirect()->route('faculties.index')->with('ok','Faculty updated.');
  }
  public function destroy(Faculty $faculty){
    $faculty->delete();
    return back()->with('ok','Faculty deleted.');
  }
}

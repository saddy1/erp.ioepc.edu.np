<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $faculties = Faculty::orderBy('code')->get();
        $sections  = Section::with('faculty')
            ->orderBy('faculty_id')
            ->orderBy('name')
            ->paginate(30);

        return view('Backend.admin.faculties.index', compact('faculties', 'sections'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'name'       => ['required', 'string', 'max:50'],
            'code'       => ['nullable', 'string', 'max:20'],
        ]);

        Section::create($data);

        return redirect()->route('sections.index')->with('ok', 'Section added.');
    }

    public function edit(Section $section)
    {
        $faculties = Faculty::orderBy('code')->get();
        return view('Backend.admin.faculties.edit', compact('section', 'faculties'));
    }

    public function update(Request $r, Section $section)
    {
        $data = $r->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'name'       => ['required', 'string', 'max:50'],
            'code'       => ['nullable', 'string', 'max:20'],
        ]);

        $section->update($data);

        return redirect()->route('sections.index')->with('ok', 'Section updated.');
    }

    public function destroy(Section $section)
    {
        $section->delete();
        return redirect()->route('sections.index')->with('ok', 'Section deleted.');
    }
}

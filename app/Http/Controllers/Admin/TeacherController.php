<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function search(Request $request)
    {
        $term = $request->query('q', '');

        $teachers = Teacher::with('faculty')
            ->when($term, fn($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json(
            $teachers->map(function ($t) {
                return [
                    'id'           => $t->id,
                    'name'         => $t->name,
                    'nick_name'    => $t->nick_name,  // <-- Added
                    'faculty_code' => optional($t->faculty)->code,
                ];
            })
        );
    }

    public function index(Request $request)
    {
        $faculties = Faculty::orderBy('name')->get();

        $filters = [
            'faculty_id' => $request->input('faculty_id'),
            'search'     => $request->input('search'),
            'status'     => $request->input('status'),
        ];

        $teachersQuery = Teacher::with('faculty')
            ->when($filters['faculty_id'], fn($q, $fid) => $q->where('faculty_id', $fid))
            ->when($filters['status'] !== null && $filters['status'] !== '', function ($q) use ($filters) {
                if ($filters['status'] == '1') $q->where('is_active', true);
                if ($filters['status'] == '0') $q->where('is_active', false);
            })
            ->when($filters['search'], function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('faculty_id')
            ->orderBy('name');

        $teachers = $teachersQuery->paginate(25)->withQueryString();

        return view('Backend.admin.teachers.index', compact(
            'teachers',
            'faculties',
            'filters'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Teacher::create($data); // nick_name auto-generated in model

        return redirect()
            ->route('admin.teachers.index', ['faculty_id' => $data['faculty_id']])
            ->with('ok', 'Teacher created successfully.');
    }

    public function edit(Teacher $teacher)
    {
        $faculties = Faculty::orderBy('name')->get();

        return view('Backend.admin.teachers.edit', compact(
            'teacher',
            'faculties'
        ));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validateData($request, $teacher->id);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $teacher->update($data); // nick_name auto-updated in model if required

        return redirect()
            ->route('admin.teachers.index', ['faculty_id' => $teacher->faculty_id])
            ->with('ok', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return back()->with('ok', 'Teacher deleted.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('teachers', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:191'],

            'email' => [
                'nullable',
                'email',
                'max:191',
                Rule::unique('teachers', 'email')->ignore($id),
            ],

            'phone'      => ['nullable', 'string', 'max:20'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'is_active'  => ['nullable', 'boolean'],
            'password'   => ['nullable', 'string', 'min:6'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

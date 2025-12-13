<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentTeacherRole;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin;


class DepartmentRoleController extends Controller
{
    public function index()
    {
        $departments = Department::with([
            'faculties',
            'hod.teacher',
            'deputyHods.teacher',
        ])->get();

        return view('Backend.departments.index', compact('departments'));
    }

    // CREATE DEPARTMENT (you already use this from faculty page)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:190'],
            'code'        => ['required', 'string', 'max:50', 'unique:departments,code'],
            'faculty_ids' => ['required', 'array', 'min:1'],
            'faculty_ids.*' => ['integer', 'exists:faculties,id'],
        ]);

        $department = Department::create([
            'name' => $data['name'],
            'code' => $data['code'],
        ]);

        $department->faculties()->sync($data['faculty_ids']);

        return redirect()
            ->route('faculties.index')
            ->with('ok', 'Department created successfully.');
    }

    public function editRoles(Department $department)
    {
        // ✅ Only teachers from faculties that belong to THIS department
        $facultyIds = $department->faculties()->pluck('faculties.id');

        $teachers = Teacher::whereIn('faculty_id', $facultyIds)
            ->orderBy('name')
            ->get();

        $existingRoles = $department->teacherRoles()
            ->with('teacher')
            ->get();

        return view('Backend.departments.edit_roles', compact(
            'department',
            'teachers',
            'existingRoles'
        ));
    }

public function storeRoles(Request $request, Department $department)
{
    $data = $request->validate([
        'hod_id'        => 'nullable|exists:teachers,id',
        'deputy_ids'    => 'array',
        'deputy_ids.*'  => 'nullable|exists:teachers,id',
    ]);

    // ✅ Only allow teacher IDs from this department's faculties
    $facultyIds = $department->faculties()->pluck('faculties.id');

    $allowedTeacherIds = Teacher::whereIn('faculty_id', $facultyIds)
        ->pluck('id')
        ->toArray();

    $hodId = $data['hod_id'] ?? null;

    // Clean deputy IDs: remove empty, unique
    $deputyIds = collect($data['deputy_ids'] ?? [])
        ->filter()   // remove null / ""
        ->unique()   // remove duplicates (35, 35 → 35)
        ->values()
        ->all();

    $errors = [];

    // 1) HOD must be from this department (if chosen)
    if ($hodId && !in_array($hodId, $allowedTeacherIds)) {
        $errors['hod_id'] = 'Selected HOD does not belong to this department.';
    }

    // 2) All deputies must be from this department
    foreach ($deputyIds as $tid) {
        if (!in_array($tid, $allowedTeacherIds)) {
            $errors['deputy_ids'] = 'One or more Deputy HODs do not belong to this department.';
            break;
        }
    }

    // 3) HOD cannot also be Deputy
    if ($hodId && in_array($hodId, $deputyIds)) {
        $errors['deputy_ids'] = 'The same teacher cannot be both HOD and Deputy HOD.';
    }

    // 4) Max 2 deputies
    if (count($deputyIds) > 2) {
        $errors['deputy_ids'] = 'You can assign at most 2 Deputy HODs.';
    }

    // ❗ If any rule failed → redirect to index with errors
    if (!empty($errors)) {
        return redirect()
            ->route('admin.departments.index')
            ->withErrors($errors)
            ->withInput();
    }

    // ✅ All good → save roles safely

    // Remove old roles
    $department->teacherRoles()->delete();

    // HOD
    if ($hodId) {
        DepartmentTeacherRole::create([
            'department_id' => $department->id,
            'teacher_id'    => $hodId,
            'role'          => 'hod',
        ]);
    }

    // Deputies
    foreach ($deputyIds as $teacherId) {
        DepartmentTeacherRole::create([
            'department_id' => $department->id,
            'teacher_id'    => $teacherId,
            'role'          => 'deputy_hod',
        ]);
    }

    // 🔵 SYNC ADMINS TABLE FOR HOD & DEPUTIES
    $this->syncAdminsForDepartment($department, $hodId, $deputyIds);

    return redirect()
        ->route('admin.departments.index')
        ->with('success', 'Department roles updated successfully.');
}
protected function syncAdminsForDepartment(Department $department, ?int $hodId, array $deputyIds): void
{
    // Current teacher IDs that should have HOD admin access for THIS department
    $teacherIds = collect([$hodId])
        ->merge($deputyIds)
        ->filter()
        ->unique()
        ->values()
        ->all();

    // Emails of teachers we are assigning as HOD/Deputy
    $teacherEmails = [];

    foreach ($teacherIds as $tid) {
        $teacher = Teacher::find($tid);

        if (!$teacher || !$teacher->email) {
            continue;
        }

        $teacherEmails[] = $teacher->email;

        // Find existing admin by email or create new
        $admin = Admin::firstOrNew(['email' => $teacher->email]);

        // Copy basic info
        $admin->name    = $teacher->name ?? $admin->name;
        $admin->contact = $teacher->contact ?? $admin->contact;

        // If new admin, copy password hash from teacher
        // (assumes teacher->password is already hashed)
        if (!$admin->exists && !empty($teacher->password)) {
            $admin->password = $teacher->password;
        }

        // Don't override super admin flag
        $admin->is_hod        = true;
        $admin->department_id = $department->id;

        $admin->save();
    }

    // Optional cleanup:
    // For this department, any admin that is_hod but whose email is NOT in current HOD/deputy list
    // will lose HOD status and department access.
    if (!empty($teacherEmails)) {
        Admin::where('department_id', $department->id)
            ->where('is_hod', true)
            ->whereNotIn('email', $teacherEmails)
            ->update([
                'is_hod'        => false,
                'department_id' => null,
            ]);
    }
}

}

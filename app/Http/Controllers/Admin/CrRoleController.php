<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CrRoleController extends Controller
{
    /**
     * Show filter form + students of selected section/batch
     */
    public function index(Request $request)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        // 🔐 Faculties filtered by role
        $facultiesQuery = Faculty::orderBy('code');

        if ($admin && $admin->isDepartmentAdmin()) {
            if (!empty($managedFacultyIds)) {
                $facultiesQuery->whereIn('id', $managedFacultyIds);
            } else {
                // no faculties mapped for this HOD
                $facultiesQuery->whereRaw('0 = 1');
            }
        }

        $faculties = $facultiesQuery->get();

        $years     = [1, 2, 3, 4, 5];               // adjust if needed
        $semesters = [1,2,3,4,5,6,7,8,9,10];        // adjust to your pattern

        $facultyId      = $request->input('faculty_id');
        $sectionId      = $request->input('section_id');
        $selectedYear   = $request->input('year');
        $selectedSem    = $request->input('semester');

        // 🔐 If HOD and selected faculty is not in managed list → ignore it
        if ($admin && $admin->isDepartmentAdmin()) {
            if ($facultyId && !in_array($facultyId, $managedFacultyIds)) {
                $facultyId = null;
                $sectionId = null;
            }
        }

        $selectedFaculty = null;
        $selectedSection = null;
        $sections        = collect();
        $students        = collect();
        $currentCrId     = null;
        $currentVcrId    = null;

        // If faculty selected, load its sections
        if ($facultyId) {
            $selectedFaculty = Faculty::find($facultyId);

            $sections = Section::where('faculty_id', $facultyId)
                ->orderBy('name')
                ->get();
        }

        // If full filter selected -> load students + CR/VCR
        if ($facultyId && $sectionId && $selectedYear && $selectedSem) {
            // Extra safety for HOD: section must belong to allowed faculties
            if ($admin && $admin->isDepartmentAdmin()) {
                $selectedSection = Section::where('id', $sectionId)
                    ->whereIn('faculty_id', $managedFacultyIds)
                    ->first();
            } else {
                $selectedSection = Section::where('faculty_id', $facultyId)
                    ->where('id', $sectionId)
                    ->first();
            }

            if ($selectedSection) {
                // students come from students table (year+sem)
                $students = Student::where('faculty_id', $facultyId)
                    ->where('section_id', $sectionId)
                    ->where('year', (int) $selectedYear)
                    ->where('semester', (int) $selectedSem)
                    ->orderBy('symbol_no')
                    ->get();

                // roles from student_roles (year+sem+section+role)
                $crRole = StudentRole::where('section_id', $sectionId)
                    ->where('year', (int) $selectedYear)
                    ->where('semester', (int) $selectedSem)
                    ->where('role', 'CR')
                    ->first();

                $vcrRole = StudentRole::where('section_id', $sectionId)
                    ->where('year', (int) $selectedYear)
                    ->where('semester', (int) $selectedSem)
                    ->where('role', 'VCR')
                    ->first();

                $currentCrId  = $crRole?->student_id;
                $currentVcrId = $vcrRole?->student_id;
            }
        }

        return view('Backend.admin.cr_roles.index', compact(
            'faculties',
            'years',
            'semesters',
            'sections',
            'selectedFaculty',
            'selectedSection',
            'selectedYear',
            'selectedSem',
            'students',
            'currentCrId',
            'currentVcrId'
        ));
    }

    /**
     * Save CR and VCR for given faculty + section + batch
     */
    public function save(Request $request)
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = $request->attributes->get('admin');
        $managedFacultyIds = $admin ? $admin->managedFacultyIds() : [];

        $data = $request->validate([
            'faculty_id'     => ['required', 'exists:faculties,id'],
            'section_id'     => ['required', 'exists:sections,id'],
            'year'           => ['required', 'integer', 'min:1'],
            'semester'       => ['required', 'integer', 'min:1'],
            'cr_student_id'  => ['nullable', 'exists:students,id'],
            'vcr_student_id' => ['nullable', 'exists:students,id'],

            'cr_password'    => ['nullable', 'string', 'min:6'],
            'vcr_password'   => ['nullable', 'string', 'min:6'],
        ]);

        // 🔐 HOD security: can only manage faculties/sections in their department
        if ($admin && $admin->isDepartmentAdmin()) {
            // faculty must be in managed list
            if (empty($managedFacultyIds) || !in_array($data['faculty_id'], $managedFacultyIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'You are not allowed to manage CR / VCR for this faculty.');
            }

            // section must also belong to allowed faculties
            $section = Section::where('id', $data['section_id'])
                ->whereIn('faculty_id', $managedFacultyIds)
                ->first();

            if (!$section) {
                return back()
                    ->withInput()
                    ->with('error', 'You are not allowed to manage CR / VCR for this section.');
            }
        }

        // CR and VCR cannot be the same
        if (!empty($data['cr_student_id']) && !empty($data['vcr_student_id']) &&
            $data['cr_student_id'] == $data['vcr_student_id']
        ) {
            return back()->withInput()->with('error', 'CR and VCR cannot be the same student.');
        }

        // Valid students in this faculty + section + year + sem
        $validIds = Student::where('faculty_id', $data['faculty_id'])
            ->where('section_id', $data['section_id'])
            ->where('year', (int)$data['year'])
            ->where('semester', (int)$data['semester'])
            ->pluck('id')
            ->all();

        foreach (['cr_student_id', 'vcr_student_id'] as $key) {
            if (!empty($data[$key]) && !in_array($data[$key], $validIds)) {
                return back()->withInput()->with(
                    'error',
                    strtoupper(str_replace('_student_id', '', $key)).' does not belong to this year/semester/section.'
                );
            }
        }

        // Delete old roles for this section + year + sem
        StudentRole::where('section_id', $data['section_id'])
            ->where('year', (int)$data['year'])
            ->where('semester', (int)$data['semester'])
            ->delete();

        // Reset can_login for all students in this context (strict)
        Student::whereIn('id', $validIds)->update(['can_login' => false]);

        // helper: set login + optionally password + must_change_password
        $ensureLogin = function (int $studentId, ?string $plainPassword = null) {
            $student = Student::find($studentId);
            if (!$student) return;

            $updates = ['can_login' => true];

            if ($plainPassword !== null && $plainPassword !== '') {
                // 🔐 assign plain text; mutator will Hash::make()
                $updates['password']             = $plainPassword;
                $updates['must_change_password'] = true;
            }

            $student->update($updates);
        };

        // Create CR
        if (!empty($data['cr_student_id'])) {
            StudentRole::create([
                'student_id' => $data['cr_student_id'],
                'section_id' => $data['section_id'],
                'year'       => (int)$data['year'],
                'semester'   => (int)$data['semester'],
                'role'       => 'CR',
            ]);

            $ensureLogin((int)$data['cr_student_id'], $data['cr_password'] ?? null);
        }

        // Create VCR
        if (!empty($data['vcr_student_id'])) {
            StudentRole::create([
                'student_id' => $data['vcr_student_id'],
                'section_id' => $data['section_id'],
                'year'       => (int)$data['year'],
                'semester'   => (int)$data['semester'],
                'role'       => 'VCR',
            ]);

            $ensureLogin((int)$data['vcr_student_id'], $data['vcr_password'] ?? null);
        }

        return redirect()->route('admin.cr_roles.index', [
            'faculty_id' => $data['faculty_id'],
            'section_id' => $data['section_id'],
            'year'       => $data['year'],
            'semester'   => $data['semester'],
        ])->with('ok', 'CR / VCR updated successfully.');
    }
}

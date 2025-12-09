<?php
// app/Http/Controllers/Student/ClassFeedbackController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassFeedback;
use App\Models\RoutineFeedback;
use App\Models\Routine;
use App\Models\Student;
use Illuminate\Http\Request;

class ClassFeedbackController extends Controller
{
    public function store(Request $request)
    {
        $student = Student::with('role')->findOrFail(session('student_id'));

        // Only CR / VCR allowed
        if (!$student->role) {
            abort(403, 'Not allowed');
        }

        $data = $request->validate([
            'routine_id' => ['required', 'exists:routines,id'],
            'date'       => ['required', 'date'],
            'was_taught' => ['required', 'boolean'],
            'remarks'    => ['nullable', 'string', 'max:500'],
        ]);

        // Only for today (prevent back/future manipulation)
        if ($data['date'] !== now()->toDateString()) {
            return back()->with('error', 'You can mark only for today.');
        }

        $routine = Routine::findOrFail($data['routine_id']);

        // Ensure this routine belongs to same section as student
        if (
            $routine->section_id !== $student->section_id ||
            $routine->faculty_id !== $student->faculty_id ||
            $routine->semester   !== $student->semester ||
            $routine->batch      !== $student->batch
        ) {
            abort(403, 'Invalid routine for this student.');
        }

        RoutineFeedback::updateOrCreate(
            [
                'routine_id' => $routine->id,
                'student_id' => $student->id,
                'date'       => $data['date'],
            ],
            [
                'was_taught' => $data['was_taught'],
                'remarks'    => $data['remarks'] ?? null,
            ]
        );

        return back()->with('ok', 'Class status saved.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::latest()->paginate(10);
        return view('Backend.admin.exams.index', compact('exams'));
    }

    public function meta(Request $r)
    {
        $title = $r->query('title');

        $exam = Exam::where('exam_title', $title)->latest('id')->first();
        if (!$exam) {
            return response()->json(['ok'=>false], 404);
        }

        // Map 'new'/'old' to your current filter's batch values '1'/'2'
        $batchNumber = $exam->batch === 'new' ? '1' : '2';

        return response()->json([
            'ok' => true,
            'data' => [
                'exam_title'    => $exam->exam_title,
                'batch_text'    => $exam->batch,
                'batch'         => $batchNumber,
                'semester_type' => $exam->semester,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester' => 'required|in:odd,even',
            'batch' => 'required|in:new,old',
            'exam_title' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'first_exam_date_bs' => 'required|string',
        ]);

        // Default status is 0 (scheduled)
        $validated['status'] = 0;

        Exam::create($validated);
        return back()->with('ok', 'Exam detail added successfully!');
    }

    public function update(Request $request, Exam $exam)
    {
        // Prevent editing if exam is completed (status = 1)
        if ($exam->status == 1) {
            return back()->withErrors(['error' => 'Cannot edit a completed exam.']);
        }

        $validated = $request->validate([
            'semester' => 'required|in:odd,even',
            'batch' => 'required|in:new,old',
            'exam_title' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
            'first_exam_date_bs' => 'required|string',
        ]);

        $exam->update($validated);
        return back()->with('ok', 'Exam detail updated successfully!');
    }

    public function updateStatus(Request $request, Exam $exam)
    {
        // Validate status input
        $validated = $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $exam->update(['status' => $validated['status']]);

        $message = $validated['status'] == 1 
            ? 'Exam marked as completed!' 
            : 'Exam marked as scheduled!';

        return back()->with('ok', $message);
    }

    public function destroy(Exam $exam)
    {
        // Prevent deletion if exam is completed (status = 1)
        if ($exam->status == 1) {
            return back()->withErrors(['error' => 'Cannot delete a completed exam.']);
        }

        $exam->delete();
        return back()->with('ok', 'Exam deleted successfully.');
    }
}
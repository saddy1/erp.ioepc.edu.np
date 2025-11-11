<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Exam, RoutineSlot, RoutineSubject, Faculty, FacultySemesterSubject};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoutineController extends Controller
{
    public function index(Request $r)
    {
        $date       = $r->query('date');
        $semester   = $r->query('semester');   // optional
        $batch      = $r->query('batch');      // required
        $examTitle  = $r->query('exam_title'); // required

        // Only fetch completed exams (status = 1)
        $examTitles = Exam::query()
            ->where('status', 1)  // Only completed exams
            ->orderBy('exam_title')
            ->pluck('exam_title')
            ->unique()
            ->values();

        $faculties = Faculty::orderBy('code')->get();

        // Must pick both batch & exam_title before querying
        $hasRequired = filled($batch) && filled($examTitle);

        if (!$hasRequired) {
            $slots   = collect();
            $grouped = collect();
            return view('Backend.admin.routines.index', compact('slots', 'grouped', 'faculties', 'examTitles'));
        }

        // Verify the selected exam is completed
        $examExists = Exam::where('exam_title', $examTitle)
            ->where('status', 1)
            ->exists();

        if (!$examExists) {
            $slots   = collect();
            $grouped = collect();
            return view('Backend.admin.routines.index', compact('slots', 'grouped', 'faculties', 'examTitles'))
                ->with('error', 'Selected exam is not completed or does not exist.');
        }

        // Query routine slots filtered by user input
        $q = RoutineSlot::with(['subjects.subject'])  // Eager load subject relationship
            ->where('batch', $batch)
            ->where('exam_title', $examTitle)
            ->when($semester, fn($q) => $q->where('semester', $semester))
            ->when($date, fn($q) => $q->whereDate('exam_date', $date))
            ->orderBy('exam_date')
            ->orderBy('start_time');

        $slots   = $q->paginate(100)->withQueryString();
        $grouped = $slots->getCollection()->groupBy(function ($s) {
            return $s->exam_date instanceof \Illuminate\Support\Carbon
                ? $s->exam_date->format('Y-m-d')
                : (string) $s->exam_date;  // keep BS string as-is
        });

        return view('Backend.admin.routines.index', compact('slots', 'grouped', 'faculties', 'examTitles'));
    }
}
@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-3 sm:p-4 text-xs">
    <h1 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">
        Exam Attendance (Present / Absent)
    </h1>

    {{-- Filters --}}
    <form method="GET" class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Exam --}}
            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam</label>
                <select name="exam_id"
                        class="w-full rounded-lg border px-2 py-1.5 text-xs"
                        onchange="this.form.submit()">
                    <option value="">— Select Exam —</option>
                    @foreach($exams as $e)
                        <option value="{{ $e->id }}" @selected($examId == $e->id)>
                            {{ $e->exam_title }} ({{ ucfirst($e->batch) }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Exam Date - only when exam chosen --}}
            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam Date</label>
                <select name="exam_date"
                        class="w-full rounded-lg border px-2 py-1.5 text-xs"
                        onchange="this.form.submit()"
                        @disabled(!$examId || $examDates->isEmpty())>
                    <option value="">— Select Date —</option>
                    @foreach($examDates as $date)
                        <option value="{{ $date }}" @selected($examDate === $date)>
                            {{ $date }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Faculty - only faculties with seats on that exam+date --}}
            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Faculty</label>
                <select name="faculty_id"
                        class="w-full rounded-lg border px-2 py-1.5 text-xs"
                        onchange="this.form.submit()"
                        @disabled(!$examId || !$examDate)>
                    <option value="">— Select Faculty —</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" @selected($facultyId == $f->id)>
                            {{ $f->code ?? '' }} {{ $f->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if(!$examId || !$examDate || !$facultyId)
        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
            <p class="text-gray-600 text-sm font-medium">
                Select exam, date and faculty to mark attendance.
            </p>
        </div>
    @elseif(empty($subjectsByRoom))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
            <p class="text-amber-800 text-sm font-medium">
                No seat data found for this faculty on selected exam & date.
            </p>
        </div>
    @else
        {{-- Attendance form --}}
        <form method="POST" action="{{ route('admin.exam_attendance.store') }}"
              class="space-y-6">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $examId }}">
            <input type="hidden" name="exam_date" value="{{ $examDate }}">
            <input type="hidden" name="batch" value="{{ $batch }}">
            <input type="hidden" name="faculty_id" value="{{ $facultyId }}">

            @foreach($subjectsByRoom as $subjectCode => $rooms)
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-3 sm:p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <div class="text-xs sm:text-sm font-semibold text-gray-800">
                                Subject: {{ $subjectCode }} — {{ $rooms[array_key_first($rooms)]['subject_name'] }}
                            </div>
                            <div class="text-[10px] text-gray-500">
                                Exam: {{ $exam->exam_title ?? '' }}
                                • Date: {{ $examDate }}
                                • Batch: {{ $batch == 1 ? 'New' : 'Old' }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach($rooms as $roomId => $info)
                            @php
                                $symbols        = $info['symbols'] ?? [];
                                $absentSymbols  = $info['absent_symbols'] ?? [];
                                $total          = count($symbols);
                                $absentCount    = count($absentSymbols);
                                $presentCount   = $total - $absentCount;
                            @endphp

                            <div class="border rounded-xl p-3 sm:p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                    <div class="text-sm sm:text-base font-semibold text-gray-700">
                                        Room: {{ $roomsMap[$roomId]->room_no ?? 'N/A' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500">
                                        Total: {{ $total }}
                                        • Present: {{ $presentCount }}
                                        • Absent: {{ $absentCount }}
                                    </div>
                                </div>

                                <div class="text-[11px] text-gray-600 mb-2">
                                    Click a symbol to toggle <span class="font-semibold text-red-600">Absent</span> / 
                                    <span class="font-semibold text-emerald-600">Present</span>.
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @foreach($symbols as $sym)
                                        @php
                                            $isAbsent = in_array($sym, $absentSymbols);
                                        @endphp

                                        <label class="cursor-pointer">
                                            {{-- Checkbox drives the "absent" array exactly like before --}}
                                            <input type="checkbox"
                                                   class="hidden symbol-toggle"
                                                   name="absent[{{ $subjectCode }}][{{ $roomId }}][]"
                                                   value="{{ $sym }}"
                                                   @checked($isAbsent)>

                                            <span class="symbol-pill inline-flex min-w-[3.2rem] items-center justify-center rounded-full border px-3 py-2 text-xs sm:text-sm font-semibold
                                                {{ $isAbsent ? 'bg-red-100 text-red-700 border-red-300 line-through' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                                {{ $sym }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-between">
                <button type="submit"
                        class="rounded-lg bg-gray-900 text-white px-4 py-2 text-xs sm:text-sm font-semibold hover:bg-gray-800">
                    Save Attendance
                </button>

                <a href="{{ route('admin.exam_attendance.download_packets', [
                        'exam_id'   => $examId,
                        'exam_date' => $examDate,
                        'batch'     => $batch,
                    ]) }}"
                   class="rounded-lg bg-blue-600 text-white px-4 py-2 text-xs sm:text-sm font-semibold hover:bg-blue-700">
                    Download All Packet Forms (PDF)
                </a>
            </div>
        </form>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Toggle pill style based on checkbox state
    document.querySelectorAll('.symbol-toggle').forEach((checkbox) => {
        const pill = checkbox.nextElementSibling;

        const setState = () => {
            if (checkbox.checked) {
                // Absent
                pill.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                pill.classList.add('bg-red-100', 'text-red-700', 'border-red-300', 'line-through');
            } else {
                // Present
                pill.classList.remove('bg-red-100', 'text-red-700', 'border-red-300', 'line-through');
                pill.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
            }
        };

        // Initial state (for already-saved absentees)
        setState();

        // Clicking the pill toggles checkbox + style
        pill.addEventListener('click', (e) => {
            e.preventDefault();
            checkbox.checked = !checkbox.checked;
            setState();
        });
    });
});
</script>
@endsection

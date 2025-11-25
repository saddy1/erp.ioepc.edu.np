@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-3 sm:p-4 text-xs">
    <h1 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">
        Exam Attendance (Present / Absent)
    </h1>

    {{-- FILTER SECTION --}}
    <form method="GET" class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- EXAM --}}
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

            {{-- EXAM DATE --}}
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

            {{-- FACULTY --}}
            <div>
                <label class="block text-[10px] font-medium text-gray-700 mb-1">Faculty</label>
                <select name="faculty_id"
                        class="w-full rounded-lg border px-2 py-1.5 text-xs"
                        onchange="this.form.submit()"
                        @disabled(!$examId || !$examDate)>
                    <option value="">— Select Faculty —</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" @selected($facultyId == $f->id)>
                            {{ $f->code }} — {{ $f->name }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>
    </form>

    {{-- EMPTY STATES --}}
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

        {{-- FULL ATTENDANCE SAVE --}}
        <form method="POST" action="{{ route('admin.exam_attendance.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $examId }}">
            <input type="hidden" name="exam_date" value="{{ $examDate }}">
            <input type="hidden" name="batch" value="{{ $batch }}">
            <input type="hidden" name="faculty_id" value="{{ $facultyId }}">

            @foreach($subjectsByRoom as $subjectCode => $rooms)

                {{-- SUBJECT BLOCK --}}
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-3 sm:p-4">

                    <div class="mb-2">
                        <div class="text-xs sm:text-sm font-semibold text-gray-800">
                            Subject: {{ $subjectCode }} — {{ $rooms[array_key_first($rooms)]['subject_name'] }}
                        </div>
                        <div class="text-[10px] text-gray-500">
                            Exam: {{ $exam->exam_title }}
                            • Date: {{ $examDate }}
                            • Batch: {{ $batch == 1 ? 'New' : 'Old' }}
                        </div>
                    </div>

                    {{-- 🔥 SUBJECT TOTAL SUMMARY BLOCK (NEW) --}}
                    @php
                        $subjectTotal = 0;
                        $subjectPresentTotal = 0;
                        $subjectAbsentTotal = 0;

                        foreach ($rooms as $rid => $inf) {
                            $roomSymbols = collect($inf['symbols'])->sort()->values()->all();
                            $roomAbsent  = collect($inf['absent_symbols'])->sort()->values()->all();

                            $tt = count($roomSymbols);
                            $ab = count($roomAbsent);
                            $pr = $tt - $ab;

                            $subjectTotal        += $tt;
                            $subjectPresentTotal += $pr;
                            $subjectAbsentTotal  += $ab;
                        }
                    @endphp

                    <div class="rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 mb-4 flex flex-wrap gap-4 text-sm font-semibold">
                        <div class="text-gray-900">
                            Total Students:
                            <span class="text-lg font-bold">{{ $subjectTotal }}</span>
                        </div>
                        <div class="text-emerald-700">
                            Present:
                            <span class="text-lg font-bold">{{ $subjectPresentTotal }}</span>
                        </div>
                        <div class="text-red-700">
                            Absent:
                            <span class="text-lg font-bold">{{ $subjectAbsentTotal }}</span>
                        </div>
                    </div>
                    {{-- END SUMMARY --}}

                    {{-- ALL ROOMS FOR THIS SUBJECT --}}
                    <div class="space-y-4">
                        @foreach($rooms as $roomId => $info)

                            @php
                                $symbols = collect($info['symbols'])->sort()->values()->all();
                                $absentSymbols = collect($info['absent_symbols'])->sort()->values()->all();

                                $total = count($symbols);
                                $absentCount = count($absentSymbols);
                                $presentCount = $total - $absentCount;
                            @endphp

                            {{-- ROOM PANEL --}}
                            <div class="border rounded-xl p-3 sm:p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm sm:text-base font-semibold text-gray-700">
                                        Room: {{ $roomsMap[$roomId]->room_no }}
                                    </div>

                                    <div class="text-[11px] sm:text-xs font-semibold text-gray-700">
                                        <span class="text-green-700">
                                            Present:
                                            <span class="text-lg font-bold">{{ $presentCount }}</span>
                                        </span>

                                        <span class="mx-2">•</span>

                                        <span class="text-red-700">
                                            Absent:
                                            <span class="text-lg font-bold">{{ $absentCount }}</span>
                                        </span>

                                        <span class="mx-2">•</span>

                                        <span class="text-gray-600">
                                            Total: {{ $total }}
                                        </span>
                                    </div>
                                </div>

                                {{-- MARK ALL ABSENT BUTTON --}}
                                <div class="flex justify-end mt-2">
                                    <button type="button"
                                            class="mark-all-absent px-3 py-1 text-[10px] bg-red-600 text-white rounded hover:bg-red-700"
                                            data-room="{{ $roomId }}"
                                            data-subject="{{ $subjectCode }}">
                                        Mark All Absent
                                    </button>
                                </div>

                                {{-- SYMBOL PILL GRID --}}
                                <div class="flex flex-wrap gap-2 mt-3">
                                    @foreach($symbols as $sym)
                                        @php $isAbsent = in_array($sym, $absentSymbols); @endphp

                                        <label class="cursor-pointer">
                                            <input type="checkbox"
                                                class="hidden symbol-toggle"
                                                name="absent[{{ $subjectCode }}][{{ $roomId }}][]"
                                                value="{{ $sym }}"
                                                @checked($isAbsent)>

                                            <span class="symbol-pill inline-flex min-w-[3rem] items-center justify-center rounded-full border px-3 py-1.5 text-xs sm:text-sm font-semibold
                                                {{ $isAbsent 
                                                    ? 'bg-red-100 text-red-700 border-red-300 line-through' 
                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
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

            {{-- SAVE ENTIRE FACULTY --}}
            <div class="flex items-center justify-between">
                <button type="submit"
                        class="rounded-lg bg-gray-900 text-white px-4 py-2 text-xs sm:text-sm font-semibold hover:bg-gray-800">
                    Save Attendance (All Rooms)
                </button>

                <a href="{{ route('admin.exam_attendance.download_packets', [
                        'exam_id' => $examId,
                        'exam_date' => $examDate,
                        'batch' => $batch
                    ]) }}"
                   class="rounded-lg bg-blue-600 text-white px-4 py-2 text-xs sm:text-sm font-semibold hover:bg-blue-700">
                    Download All Packet Forms (PDF)
                </a>
            </div>

        </form>
    @endif
</div>

{{-- =============== JAVASCRIPT =============== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    // TOGGLE PRESENT/ABSENT STYLE
    document.querySelectorAll('.symbol-toggle').forEach(cb => {
        const pill = cb.nextElementSibling;

        const applyState = () => {
            if (cb.checked) {
                pill.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                pill.classList.add('bg-red-100', 'text-red-700', 'border-red-300', 'line-through');
            } else {
                pill.classList.remove('bg-red-100', 'text-red-700', 'border-red-300', 'line-through');
                pill.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
            }
        };

        pill.addEventListener('click', e => {
            e.preventDefault();
            cb.checked = !cb.checked;
            applyState();
        });

        applyState();
    });

    // MARK ALL ABSENT BUTTON
    document.querySelectorAll('.mark-all-absent').forEach(btn => {
        btn.addEventListener('click', () => {
            const room = btn.dataset.room;
            const subject = btn.dataset.subject;

            const checkboxes = document.querySelectorAll(
                `.symbol-toggle[name="absent[${subject}][${room}][]"]`
            );

            checkboxes.forEach(cb => {
                cb.checked = true;
                const pill = cb.nextElementSibling;

                pill.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                pill.classList.add('bg-red-100', 'text-red-700', 'border-red-300', 'line-through');
            });
        });
    });

});
</script>
@endsection

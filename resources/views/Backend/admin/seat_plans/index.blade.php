@extends('Backend.layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">
        {{-- Header --}}
        <div class="mb-4">
            <h1 class="text-base sm:text-lg font-semibold text-gray-900">Seat Planning</h1>
            <p class="text-[11px] text-gray-500 mt-1">
                Generate bench-wise seating plan using room allocations. Each bench holds 2 students; same subject is
                avoided side by side as far as possible.
            </p>
        </div>

        {{-- Filters + Invigilators --}}
        <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
            <form method="GET" id="filterForm" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Exam --}}
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam <span
                                class="text-red-500">*</span></label>
                        <select name="exam_id"
                            class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900"
                            onchange="this.form.submit()">
                            <option value="">— Select Exam —</option>
                            @foreach ($exams as $e)
                                <option value="{{ $e->id }}" @selected($examId == $e->id)>
                                    {{ $e->exam_title }} ({{ ucfirst($e->batch) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Exam date from allocations --}}
                    @if ($examId && !empty($allocatedDates))
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam Date <span
                                    class="text-red-500">*</span></label>
                            <select name="exam_date"
                                class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900"
                                onchange="this.form.submit()">
                                <option value="">— Select Date —</option>
                                @foreach ($allocatedDates as $date)
                                    <option value="{{ $date }}" @selected($examDate === $date)>{{ $date }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Batch --}}
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Batch</label>
                            <select name="batch"
                                class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900"
                                onchange="this.form.submit()">
                                <option value="">Auto</option>
                                <option value="1" @selected(($batch ?? '') == 1)>New</option>
                                <option value="2" @selected(($batch ?? '') == 2)>Old</option>
                            </select>
                        </div>
                    @endif
                </div>

                @if ($examId && empty($allocatedDates))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                        <p class="text-amber-800 text-xs">
                            No room allocations found for this exam. Please create allocations first.
                        </p>
                    </div>
                @endif

                {{-- Invigilator selection (ALWAYS visible when we have data) --}}
                @if ($examId && $examDate && $hasData)
                    <div class="border-t pt-3">
                        <label class="block text-[10px] font-medium text-gray-700 mb-2">
                            Invigilators (Employees) <span class="text-gray-500">(click to select/deselect)</span>
                        </label>

                        {{-- Stats --}}
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-center">
                                <div class="text-[10px] text-blue-700 font-medium">Total Selected</div>
                                <div class="text-lg font-bold text-blue-900" id="totalCount">0</div>
                            </div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-center">
                                <div class="text-[10px] text-emerald-700 font-medium">Faculty</div>
                                <div class="text-lg font-bold text-emerald-900" id="facultyCount">0</div>
                            </div>
                            <div class="rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-center">
                                <div class="text-[10px] text-purple-700 font-medium">Staff</div>
                                <div class="text-lg font-bold text-purple-900" id="staffCount">0</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Faculty list --}}
                            <div>
                                <div class="text-[11px] font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <span>Faculty Members</span>
                                    <span class="text-gray-400">
                                        ({{ $employees->where('employee_type', 'faculty')->count() }})
                                    </span>
                                </div>
                                <div
                                    class="border border-gray-200 rounded-lg p-2 bg-gray-50 max-h-64 overflow-y-auto space-y-1">
                                    @foreach ($employees->where('employee_type', 'faculty') as $emp)
                                        <label
                                            class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white cursor-pointer employee-item"
                                            data-type="faculty">
                                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 employee-checkbox"
                                                @checked(in_array($emp->id, $employeeIds))>
                                            <span class="text-[11px] text-gray-900 flex-1">{{ $emp->full_name }}</span>
                                            <span
                                                class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                                Faculty
                                            </span>
                                        </label>
                                    @endforeach
                                    @if ($employees->where('employee_type', 'faculty')->isEmpty())
                                        <p class="text-[11px] text-gray-400 text-center py-4">No faculty members</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Staff list --}}
                            <div>
                                <div class="text-[11px] font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <span>Staff Members</span>
                                    <span class="text-gray-400">
                                        ({{ $employees->where('employee_type', 'staff')->count() }})
                                    </span>
                                </div>
                                <div
                                    class="border border-gray-200 rounded-lg p-2 bg-gray-50 max-h-64 overflow-y-auto space-y-1">
                                    @foreach ($employees->where('employee_type', 'staff') as $emp)
                                        <label
                                            class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white cursor-pointer employee-item"
                                            data-type="staff">
                                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                class="rounded border-gray-300 text-gray-900 focus:ring-gray-900 employee-checkbox"
                                                @checked(in_array($emp->id, $employeeIds))>
                                            <span class="text-[11px] text-gray-900 flex-1">{{ $emp->full_name }}</span>
                                            <span class="text-[9px] px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                                Staff
                                            </span>
                                        </label>
                                    @endforeach
                                    @if ($employees->where('employee_type', 'staff')->isEmpty())
                                        <p class="text-[11px] text-gray-400 text-center py-4">No staff members</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <button type="submit"
                                class="rounded-lg bg-gray-900 text-white px-4 py-2 text-xs font-semibold hover:bg-gray-800">
                                Generate Seat Plan
                            </button>
                            <button type="button"
                                onclick="document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false); updateCounts();"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium hover:bg-gray-50">
                                Clear All
                            </button>
                        </div>
                    </div>
                @endif
            </form>

            {{-- PDF actions (always visible once we have data) --}}
            @if ($examId && $examDate && $hasData)
                <div class="mt-3 pt-3 border-t flex flex-wrap gap-2">
                    {{-- Print Seat Plan (Landscape) --}}
                    <form method="POST" action="{{ route('seat_plans.print_seat_plan') }}" class="inline" target="_blank">
                        @csrf
                        <input type="hidden" name="exam_id" value="{{ $examId }}">
                        <input type="hidden" name="exam_date" value="{{ $examDate }}">
                        <input type="hidden" name="batch" value="{{ $batch }}">
                        <input type="hidden" name="employee_ids_json" value="{{ json_encode($employeeIds) }}">
                        <button type="submit"
                            class="rounded-lg bg-blue-600 text-white px-4 py-2 text-xs font-semibold hover:bg-blue-700 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 9V4h12v5m0 4v5H6v-5m0-4h12M9 17h6" />
                            </svg>
                            Print Seat Plan
                        </button>
                    </form>

                    {{-- Print Attendance Sheets --}}
                    <form method="POST" action="{{ route('seat_plans.print_attendance') }}" class="inline"
                        target="_blank">
                        @csrf
                        <input type="hidden" name="exam_id" value="{{ $examId }}">
                        <input type="hidden" name="exam_date" value="{{ $examDate }}">
                        <input type="hidden" name="batch" value="{{ $batch }}">
                        <input type="hidden" name="employee_ids_json" value="{{ json_encode($employeeIds) }}">
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-xs font-semibold hover:bg-emerald-700 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 9V4h12v5m0 4v5H6v-5m0-4h12M9 17h6" />
                            </svg>
                            Print Attendance Sheets
                        </button>
                    </form>

                    <a href="{{ route('rooms.exam_seat_plan.print', ['exam_id' => $examId, 'exam_date' => $examDate, 'batch' => $batch]) }}"
                        target="_blank"
                        class="inline-flex items-center rounded-lg bg-gray-900 text-white px-3 py-2 text-[11px] font-semibold hover:bg-gray-800">
                        Print Sheet
                    </a>
                    {{-- Print Invigilator Sheet --}}
                    <form method="POST" action="{{ route('seat_plans.print_invigilators') }}" class="inline"
                        target="_blank">
                        @csrf
                        <input type="hidden" name="exam_id" value="{{ $examId }}">
                        <input type="hidden" name="exam_date" value="{{ $examDate }}">
                        <input type="hidden" name="batch" value="{{ $batch }}">
                        <input type="hidden" name="employee_ids_json" value="{{ json_encode($employeeIds) }}">
                        <button type="submit"
                            class="rounded-lg bg-indigo-600 text-white px-4 py-2 text-xs font-semibold hover:bg-indigo-700 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v6h6M20 20v-6h-6M4 10l6-6m4 16l6-6" />
                            </svg>
                            Print Invigilator Sheet
                        </button>
                    </form>

                </div>
            @endif
        </div>

        {{-- States --}}
        @if (!$examId)
            <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                <p class="text-gray-600 text-sm font-medium">Select an exam to begin seat planning.</p>
            </div>
        @elseif(!$examDate)
            <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                <p class="text-gray-600 text-sm font-medium">Select an exam date from allocated dates.</p>
            </div>
        @elseif(!$hasData)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <p class="text-amber-800 text-sm font-medium">No room allocations found.</p>
                <p class="text-[11px] text-amber-700 mt-1">
                    Please create room allocations for {{ $exam->exam_title }} on {{ $examDate }} first.
                </p>
            </div>
        @else
          {{-- Seat layout --}}
{{-- Seat layout – preview styled similar to PDF layout --}}
<div class="space-y-6">
    @foreach ($seatLayout as $roomId => $data)
        @php
            $room         = $data['room'];
            $cols         = $data['cols'];
            $invigilators = $data['invigilators'] ?? [];

            $maxRows = max(
                count($cols[1] ?? []),
                count($cols[2] ?? []),
                count($cols[3] ?? [])
            );
        @endphp

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            {{-- Room / exam header (similar to PDF main + room header) --}}
            <div class="border-b bg-gray-50">
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="text-center">
                        <div class="text-sm font-extrabold tracking-[0.20em] uppercase text-gray-900">
                            Examination Seat Plan
                        </div>
                        <div class="mt-1 text-[11px] text-gray-700">
                            <strong>{{ $exam->exam_title }}</strong>
                            &nbsp;|&nbsp;
                            Semester: {{ $exam->semester }}
                            &nbsp;|&nbsp;
                            Batch: {{ ucfirst($exam->batch) }}
                            &nbsp;|&nbsp;
                            Date: <strong>{{ $examDate }}</strong>
                            @if ($exam->start_time && $exam->end_time)
                                &nbsp;|&nbsp;
                                Time:
                                {{ \Carbon\Carbon::parse($exam->start_time)->format('h:i A') }}
                                –
                                {{ \Carbon\Carbon::parse($exam->end_time)->format('h:i A') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2 bg-gradient-to-r from-gray-50 to-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <div class="text-sm font-bold text-gray-900">
                                Room {{ $room->room_no }}
                            </div>
                            <div class="text-[10px] text-emerald-700 mt-0.5">
                                Benches:
                                <strong>{{ $room->computed_total_benches }}</strong>
                                &nbsp;•&nbsp;
                                Seats:
                                <strong>{{ $room->computed_total_seats }}</strong>
                            </div>
                        </div>

                        {{-- Invigilators badge list (similar concept to PDF badges) --}}
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 mb-1">
                                Invigilators
                            </div>
                            <div class="flex flex-wrap gap-1 justify-end">
                                @forelse($invigilators as $inv)
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border
                                        {{ $inv->employee_type === 'faculty'
                                            ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                                            : 'bg-purple-50 border-purple-300 text-purple-800' }}">
                                        {{ $inv->full_name }}
                                        <span class="text-[8px] opacity-70">
                                            ({{ ucfirst($inv->employee_type) }})
                                        </span>
                                    </span>
                                @empty
                                    <span class="text-[10px] text-gray-400 italic">
                                        Not assigned
                                    </span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Seat grid – 3 columns, R1/R2 etc, big symbol numbers like PDF --}}
            <div class="px-4 py-4 overflow-x-auto">
                <table class="w-full text-[11px] border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left pb-2 pr-4 text-[11px] font-bold text-gray-600">
                                Column 1
                            </th>
                            <th class="text-left pb-2 pr-4 text-[11px] font-bold text-gray-600">
                                Column 2
                            </th>
                            <th class="text-left pb-2 text-[11px] font-bold text-gray-600">
                                Column 3
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($rowIdx = 1; $rowIdx <= $maxRows; $rowIdx++)
                            <tr>
                                @for ($c = 1; $c <= 3; $c++)
                                    @php
                                        $rows  = $cols[$c] ?? [];
                                        $bench = $rows[$rowIdx] ?? null;

                                        $left  = $bench['left'] ?? null;
                                        $right = $bench['right'] ?? null;

                                        $sameSubject = $left && $right && $left['subject_code'] === $right['subject_code'];

                                        // ⭐ Column 3 rule: if only one student, show on RIGHT side
                                        if ($c === 3 && $bench) {
                                            $hasLeft  = !empty($left);
                                            $hasRight = !empty($right);

                                            if ($hasLeft && !$hasRight) {
                                                $right = $left;
                                                $left  = null;
                                            }
                                        }
                                    @endphp

                                    <td class="{{ $c < 3 ? 'pr-4' : '' }} align-top pt-2">
                                        @if ($bench)
                                            <div class="flex items-start gap-1">
                                                {{-- Row label: R1, R2... --}}
                                                <div class="w-7 text-[9px] font-bold text-gray-400 pt-1 text-center">
                                                    R{{ $rowIdx }}
                                                </div>

                                                {{-- Bench wrapper (like PDF box) --}}
                                                <div
                                                    class="flex-1 rounded-xl border-2 p-1.5
                                                    {{ $sameSubject ? 'border-amber-400 bg-amber-50' : 'border-gray-200 bg-gray-50' }}">
                                                    <table class="w-full border-collapse">
                                                        <tr>
                                                            {{-- LEFT seat --}}
                                                            <td class="w-1/2 border border-gray-200 bg-white px-2 py-2">
                                                                <div class="text-[15px] font-extrabold font-mono text-left text-gray-900">
                                                                    @if ($left && !empty($left['symbol_no']))
                                                                        {{ $left['symbol_no'] }}
                                                                    @else
                                                                        &mdash;
                                                                    @endif
                                                                </div>
                                                            </td>

                                                            {{-- RIGHT seat --}}
                                                            <td class="w-1/2 border border-gray-200 bg-white px-2 py-2">
                                                                <div class="text-[15px] font-extrabold font-mono text-right text-gray-900">
                                                                    @if ($right && !empty($right['symbol_no']))
                                                                        {{ $right['symbol_no'] }}
                                                                    @else
                                                                        &mdash;
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                {{-- Same-subject warning like triangle --}}
                                                @if ($sameSubject)
                                                    <div class="text-amber-500 pt-1" title="Same subject on this bench">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4 py-2 bg-gray-50 text-[9px] text-gray-500 text-right">
                Preview only – final print layout uses PDF template.
            </div>
        </div>
    @endforeach
</div>


        @endif
    </div>

    <script>
        function updateCounts() {
            const totalEl = document.getElementById('totalCount');
            const facultyEl = document.getElementById('facultyCount');
            const staffEl = document.getElementById('staffCount');

            if (!totalEl || !facultyEl || !staffEl) return;

            const checkboxes = document.querySelectorAll('.employee-checkbox');
            let total = 0,
                faculty = 0,
                staff = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total++;
                    const type = cb.closest('.employee-item').dataset.type;
                    if (type === 'faculty') faculty++;
                    if (type === 'staff') staff++;
                }
            });

            totalEl.textContent = total;
            facultyEl.textContent = faculty;
            staffEl.textContent = staff;
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateCounts();
            document.querySelectorAll('.employee-checkbox').forEach(cb => {
                cb.addEventListener('change', updateCounts);
            });
        });
    </script>
@endsection

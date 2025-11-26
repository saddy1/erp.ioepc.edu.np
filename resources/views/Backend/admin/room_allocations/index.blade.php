@extends('Backend.layouts.app')

@section('content')
    <style>
        /* Smooth scrolling */
        .table-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
        }

        .table-scroll-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-scroll-container::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Sticky shadows */
        .sticky-left {
            box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.1);
        }

        .sticky-right {
            box-shadow: -2px 0 4px -2px rgba(0, 0, 0, 0.1);
        }

        .sticky-header {
            box-shadow: 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }

        .sticky-footer {
            box-shadow: 0 -2px 4px -2px rgba(0, 0, 0, 0.1);
        }

        /* Hide number input spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Bigger, bolder allocation numbers */
        .alloc-input {
            font-size: 14px;
            font-weight: 700;
        }

        /* Bigger room text */
        .room-label {
            font-size: 14px;
            font-weight: 700;
        }
    </style>

    <div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">

        {{-- Success Message (for normal redirect) --}}
        @if (session('ok'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-emerald-800 text-sm font-medium">{{ session('ok') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                              clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <ul class="space-y-1 text-sm text-red-800">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="text-base sm:text-lg font-semibold text-gray-900">Room Allocation</h1>
            <p class="text-[11px] text-gray-500 mt-1">
                Allocate students to rooms for each exam date. Room capacity and student counts are validated automatically.
            </p>
        </div>

        {{-- Filter --}}
        <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
            <form method="GET" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Exam --}}
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam <span
                                class="text-red-500">*</span></label>
                        <select name="exam_id"
                                class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                onchange="this.form.submit()">
                            <option value="">— Select Exam —</option>
                            @foreach ($exams as $e)
                                <option value="{{ $e->id }}" @selected($examId == $e->id)>
                                    {{ $e->exam_title }} ({{ ucfirst($e->batch) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date --}}
                    @if ($exam && !empty($examDates))
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam Date <span
                                    class="text-red-500">*</span></label>
                            <select name="exam_date"
                                    class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                    onchange="this.form.submit()">
                                <option value="">— Select Date —</option>
                                @foreach ($examDates as $date)
                                    <option value="{{ $date }}" @selected($examDate === $date)>
                                        {{ $date }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Batch --}}
                        <div>
                            <label class="block text-[10px] font-medium text-gray-700 mb-1">Batch</label>
                            <select name="batch"
                                    class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                    onchange="this.form.submit()">
                                <option value="">Auto</option>
                                <option value="1" @selected(($batch ?? '') == 1)>New</option>
                                <option value="2" @selected(($batch ?? '') == 2)>Old</option>
                            </select>
                        </div>
                    @endif
                </div>

                @if ($exam && $examDate)
                    <div class="mt-3 flex items-center justify-end">
                        <a href="{{ route('room_allocations.print', [
                            'exam_id'   => $examId,
                            'exam_date' => $examDate,
                            'batch'     => $batch,
                        ]) }}"
                           target="_blank"
                           class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                            Download Room Plan (PDF)
                        </a>
                    </div>
                @endif

                @if ($exam && empty($examDates))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                        <p class="text-amber-800 text-xs">
                            No exam dates found in the routine for this exam.
                        </p>
                    </div>
                @endif
            </form>
        </div>

        {{-- States --}}
        @if (!$examId)
            <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                <p class="text-gray-600 text-sm font-medium">
                    Select an exam to begin room allocation
                </p>
            </div>
        @elseif(!$examDate)
            <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                <p class="text-gray-600 text-sm font-medium">
                    Select an exam date
                </p>
            </div>
        @elseif(empty($papers))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <p class="text-amber-800 text-sm font-medium">
                    No subjects scheduled for this date
                </p>
                <p class="text-[11px] text-amber-700">
                    There are no routine slots found for {{ $exam->exam_title }} on {{ $examDate }}
                </p>
            </div>
        @else
            {{-- Allocation Grid --}}
            <form id="roomAllocationForm" method="POST" action="{{ route('room_allocations.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="exam_id" value="{{ $examId }}">
                <input type="hidden" name="exam_date" value="{{ $examDate }}">
                <input type="hidden" name="batch" value="{{ $batch }}">

                @php
                    $colors = [
                        'blue'    => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'header' => 'bg-blue-100'],
                        'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'header' => 'bg-emerald-100'],
                        'purple'  => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'header' => 'bg-purple-100'],
                        'amber'   => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'header' => 'bg-amber-100'],
                        'rose'    => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'header' => 'bg-rose-100'],
                        'cyan'    => ['bg' => 'bg-cyan-50', 'border' => 'border-cyan-200', 'header' => 'bg-cyan-100'],
                        'indigo'  => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'header' => 'bg-indigo-100'],
                        'pink'    => ['bg' => 'bg-pink-50', 'border' => 'border-pink-200', 'header' => 'bg-pink-100'],
                    ];

                    $subjectColors = [];
                    $colorIndex    = 0;
                    $colorKeys     = array_keys($colors);

                    foreach ($papers as $paperKey => $paper) {
                        $subjectCode = $paper['subject_code'];
                        if (!isset($subjectColors[$subjectCode])) {
                            $subjectColors[$subjectCode] = $colors[$colorKeys[$colorIndex % count($colorKeys)]];
                            $colorIndex++;
                        }
                    }
                @endphp

                {{-- Summary --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-blue-700 font-medium">Exam Date</span>
                            <span class="text-sm font-bold text-blue-900">{{ $examDate }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-emerald-700 font-medium">Total Students</span>
                            <span class="text-sm font-bold text-emerald-900">{{ $totalStudents }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-purple-700 font-medium">Subjects Today</span>
                            <span class="text-sm font-bold text-purple-900">{{ count($papers) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="table-scroll-container overflow-auto" style="max-height: 420px;">
                        <table class="min-w-full text-[11px] border-collapse">

                            {{-- Header --}}
                            <thead class="sticky top-0 z-30 sticky-header">
                            <tr class="bg-gray-50 border-b border-gray-200">
                                {{-- Room header --}}
                                <th
                                    class="px-2 py-1.5 text-left font-semibold sticky left-0 z-40 bg-gray-50 min-w-[120px] border-r border-gray-200 sticky-left">
                                    Room
                                </th>

                                {{-- Subject headers --}}
                                @foreach ($papers as $paperKey => $paper)
                                    @php
                                        $fac = $faculties->firstWhere('id', $paper['faculty_id']);
                                        $subjectColor = $subjectColors[$paper['subject_code']];
                                    @endphp
                                    <th
                                        class="px-1.5 py-1.5 text-left font-semibold min-w-[110px] {{ $subjectColor['header'] }} border-x border-gray-200">
                                        <div class="flex flex-col">
                                            <span class="text-gray-900">{{ $paper['subject_code'] }}</span>
                                            <span class="text-[9px] text-gray-500 font-normal line-clamp-1">
                                                {{ $paper['subject_name'] }}
                                            </span>
                                            <span class="text-[9px] text-blue-600 font-medium">
                                                {{ $fac?->code ?? 'N/A' }}
                                            </span>
                                            <span class="text-[9px] text-gray-600 font-medium">
                                                Total: {{ $paper['total_students'] }}
                                            </span>
                                        </div>
                                    </th>
                                @endforeach

                                {{-- Invigs --}}
                                <th
                                    class="px-2 py-1.5 text-center font-semibold bg-gray-50 min-w-[70px] border-l border-gray-200">
                                    Invigs
                                </th>

                                {{-- Room total header --}}
                                <th
                                    class="px-2 py-1.5 text-center font-semibold sticky right-0 z-40 bg-gray-100 min-w-[90px] border-l border-gray-200 sticky-right">
                                    Room Total
                                </th>
                            </tr>
                            </thead>

                            {{-- Body --}}
                            <tbody class="bg-white">
                            @foreach ($rooms as $room)
                                @php
                                    $roomTotal = $totalsByRoom[$room->id] ?? 0;
                                    $roomCap   = $room->computed_total_seats;
                                    $isOver    = $roomTotal > $roomCap;
                                @endphp
                                <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                    {{-- Room col --}}
                                    <td
                                        class="px-2 py-1.5 font-semibold sticky left-0 z-20 bg-white border-r border-gray-200 sticky-left">
                                        <div class="flex flex-col">
                                            <span class="room-label text-gray-900">{{ $room->room_no }}</span>
                                            <span class="text-[9px] text-gray-500">
                                                Cap: {{ $roomCap }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Alloc inputs --}}
                                    @foreach ($papers as $paperKey => $paper)
                                        @php
                                            $val = $allocByRoom[$room->id][$paperKey] ?? 0;
                                            $subjectColor = $subjectColors[$paper['subject_code']];
                                        @endphp
                                        <td class="px-1 py-1 {{ $subjectColor['bg'] }} border-x border-gray-100">
                                            <input
                                                type="number"
                                                name="alloc[{{ $room->id }}][{{ $paperKey }}]"
                                                data-room-id="{{ $room->id }}"
                                                data-paper-key="{{ $paperKey }}"
                                                value="{{ $val ?: '' }}"
                                                min="0"
                                                max="{{ $paper['total_students'] }}"
                                                class="alloc-input w-full rounded border border-gray-300 px-1 py-1 text-center focus:ring-2 focus:ring-gray-900 focus:border-transparent bg-white"
                                                placeholder="0">
                                        </td>
                                    @endforeach

                                    {{-- Invigs --}}
                                    @php
                                        $defaultInvigs = $room->faculties_per_room ?? 2;
                                        $invCount = $invigilatorsByRoom[$room->id] ?? $defaultInvigs;
                                    @endphp
                                    <td class="px-1.5 py-1 bg-white border-l border-gray-200 text-center">
                                        <input type="number" name="invigilators[{{ $room->id }}]"
                                               value="{{ $invCount }}" min="0" max="10"
                                               class="w-full rounded border border-gray-300 px-1 py-1 text-center text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent bg-white">
                                    </td>

                                    {{-- Room total col --}}
                                    <td
                                        class="px-2 py-1.5 text-center font-semibold sticky right-0 z-20 bg-gray-50 border-l border-gray-200 sticky-right">
                                        <span
                                            class="room-total-pill inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $isOver ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-700' }}"
                                            data-room-id="{{ $room->id }}"
                                            data-room-capacity="{{ $roomCap }}">
                                            <span class="room-total-current">{{ $roomTotal }}</span>/{{ $roomCap }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>

                            {{-- Footer: subject totals + remaining --}}
                            <tfoot class="sticky bottom-0 z-30 sticky-footer">
                            <tr class="bg-gray-100 border-t-2 border-gray-300">
                                <td
                                    class="px-2 py-1.5 font-semibold sticky left-0 z-40 bg-gray-100 border-r border-gray-200 sticky-left">
                                    Subject Total / Remaining
                                </td>

                                @foreach ($papers as $paperKey => $paper)
                                    @php
                                        $paperTotal   = $totalsByPaper[$paperKey] ?? 0;
                                        $paperMax     = $paper['total_students'];
                                        $isOverAlloc  = $paperTotal > $paperMax;
                                        $subjectColor = $subjectColors[$paper['subject_code']];
                                        $remaining    = max($paperMax - $paperTotal, 0);
                                    @endphp
                                    <td
                                        class="px-1 py-1.5 text-center {{ $subjectColor['header'] }} border-x border-gray-200">
                                        <span
                                            class="paper-total-pill inline-flex flex-col items-center justify-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-white text-gray-700"
                                            data-paper-key="{{ $paperKey }}"
                                            data-paper-max="{{ $paperMax }}">
                                            <span>
                                                <span class="paper-total-current {{ $isOverAlloc ? 'text-red-700' : '' }}">
                                                    {{ $paperTotal }}
                                                </span>/{{ $paperMax }}
                                            </span>
                                            <span class="text-[9px] mt-0.5 {{ $remaining === 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                                Rem:
                                                <span class="paper-remaining">
                                                    {{ $remaining }}
                                                </span>
                                            </span>
                                        </span>
                                    </td>
                                @endforeach

                                <td
                                    class="px-2 py-1.5 text-center font-semibold sticky right-0 z-40 bg-gray-200 border-l border-gray-200 sticky-right">
                                    —
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <button type="submit"
                            class="rounded-xl bg-gray-900 text-white px-6 py-2.5 text-sm font-semibold hover:bg-gray-800 transition-colors">
                        Save Allocation
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- JS: realtime validation + AJAX save --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('roomAllocationForm');
            if (!form) return;

            const tableContainer = document.querySelector('.table-scroll-container');
            const submitBtn = form.querySelector('button[type="submit"]');
            const allocInputs = form.querySelectorAll('.alloc-input');

            // ----- Realtime change -----
            allocInputs.forEach(input => {
                input.addEventListener('input', onAllocChange);
                input.addEventListener('blur', onAllocChange);
            });

            function onAllocChange(e) {
                const input = e.target;
                const roomId = input.dataset.roomId;
                const paperKey = input.dataset.paperKey;
                if (!roomId || !paperKey) return;

                // Clean numeric
                let raw = (input.value || '').replace(/[^\d]/g, '');
                if (raw === '') {
                    input.value = '';
                    updateRoomTotals(roomId);
                    updatePaperTotals(paperKey);
                    return;
                }

                let value = parseInt(raw, 10);
                if (Number.isNaN(value) || value < 0) value = 0;

                // Respect input max (subject total)
                const maxAttr = input.getAttribute('max');
                if (maxAttr) {
                    const maxVal = parseInt(maxAttr, 10);
                    if (!Number.isNaN(maxVal) && value > maxVal) {
                        value = maxVal;
                    }
                }

                // Subject max & remaining
                const paperPill = document.querySelector(`.paper-total-pill[data-paper-key="${cssEscape(paperKey)}"]`);
                if (paperPill) {
                    const paperMax = parseInt(paperPill.dataset.paperMax || '0', 10);
                    if (!Number.isNaN(paperMax)) {
                        const totalWithout = sumPaperAlloc(paperKey, input);
                        const remaining = paperMax - totalWithout;
                        if (value > remaining) {
                            value = Math.max(0, remaining);
                        }
                    }
                }

                // Room capacity
                const roomPill = document.querySelector(`.room-total-pill[data-room-id="${roomId}"]`);
                if (roomPill) {
                    const roomCap = parseInt(roomPill.dataset.roomCapacity || '0', 10);
                    if (!Number.isNaN(roomCap)) {
                        const totalWithout = sumRoomAlloc(roomId, input);
                        const remaining = roomCap - totalWithout;
                        if (value > remaining) {
                            value = Math.max(0, remaining);
                        }
                    }
                }

                input.value = value > 0 ? String(value) : '';

                updateRoomTotals(roomId);
                updatePaperTotals(paperKey);
            }

            function sumPaperAlloc(paperKey, excludeInput = null) {
                let sum = 0;
                document.querySelectorAll(`.alloc-input[data-paper-key="${cssEscape(paperKey)}"]`).forEach(inp => {
                    if (inp === excludeInput) return;
                    const v = parseInt(inp.value || '0', 10);
                    if (!Number.isNaN(v)) sum += v;
                });
                return sum;
            }

            function sumRoomAlloc(roomId, excludeInput = null) {
                let sum = 0;
                document.querySelectorAll(`.alloc-input[data-room-id="${roomId}"]`).forEach(inp => {
                    if (inp === excludeInput) return;
                    const v = parseInt(inp.value || '0', 10);
                    if (!Number.isNaN(v)) sum += v;
                });
                return sum;
            }

            function updateRoomTotals(roomId) {
                const pill = document.querySelector(`.room-total-pill[data-room-id="${roomId}"]`);
                if (!pill) return;

                const cap = parseInt(pill.dataset.roomCapacity || '0', 10);
                let total = 0;
                document.querySelectorAll(`.alloc-input[data-room-id="${roomId}"]`).forEach(inp => {
                    const v = parseInt(inp.value || '0', 10);
                    if (!Number.isNaN(v)) total += v;
                });

                const span = pill.querySelector('.room-total-current');
                if (span) span.textContent = total;

                pill.classList.remove('bg-red-100', 'text-red-800', 'bg-gray-200', 'text-gray-700');
                if (total > cap) {
                    pill.classList.add('bg-red-100', 'text-red-800');
                } else {
                    pill.classList.add('bg-gray-200', 'text-gray-700');
                }
            }

            function updatePaperTotals(paperKey) {
                const pill = document.querySelector(`.paper-total-pill[data-paper-key="${cssEscape(paperKey)}"]`);
                if (!pill) return;

                const max = parseInt(pill.dataset.paperMax || '0', 10);
                let total = 0;
                document.querySelectorAll(`.alloc-input[data-paper-key="${cssEscape(paperKey)}"]`).forEach(inp => {
                    const v = parseInt(inp.value || '0', 10);
                    if (!Number.isNaN(v)) total += v;
                });

                const totalSpan = pill.querySelector('.paper-total-current');
                const remSpan = pill.querySelector('.paper-remaining');
                if (totalSpan) totalSpan.textContent = total;
                if (remSpan) remSpan.textContent = Math.max(max - total, 0);

                const remWrapper = remSpan?.parentElement;
                if (remWrapper) {
                    remWrapper.classList.remove('text-red-700', 'text-emerald-700');
                    remWrapper.classList.add(max - total === 0 ? 'text-red-700' : 'text-emerald-700');
                }
            }

            // CSS.escape polyfill-ish
            function cssEscape(str) {
                if (window.CSS && CSS.escape) return CSS.escape(str);
                return str.replace(/("|'|\\)/g, '\\$1');
            }

            // ----- AJAX SAVE (stay on same position) -----
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const tableScroll = tableContainer ? tableContainer.scrollTop : 0;

                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving...';

                const formData = new FormData(form);

                try {
                    const resp = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (!resp.ok) throw new Error('Network error');

                    let data = null;
                    try {
                        data = await resp.json();
                    } catch (err) {}

                    showAllocToast(data && data.message ? data.message : 'Room allocation saved ✔');

                    if (tableContainer) {
                        tableContainer.scrollTop = tableScroll;
                    }

                } catch (error) {
                    console.error(error);
                    showAllocToast('Error while saving allocation', true);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });

            function showAllocToast(message, isError = false) {
                let toast = document.getElementById('alloc-toast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'alloc-toast';
                    toast.className =
                        'fixed bottom-4 right-4 z-50 px-3 py-2 rounded-lg text-xs sm:text-sm shadow-lg transition-opacity';
                    document.body.appendChild(toast);
                }
                toast.textContent = message;
                toast.className =
                    'fixed bottom-4 right-4 z-50 px-3 py-2 rounded-lg text-xs sm:text-sm shadow-lg ' +
                    (isError ? 'bg-red-600 text-white' : 'bg-emerald-600 text-white');

                toast.style.opacity = '1';
                setTimeout(() => {
                    toast.style.opacity = '0';
                }, 2200);
            }
        });
    </script>
@endsection

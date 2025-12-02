<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Dashboard</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 text-slate-900">
<div class="min-h-screen max-w-6xl mx-auto p-4 sm:p-6 space-y-5">

    @php
        // Map the day code (mon, tue, ...) to a nice label
        $dayLabels = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];

        // $day comes from controller (e.g. "mon")
        $todayLabel = $dayLabels[$day] ?? ucfirst($day);

        // Today date string (you can change format as you like)
        $todayDate  = now()->format('Y-m-d');

        // For now, use routines as today's slots
        $todaySlots = $routines ?? collect();

        // If controller later passes selectedSlot/students/attendanceMap,
        // keep them; otherwise default safe empty values.
        $selectedSlot  = $selectedSlot  ?? null;
        $students      = $students      ?? collect();
        $attendanceMap = $attendanceMap ?? [];
    @endphp

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                {{ $teacher->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ $teacher->faculty->code ?? '' }} – {{ $teacher->faculty->name ?? '' }}
            </p>
        </div>
        <div class="text-right text-xs">
            <div class="font-semibold text-slate-700">{{ $todayLabel }}</div>
            <div class="text-slate-500">{{ $todayDate }}</div>
            <p class="mt-1 text-[11px] text-slate-500 max-w-xs">
                Attendance can be marked only for today’s classes assigned to you.
            </p>
        </div>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
            <div class="text-[11px] text-slate-500">Today’s Classes</div>
            <div class="mt-1 text-xl font-bold text-slate-900">
                {{ $todaySlots->count() }}
            </div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
            <div class="text-[11px] text-slate-500">Attendance Completed</div>
            <div class="mt-1 text-xl font-bold text-emerald-700">
                {{ $todaySlots->where('attendance_marked', true)->count() }}
            </div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
            <div class="text-[11px] text-slate-500">Pending</div>
            <div class="mt-1 text-xl font-bold text-rose-600">
                {{ $todaySlots->where('attendance_marked', false)->count() }}
            </div>
        </div>
    </div>

    {{-- TODAY'S SCHEDULE TABLE --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-800">
                Today’s Teaching Schedule
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-slate-50">
                <tr class="text-slate-700">
                    <th class="px-3 py-2 text-left font-semibold">Period</th>
                    <th class="px-3 py-2 text-left font-semibold">Time</th>
                    <th class="px-3 py-2 text-left font-semibold">Faculty / Section</th>
                    <th class="px-3 py-2 text-left font-semibold">Subject</th>
                    <th class="px-3 py-2 text-left font-semibold">Room</th>
                    <th class="px-3 py-2 text-center font-semibold">Attendance</th>
                    <th class="px-3 py-2 text-center font-semibold">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($todaySlots as $slot)
                    <tr @class([
                        'bg-emerald-50' => $selectedSlot && $selectedSlot->id === $slot->id,
                    ])>
                        <td class="px-3 py-2 align-middle font-semibold">
                            {{ $slot->period_order }}
                        </td>
                        <td class="px-3 py-2 align-middle text-slate-700">
                            {{ \Carbon\Carbon::parse($slot->period_start)->format('g:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($slot->period_end)->format('g:i A') }}
                        </td>
                        <td class="px-3 py-2 align-middle">
                            <div class="font-semibold text-slate-800">
                                {{ $slot->faculty_code }}
                            </div>
                            <div class="text-[10px] text-slate-500">
                                Section {{ $slot->section_name }},
                                Sem {{ $slot->semester }},
                                {{ $slot->shift_label ?? '' }}
                            </div>
                        </td>
                        <td class="px-3 py-2 align-middle">
                            <div class="font-semibold text-slate-800">
                                {{ $slot->subject_code }}
                            </div>
                            <div class="text-[10px] text-slate-500">
                                {{ $slot->subject_name }}
                            </div>
                        </td>
                        <td class="px-3 py-2 align-middle text-slate-700">
                            {{ $slot->room_no ?? '-' }}
                        </td>
                        <td class="px-3 py-2 align-middle text-center">
                            @if($slot->attendance_marked)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-800 border border-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Marked
                                </span>
                            @else
                                <span class="text-[10px] text-rose-500 font-medium">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-middle text-center">
                            {{-- Simple GET form to select this slot for attendance --}}
                            <form method="GET" action="{{ route('teacher.dashboard') }}">
                                <input type="hidden" name="routine_id" value="{{ $slot->id }}">
                                <button
                                    class="px-3 py-1 rounded-lg text-[10px] font-semibold border
                                           {{ $selectedSlot && $selectedSlot->id === $slot->id ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-50 text-slate-800 border-slate-200 hover:bg-slate-100' }}">
                                    Take Attendance
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-[11px] text-slate-500">
                            No classes assigned for today.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ATTENDANCE FORM FOR SELECTED SLOT --}}
    @if ($selectedSlot)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">
                        Attendance – {{ $selectedSlot->faculty_code }} / Section {{ $selectedSlot->section_name }}
                    </h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Subject: {{ $selectedSlot->subject_code }} – {{ $selectedSlot->subject_name }},
                        Period {{ $selectedSlot->period_order }}, {{ $todayLabel }} ({{ $todayDate }})
                    </p>
                </div>
                <div class="text-[11px] text-slate-500">
                    Only today’s date is allowed.
                </div>
            </div>

            <form method="POST" action="{{ route('teacher.attendance.store') }}">
                @csrf
                <input type="hidden" name="routine_id" value="{{ $selectedSlot->id }}">
                <input type="hidden" name="date" value="{{ $todayDate }}">

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Roll</th>
                            <th class="px-3 py-2 text-left font-semibold">Name</th>
                            <th class="px-3 py-2 text-center font-semibold">Status</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $s)
                            @php
                                $current = $attendanceMap[$s->id] ?? 'P'; // default Present
                            @endphp
                            <tr>
                                <td class="px-3 py-2 align-middle font-semibold text-slate-800">
                                    {{ $s->symbol_no }}
                                </td>
                                <td class="px-3 py-2 align-middle text-slate-800">
                                    {{ $s->name }}
                                </td>
                                <td class="px-3 py-2 align-middle text-center">
                                    <div class="inline-flex items-center gap-4">
                                        <label class="inline-flex items-center gap-1 text-[11px]">
                                            <input type="radio"
                                                   name="attendance[{{ $s->id }}]"
                                                   value="P"
                                                   class="h-3 w-3"
                                                   @checked($current === 'P')>
                                            <span>Present</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 text-[11px]">
                                            <input type="radio"
                                                   name="attendance[{{ $s->id }}]"
                                                   value="A"
                                                   class="h-3 w-3"
                                                   @checked($current === 'A')>
                                            <span>Absent</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-[11px] text-slate-500">
                                    No students found for this section.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-[10px] text-slate-500">
                        Once saved, attendance cannot be changed without admin permission (handle in controller).
                    </p>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                        Save Attendance
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
</body>
</html>

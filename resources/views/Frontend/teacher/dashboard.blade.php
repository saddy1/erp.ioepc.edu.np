<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teacher Dashboard</title>
    @vite('resources/css/app.css')

    <style>
        /* WEEKLY TABLE: Sticky day column like student dashboard */
        .t-sticky-wrap {
            position: relative;
            overflow-x: auto;
            max-width: 100%;
        }

        .t-grid-table {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 100%;
        }

        .t-col-day {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #f8fafc;
            border-right: 1px solid #e2e8f0;
        }

        .t-col-day,
        .t-col-day * {
            background-clip: padding-box !important;
        }

        .t-grid-table th,
        .t-grid-table td {
            border: 0.5px solid #e2e8f0;
        }

        /* Slight highlight for selected merged block row */
        .t-merged-selected {
            background-color: #ecfdf5;
        }

        /* Mobile-friendly attendance table scroll */
        .t-attendance-wrap {
            overflow-x: auto;
            max-width: 100%;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-900">

    <header class="fixed inset-x-0 top-0 h-16 bg-white shadow-lg z-50">
        <div class="h-full px-4 md:px-6 flex items-center justify-between">

            <!-- Logo -->
            <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('assets/ioepc_logo.png') }}" class="h-12 w-auto" alt="Logo">
                <span class="text-lg md:text-xl sm:text-2xl font-bold text-blue-900">IOEPC</span>
            </a>

            <!-- Teacher Profile -->
            <div class="relative">
                <button id="teacherDropdownBtn" class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-gray-100">

                    <!-- Avatar -->
                    <div
                        class="w-9 h-9 rounded-full bg-blue-700 text-white flex items-center justify-center sm:text-2xl text-lg font-semibold">
                        {{ strtoupper(mb_substr($teacher->name ?? 'T', 0, 1)) }}
                    </div>

                    <!-- Name -->
                    <span class="hidden sm:text-2xl sm:inline-block font-medium">
                        {{ $teacher->name ?? 'Teacher' }}
                    </span>

                    <i class="fa-solid fa-chevron-down text-sm text-gray-500"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="teacherDropdownMenu"
                    class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-100 z-50">

                    <div class="px-4 py-2 text-sm text-gray-600 border-b">
                        {{ $teacher->faculty->code ?? '' }} – {{ $teacher->faculty->name ?? '' }}
                    </div>

                    <form method="POST" action="{{ route('teacher.logout') }}">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <script>
        // Toggle dropdown
        document.addEventListener("DOMContentLoaded", () => {
            const btn = document.getElementById("teacherDropdownBtn");
            const menu = document.getElementById("teacherDropdownMenu");

            btn.addEventListener("click", () => {
                menu.classList.toggle("hidden");
            });

            document.addEventListener("click", (e) => {
                if (!btn.contains(e.target)) {
                    menu.classList.add("hidden");
                }
            });
        });
    </script>

    <!-- Header Spacer (to avoid content hiding behind fixed header) -->



    <div class=" mt-16 min-h-screen max-w-6xl mx-auto p-3 sm:p-5 space-y-5">

        @php
            use Carbon\Carbon;
            $todayDate = $todayDate ?? now()->toDateString();
            $todayLabel = $todayLabel ?? now()->format('l');

            $dayLabels = $dayLabels ?? [];
            $weeklyRoutines = $weeklyRoutines ?? collect();
            $gridPeriods = $gridPeriods ?? collect();
            $todaySlots = $todaySlots ?? collect();
            $mergedToday = $mergedToday ?? [];
            $selectedGroup = $selectedGroup ?? null;
            $students = $students ?? collect();
            $attendanceMap = $attendanceMap ?? [];
        @endphp



        {{-- FLASH MESSAGES --}}
        @if (session('ok'))
            <div
                class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-[12px] sm:text-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-[12px] sm:text-sm">
                {{ session('error') }}
            </div>
        @endif
{{-- ================================
    SECTION 1: WEEKLY TEACHING ROUTINE
   ================================ --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="px-3 py-2.5 border-b border-slate-100">
        <h2 class="text-base font-semibold text-slate-800">
            Weekly Teaching Routine
        </h2>
        <p class="text-[12px] text-slate-500 leading-tight">
            All classes assigned for the week, grouped by day & period.
        </p>
    </div>

    @if ($gridPeriods->isEmpty())
        <div class="px-4 py-4 text-sm text-slate-500">
            No routine entries found for you.
        </div>
    @else

        <!-- SCROLLABLE WRAPPER -->
        <div class="overflow-x-auto overflow-y-hidden">
            <table class="min-w-max text-[12px] whitespace-normal break-words border-collapse">

                <!-- HEADER -->
                <thead>
                    <tr class="bg-slate-50 text-slate-700">
                        <th class="sticky left-0 z-20 bg-slate-50 px-2 py-2 text-left
                                   font-semibold border-r border-slate-200 min-w-[70px] whitespace-nowrap">
                            Days
                        </th>

                        @foreach ($gridPeriods as $p)
                            <th class="px-3 py-2 text-center font-semibold min-w-[120px] border-r border-slate-200">
                                <div>{{ $p->order }}</div>
                                <div class="text-[11px] text-slate-600">
                                    {{ Carbon::parse($p->start_time)->format('g:i A') }} –
                                    {{ Carbon::parse($p->end_time)->format('g:i A') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @php
                        $periodCount = $gridPeriods->count();
                        $periodArray = $gridPeriods->values();
                    @endphp

                    @foreach ($dayLabels as $dayKey => $label)
                        @php
                            $segments = [];
                            $dayRoutines = $weeklyRoutines[$dayKey] ?? collect();

                            foreach ($gridPeriods as $idx => $p) {
                                $slot = $dayRoutines->where('period_id', $p->id)->values();

                                foreach ($slot as $r) {

                                    $segKey = implode('|', [
                                        $r->subject_id,
                                        $teacher->id,
                                        $r->group,
                                        $r->type,
                                        $r->room_id,
                                        $r->faculty_id,
                                        $r->section_id,
                                        $r->semester,
                                    ]);

                                    $extended = false;
                                    foreach ($segments as &$sg) {
                                        if ($sg['key'] === $segKey && $sg['end'] === $idx - 1) {
                                            $sg['end'] = $idx;
                                            $extended = true;
                                            break;
                                        }
                                    }
                                    unset($sg);

                                    if (!$extended) {
                                        $segments[] = [
                                            'key' => $segKey,
                                            'start' => $idx,
                                            'end' => $idx,
                                            'routine' => $r,
                                        ];
                                    }
                                }
                            }

                            usort($segments, fn($a, $b) => $a['start'] <=> $b['start']);

                            $lanes = [];
                            foreach ($segments as $seg) {
                                $placed = false;
                                foreach ($lanes as &$lane) {
                                    $last = end($lane);
                                    if ($last['end'] < $seg['start']) {
                                        $lane[] = $seg;
                                        $placed = true;
                                        break;
                                    }
                                }
                                unset($lane);
                                if (!$placed) {
                                    $lanes[] = [$seg];
                                }
                            }
                            if (empty($lanes)) {
                                $lanes = [[]];
                            }
                        @endphp

                        @foreach ($lanes as $laneIndex => $lane)
                            @php
                                $startMap = [];
                                foreach ($lane as $seg) {
                                    $startMap[$seg['start']] = $seg;
                                }
                            @endphp

                            <tr class="border-b border-slate-200">

                                <!-- Sticky day column -->
                                @if ($laneIndex === 0)
                                    <td class="sticky left-0 z-10 bg-white border-r border-slate-200 px-2 py-2 
                                               font-semibold text-slate-800 align-top min-w-[70px]">
                                        <span class="hidden sm:inline">{{ $label }}</span>
                                        <span class="sm:hidden">{{ substr($label, 0, 3) }}</span>
                                    </td>
                                @endif

                                @php $i = 0; @endphp
                                @while ($i < $periodCount)
                                    @php $seg = $startMap[$i] ?? null; @endphp

                                    @if ($seg)
                                        @php
                                            $span = $seg['end'] - $seg['start'] + 1;
                                            $cell = $seg['routine'];
                                            $startPeriod = $periodArray[$seg['start']] ?? null;
                                            $endPeriod = $periodArray[$seg['end']] ?? null;
                                        @endphp

                                        <td colspan="{{ $span }}" class="px-2 py-2 align-top border-r border-slate-200">
                                            <div class="text-[12px] font-semibold text-slate-900 leading-tight">
                                                {{ $cell->subject->code ?? '' }} —
                                                {{ $cell->subject->name ?? '' }}
                                            </div>

                                            <div class="text-[11px] text-slate-600 leading-snug">
                                                {{ $cell->faculty->code ?? '' }}
                                                @if ($cell->faculty?->name)
                                                    — {{ $cell->faculty->name }}
                                                @endif
                                            </div>

                                            <div class="text-[11px] text-slate-600 leading-snug">
                                                Sec {{ $cell->section->name ?? '-' }}
                                                · Sem {{ $cell->semester ?? '-' }}
                                            </div>

                                            <div class="text-[11px] text-slate-600 leading-snug">
                                                {{ Carbon::parse($startPeriod->start_time)->format('g:i A') }} –
                                                {{ Carbon::parse($endPeriod->end_time)->format('g:i A') }}
                                                @if ($cell->room) · Rm {{ $cell->room->room_no }} @endif
                                            </div>
                                        </td>

                                        @php $i += $span; @endphp

                                    @else
                                        <td class="px-2 py-2 border-r border-slate-200"></td>
                                        @php $i++; @endphp
                                    @endif
                                @endwhile
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile hint -->
        <div class="sm:hidden px-3 py-2 text-center text-[11px] text-slate-500 border-t border-slate-100">
            ← Swipe to view full weekly routine →
        </div>

    @endif
</div>


        {{-- =====================================
        SECTION 2: TODAY + MERGED ATTENDANCE
       ====================================== --}}

        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
                <div class="text-[11px] sm:text-xl text-slate-500">Today's Classes</div>
                <div class="mt-1 text-xl sm:text-xl font-bold text-slate-900">
                    {{ count($mergedToday) }}
                </div>
            </div>
            @php
                // merged-level marked / pending calculation
                $completed = 0;
                $pending = 0;
                foreach ($mergedToday as $block) {
                    $isMarked = false;
                    foreach ($block['routine_ids'] as $rid) {
                        $r = $todaySlots->firstWhere('id', $rid);
                        if (!empty($r?->attendance_marked)) {
                            $isMarked = true;
                            break;
                        }
                    }
                    if ($isMarked) {
                        $completed++;
                    } else {
                        $pending++;
                    }
                }
            @endphp
            <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
                <div class="text-[11px]  sm:text-xl text-slate-500">Attendance Completed</div>
                <div class="mt-1 text-xl sm:text-xl font-bold text-emerald-700">
                    {{ $completed }}
                </div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-3 shadow-sm">
                <div class="text-[11px]  sm:text-xl text-slate-500">Pending</div>
                <div class="mt-1 text-xl sm:text-xl font-bold text-rose-600">
                    {{ $pending }}
                </div>
            </div>
        </div>

        {{-- TODAY'S MERGED SCHEDULE --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-3 sm:px-4 py-2.5 sm:py-3 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm  sm:text-l font-semibold text-slate-800">
                    Today's Teaching Schedule
                </h2>
                <p class="hidden sm:block text-[10px]  sm:text-xl text-green-500">
                    Click "Take Attendance" to open the student list below.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[11px]  sm:text-[15px]">
                    <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Time</th>
                            <th class="px-3 py-2 text-left font-semibold">Faculty / Section</th>
                            <th class="px-3 py-2 text-left font-semibold">Subject</th>
                            <th class="px-3 py-2 text-left font-semibold">Room</th>
                            <th class="px-3 py-2 text-center font-semibold">Attendance</th>
                            <th class="px-3 py-2 text-center font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($mergedToday as $block)
                            @php
                                $r = $block['sample'];
                                $startLabel = Carbon::parse($block['start'])->format('g:i A');
                                $endLabel = Carbon::parse($block['end'])->format('g:i A');

                                // Marked or pending
                                $isMarked = false;
                                foreach ($block['routine_ids'] as $rid) {
                                    $rt = $todaySlots->firstWhere('id', $rid);
                                    if (!empty($rt?->attendance_marked)) {
                                        $isMarked = true;
                                        break;
                                    }
                                }
                            @endphp
                            <tr class="@if ($selectedGroup && $selectedGroup['key'] === $block['key']) t-merged-selected @endif">
                                <td class="px-3 py-2 align-middle whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 text-[12px] sm:text-[16px]">
                                        {{ $startLabel }} – {{ $endLabel }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-semibold text-slate-800 text-[12px] sm:text-[16px]">
                                        {{ $r->faculty->code ?? '' }}
                                    </div>
                                    <div class="text-[11px] sm:text-[15px] text-slate-500">
                                        {{ $r->faculty->name ?? '' }} ·
                                        Sec {{ $r->section->name ?? '-' }} ·
                                        Sem {{ $r->semester ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-semibold text-slate-800 text-[15px] sm:text-[16px]">
                                        {{ $r->subject->code ?? '' }}
                                    </div>
                                    <div class="text-[11px] sm:text-[15px] text-slate-500">
                                        {{ $r->subject->name ?? '' }}
                                    </div>
                                    @if ($r->type)
                                        <div class="text-[10px] sm:text-[13px] text-slate-500">
                                            {{ $r->type === 'Practical' ? '(Practical)' : '(Theory)' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-middle sm:text-[15px] text-slate-700">
                                    {{ $r->room->room_no ?? '-' }}
                                </td>
                                <td class="px-3 py-2 align-middle text-center">
                                    @if ($isMarked)
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-800 border border-emerald-200">
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
                                    <form method="GET" action="{{ route('teacher.dashboard') }}">
                                        <input type="hidden" name="merged" value="{{ $block['key'] }}">
                                        <button
                                            class="px-3 py-1 rounded-lg text-[11px] sm:text-xl font-semibold border
                                           @if ($selectedGroup && $selectedGroup['key'] === $block['key']) bg-emerald-600 text-white border-emerald-600
                                           @else
                                               bg-slate-300 text-slate-800 border-slate-200 hover:bg-slate-100 @endif">
                                            Take Attendance
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-3 py-4 text-center text-[12px] sm:text-[11px] text-slate-500">
                                    No classes assigned for today.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sm:hidden px-3 py-2 text-center text-[11px] text-slate-500 border-t border-slate-100">
                ← Swipe to view full schedule →
            </div>
        </div>

        {{-- ATTENDANCE FORM FOR SELECTED MERGED BLOCK (shows only after clicking Take Attendance) --}}
        @if ($selectedGroup && $students->isNotEmpty())
            @php
                $sample = $selectedGroup['sample'];
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-3 sm:px-4 py-2.5 sm:py-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm sm:text-2xlfont-semibold text-slate-800">
                            Attendance – {{ $sample->faculty->code ?? '' }}
                            / Sec {{ $sample->section->name ?? '-' }}
                            / Sem {{ $sample->semester ?? '-' }}
                        </h2>
                        <p class="text-[11px] sm:text-[10px] text-slate-500 mt-0.5">
                            Subject: {{ $sample->subject->code ?? '' }} – {{ $sample->subject->name ?? '' }},
                            {{ Carbon::parse($selectedGroup['start'])->format('g:i A') }}
                            –
                            {{ Carbon::parse($selectedGroup['end'])->format('g:i A') }},
                            {{ $todayLabel }} ({{ $todayDate }})
                        </p>
                    </div>
                    <div class="text-[11px] sm:text-[10px] text-slate-500">
                        Total Students: {{ $students->count() }}
                    </div>
                </div>

                <form method="POST" action="{{ route('teacher.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $todayDate }}">
                    <input type="hidden" name="merged_key" value="{{ $selectedGroup['key'] }}">

                    @foreach ($selectedGroup['routine_ids'] as $rid)
                        <input type="hidden" name="routine_ids[]" value="{{ $rid }}">
                    @endforeach
                    <div class="flex gap-2 px-3 sm:px-4 py-3 border-b border-slate-100">
                        <button type="button" id="markAllPresent"
                            class="px-3 py-1.5 bg-emerald-600 text-white text-xs sm:text-xl font-semibold rounded-lg hover:bg-emerald-700">
                            Mark All Present
                        </button>

                        <button type="button" id="markAllAbsent"
                            class="px-3 py-1.5 bg-rose-600 text-white text-xs sm:text-xl font-semibold rounded-lg hover:bg-rose-700">
                            Mark All Absent
                        </button>

                        <button type="button" id="clearAll"
                            class="px-3 py-1.5 bg-slate-500 text-white text-xs sm:text-xl font-semibold rounded-lg hover:bg-slate-600">
                            Clear All
                        </button>
                    </div>

                    <div class="t-attendance-wrap mt-3 px-3 sm:px-4 py-3">
                        <table class="min-w-full text-[11px] sm:text-2xl">
                            <thead class="bg-red-50 text-slate-700">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">Roll</th>
                                    <th class="px-3 py-2 text-left font-semibold">Name</th>
                                    <th class="px-3 py-2 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($students as $s)
                                    @php
                                        $current = $attendanceMap[$s->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td
                                            class="px-3 py-2 align-middle font-semibold text-slate-800 whitespace-nowrap text-[12px] sm:text-xl">
                                            {{ $s->symbol_no }}
                                        </td>
                                        <td class="px-3 py-2 align-middle text-slate-800 text-[12px] sm:text-xl">
                                            {{ $s->name }}
                                        </td>
                                        <td class="px-3 py-2 align-middle text-center">
                                            <div class="inline-flex items-center gap-4">
                                                <label
                                                    class="inline-flex items-center gap-1 text-[11px] sm:text-xl cursor-pointer">
                                                    <input type="radio" name="attendance[{{ $s->id }}]"
                                                        value="P" class="h-6 w-6 cursor-pointer"
                                                        @checked($current === 'P')>
                                                    <span>Present</span>
                                                </label>
                                                <label
                                                    class="inline-flex items-center gap-1 text-[11px] sm:text-xl cursor-pointer">
                                                    <input type="radio" name="attendance[{{ $s->id }}]"
                                                        value="A" class="h-6 w-6 cursor-pointer"
                                                        @checked($current === 'A')>
                                                    <span>Absent</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="px-3 sm:px-4 py-2.5 sm:py-3 border-t border-slate-100 flex items-center justify-between">
                        <p class="text-[10px] sm:text-xl text-red-500">
                            Once saved, attendance cannot be changed without admin permission.
                        </p>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white sm:text-4xl font-semibold hover:bg-slate-800 transition-colors">
                            Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            // Mark All Present
            document.getElementById("markAllPresent").addEventListener("click", () => {
                document.querySelectorAll("input[type='radio'][value='P']").forEach(r => {
                    r.checked = true;
                });
            });

            // Mark All Absent
            document.getElementById("markAllAbsent").addEventListener("click", () => {
                document.querySelectorAll("input[type='radio'][value='A']").forEach(r => {
                    r.checked = true;
                });
            });

            // Clear All
            document.getElementById("clearAll").addEventListener("click", () => {
                document.querySelectorAll("input[type='radio']").forEach(r => {
                    r.checked = false;
                });
            });

        });
    </script>

</body>

</html>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Routine – CR / VCR Panel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
<style>
/* ===== Sticky table wrapper ===== */
.sticky-table-container {
    overflow-x: auto;
    overflow-y: visible;
    max-width: 100%;
    position: relative;
}

/* Main table */
.sticky-table {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    min-width: 100%;
    border: 0.5px solid #0f172a;
}

/* All cells: solid borders + no bleed */
.sticky-table th,
.sticky-table td {
    border: 0.5px solid #0f172a;
    background-clip: padding-box; /* prevents weird transparency on sticky */
}

/* ===== Sticky columns (Days + Sem) ===== */
.col-day,
.col-sem {
    position: sticky;
    z-index: 20;
    background-color: #f8fafc !important;   /* solid, NOT transparent */
    backdrop-filter: none !important;
}

/* Fixed positions & widths (no shifting) */
.col-day {
    left: 0;
    width: 80px !important;
    min-width: 80px !important;
}
.col-sem {
    left: 80px;                 /* exactly next to day col */
    width: 50px !important;
    min-width: 50px !important;
}

/* Header cells above body */
thead .col-day,
thead .col-sem {
    z-index: 40;
}

/* Strong, consistent border color on sticky cols */
.col-day,
.col-sem,
.col-day * ,
.col-sem * {
    border-color: #0f172a !important;
}
 /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            animation: fadeIn 0.2s ease-out;
        }
        
        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 28rem;
            width: 90%;
            margin: 1rem;
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

.current-class-highlight {
        background-color: #ecfdf5;
        border-left: 4px solid #10b981;
    }
    
    /* Clickable block styling */
    .clickable-block {
        cursor: pointer;
        transition: all 0.15s ease;
    }
    
    .clickable-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .clickable-block:active {
        transform: translateY(0);
    }
</style>


</head>
<body class="bg-slate-100 text-slate-900">
        <header class="fixed inset-x-0 top-0 h-16 bg-white shadow-lg z-50">
        <div class="h-full px-4 md:px-6 flex items-center justify-between">

            <!-- Logo -->
            <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('assets/ioepc_logo.png') }}" class="h-12 w-auto" alt="Logo">
                <span class="text-lg md:text-xl font-bold text-blue-900">IOEPC</span>
            </a>

            <!-- Teacher Profile -->
            <div class="relative">
                <button id="teacherDropdownBtn" class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-gray-100">

                    <!-- Avatar -->
                    <div
                        class="w-9 h-9 rounded-full bg-blue-700 text-white flex items-center justify-center text-lg font-semibold">
                        {{ strtoupper(mb_substr($student->name ?? 'T', 0, 1)) }}
                    </div>

                    <!-- Name -->
                    <span class="hidden sm:inline-block font-medium">
                        {{ $student->name ?? 'Student' }}
                    </span>

                    <i class="fa-solid fa-chevron-down text-sm text-gray-500"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="teacherDropdownMenu"
                    class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-100 z-50">

                    <div class="px-4 py-2 text-sm text-gray-600 border-b">
                        {{ $student->faculty->code ?? '' }} – {{ $student->faculty->name ?? '' }}
                    </div>

                    <form method="POST" action="{{ route('student.logout') }}">
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
<div class=" mt-16 max-w-7xl mx-auto p-3 sm:p-4 lg:p-6 text-sm">
    {{-- Flash Messages --}}
    @if(session('ok'))
        <div class="mb-3 sm:mb-4 rounded-lg sm:rounded-xl border border-emerald-200 bg-emerald-50 px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-emerald-800">
            {{ session('ok') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3 sm:mb-4 rounded-lg sm:rounded-xl border border-red-200 bg-red-50 px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

 
    {{-- Password Change Notice --}}
    @if($student->must_change_password)
        <div class="mb-3 sm:mb-4 rounded-lg sm:rounded-xl border border-amber-200 bg-amber-50 px-3 sm:px-4 py-2 sm:py-3 text-[10px] sm:text-[11px] text-amber-900">
            <span class="font-semibold">Security notice:</span>
            You have been assigned a new password by campus. Please go to the password change page and set your own password.
        </div>
    @endif

    {{-- Student Info Cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
        <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-700 mb-2">Profile</h2>
            <dl class="text-[10px] sm:text-[11px] text-slate-700 space-y-1.5 sm:space-y-1">
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Name</dt>
                    <dd class="text-right truncate">{{ $student->name }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Roll / Symbol No.</dt>
                    <dd class="text-right">{{ $student->symbol_no }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Email</dt>
                    <dd class="text-right truncate">{{ $student->email }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Contact</dt>
                    <dd class="text-right">{{ $student->contact }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-700 mb-2">Academic Info</h2>
            <dl class="text-[10px] sm:text-[11px] text-slate-700 space-y-1.5 sm:space-y-1">
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Faculty</dt>
                    <dd class="text-right truncate">
                        @if($student->faculty)
                            {{ $student->faculty->code }} &mdash; {{ $student->faculty->name }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Section</dt>
                    <dd class="text-right">{{ optional($student->section)->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Batch</dt>
                    <dd class="text-right">{{ $student->batch }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Year / Semester</dt>
                    <dd class="text-right">Year {{ $student->year }} &middot; Sem {{ $student->semester }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="font-medium flex-shrink-0">Role</dt>
                    <dd class="text-right">
                        @if($student->isCr())
                            <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-semibold">
                                CR
                            </span>
                        @elseif($student->isVcr())
                            <span class="inline-flex px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 text-[10px] font-semibold">
                                VCR
                            </span>
                        @else
                            <span class="text-[10px] text-slate-500">Regular Student</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- SECTION 1: COMPLETE CLASS ROUTINE --}}
    @php
        $grid = [];
        if (isset($dayLabels, $gridPeriods, $weeklyRoutines)) {
            foreach ($dayLabels as $dayKey => $dayLabel) {
                $grid[$dayKey] = [];
                foreach ($gridPeriods as $p) {
                    $grid[$dayKey][$p->id] = [];
                }
            }

            foreach ($weeklyRoutines as $dayKey => $dayRoutines) {
                foreach ($dayRoutines as $r) {
                    if (!$r->period_id) continue;
                    if (!isset($grid[$dayKey][$r->period_id])) {
                        $grid[$dayKey][$r->period_id] = [];
                    }
                    $grid[$dayKey][$r->period_id][] = $r;
                }
            }
        }
        $periodArray = $gridPeriods->values(); 
        $periodCount = $gridPeriods->count();
    @endphp

    <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-3 sm:px-4 py-2.5 sm:py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex-1 min-w-0">
                <h2 class="text-xs sm:text-sm font-semibold text-slate-800 truncate">
                    Class Routine – {{ $student->faculty->code ?? '' }}
                    @if($student->section) / {{ $student->section->name }} @endif
                </h2>
                <p class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">
                    Batch {{ $student->batch }} &middot; Semester {{ $student->semester }}
                </p>
            </div>
            <div class="text-left sm:text-right text-[10px] sm:text-[11px] text-slate-500 flex-shrink-0">
                @if(!empty($shiftLabel))
                    Time Slot: <span class="font-semibold text-slate-700">{{ $shiftLabel }}</span>
                @else
                    Time Slot: <span class="text-slate-400">Not defined</span>
                @endif
            </div>
        </div>

   @if($gridPeriods->isEmpty())
    <div class="px-3 sm:px-4 py-3 sm:py-4 text-[10px] sm:text-[11px] text-slate-500">
        No routine entries found for your faculty / batch / semester / section.
    </div>
@else
    <div class="sticky-table-container py-3">
        <table class="sticky-table min-w-full text-[10px] sm:text-[11px] border-2 border-slate-900 rounded-lg">
            <thead>
                <tr class="bg-slate-50">
                    <th class="col-day border border-slate-900   text-center align-middle font-semibold text-slate-700 min-w-[60px] sm:min-w-[80px] whitespace-nowrap">
                        Days
                    </th>
                    <th class="col-sem border border-slate-900  text-center align-middle font-semibold text-slate-700 min-w-[40px] sm:min-w-[50px] whitespace-nowrap">
                        Sem
                    </th>
                    @foreach ($gridPeriods as $p)
                        <th class="border-b-2 border-l border-slate-900  text-center align-middle min-w-[120px] sm:min-w-[140px] bg-slate-50">
                            <div class="font-semibold text-slate-800">{{ $p->order }}</div>
                            <div class="mt-0.5 text-[9px] sm:text-[10px] text-slate-900 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }}
                                –
                                {{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($dayLabels as $dayKey => $dayLabel)
                    @php
                        // Keep your existing segment logic here
                        $segments = [];
                        foreach ($gridPeriods as $idx => $p) {
                            $slot = $grid[$dayKey][$p->id] ?? [];
                            if ($slot instanceof \Illuminate\Support\Collection) {
                                $slot = $slot->all();
                            } elseif (!is_array($slot)) {
                                $slot = $slot ? [$slot] : [];
                            }

                            foreach ($slot as $r) {
                                $teacherKey = 0;
                                if (!is_null($r->teacher_id)) {
                                    $teacherKey = $r->teacher_id;
                                } elseif (isset($r->teachers) && $r->teachers->count()) {
                                    $teacherKey = $r->teachers->pluck('id')->sort()->join('-');
                                }

                                $segKey = implode('|', [
                                    $r->subject_id ?? 0,
                                    $teacherKey,
                                    $r->group ?? '',
                                    $r->type ?? '',
                                    $r->room_id ?? 0,
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
                                        'key'     => $segKey,
                                        'start'   => $idx,
                                        'end'     => $idx,
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

                        $laneCount = count($lanes);
                    @endphp

                    @for ($laneIndex = 0; $laneIndex < $laneCount; $laneIndex++)
                        @php
                            $lane = $lanes[$laneIndex];
                            $startMap = [];
                            foreach ($lane as $seg) {
                                $startMap[$seg['start']] = $seg;
                            }
                        @endphp

                        <tr class="border-t border-slate-900">
                            @if ($laneIndex === 0)
                                <td class="col-day px-1 text-center font-semibold text-slate-800"
                                    rowspan="{{ $laneCount }}">
                                    <span class="hidden xs:inline">{{ $dayLabel }}</span>
                                    <span class="xs:hidden">{{ substr($dayLabel, 0, 3) }}</span>
                                </td>
                                <td class="col-sem px-1 sm:px-2 py-2 sm:py-3 text-center text-slate-800 font-medium"
                                    rowspan="{{ $laneCount }}">
                                    {{ $student->semester }}
                                </td>
                            @endif

                            @php $i = 0; @endphp
                            @while ($i < $periodCount)
                                @php $seg = $startMap[$i] ?? null; @endphp

                                @if ($seg)
                                    @php
                                        $span = $seg['end'] - $seg['start'] + 1;
                                        $cell = $seg['routine'];

                                        $teacherShorts = $cell->teachers
                                            ? $cell->teachers
                                                ->map(function ($t) {
                                                    $parts = preg_split('/\s+/', trim($t->name));
                                                    $initials = array_map(
                                                        fn($p) => strtoupper($p[0] ?? ''),
                                                        $parts
                                                    );
                                                    return implode('', $initials);
                                                })
                                                ->filter()
                                                ->unique()
                                                ->values()
                                                ->toArray()
                                            : [];

                                        if (empty($teacherShorts) && $cell->teacher) {
                                            $parts = preg_split('/\s+/', trim($cell->teacher->name));
                                            $initials = array_map(
                                                fn($p) => strtoupper($p[0] ?? ''),
                                                $parts
                                            );
                                            $teacherShorts = [implode('', $initials)];
                                        }

                                        $startPeriod = $periodArray[$seg['start']] ?? null;
                                        $endPeriod   = $periodArray[$seg['end']]   ?? null;

                                        $startLabel = $startPeriod
                                            ? \Carbon\Carbon::parse($startPeriod->start_time)->format('g:i A')
                                            : '';
                                        $endLabel   = $endPeriod
                                            ? \Carbon\Carbon::parse($endPeriod->end_time)->format('g:i A')
                                            : '';
                                    @endphp

                                    <td colspan="{{ $span }}" class=" align-top bg-white border-l border-slate-900">
                                        <div class="px-2 sm:px-3 py-1.5 sm:py-2 text-[9px] sm:text-[10px] leading-snug">
                                            <div class="text-[10px] sm:text-[11px] font-semibold text-slate-900 leading-tight">
                                                <span class="block sm:inline">{{ $cell->subject->name ?? '' }}</span>

                                                @if (!empty($teacherShorts))
                                                    <span class="block sm:inline sm:before:content-['·'] sm:before:mx-1">{{ implode(', ', $teacherShorts) }}</span>
                                                @endif

                                                <span class="block sm:inline sm:before:content-['·'] sm:before:mx-1">{{ $cell->type }}</span>

                                                @if ($cell->group && $cell->group !== 'ALL')
                                                    <span class="block sm:inline sm:before:content-['·'] sm:before:mx-1">Group {{ $cell->group }}</span>
                                                @endif

                                                @if ($cell->room)
                                                    <span class="block sm:inline sm:before:content-['·'] sm:before:mx-1">Rm {{ $cell->room->room_no }}</span>
                                                @endif
                                            </div>

                                            @if($startLabel && $endLabel)
                                                <div class="mt-0.5 text-[9px] sm:text-[10px] text-slate-500">
                                                    {{ $startLabel }} – {{ $endLabel }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    @php $i += $span; @endphp
                                @else
                                    <td class="px-2 py-1 border-l border-slate-900 bg-white">
                                        <div class="h-6 sm:h-8"></div>
                                    </td>
                                    @php $i++; @endphp
                                @endif
                            @endwhile
                        </tr>
                    @endfor
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="sm:hidden px-3 py-2 text-center text-[10px] text-slate-500 border-t border-slate-100">
        ← Swipe to view full schedule →
    </div>
@endif
    </div>

    {{-- SECTION 2: TODAY'S ROUTINE --}}
    @php
        use Carbon\Carbon;

        $mergedToday = [];
        $todayRoutines = $todayRoutines->sortBy(fn($r) => $r->period->order ?? 0)->values();

        foreach ($todayRoutines as $r) {
            $teacherKey = $r->teacher_id ?? (
                $r->teachers && $r->teachers->count()
                    ? $r->teachers->pluck('id')->sort()->join('-')
                    : 0
            );

            $key = implode('|', [
                $r->subject_id,
                $teacherKey,
                $r->group ?? '',
                $r->type ?? '',
                $r->room_id ?? 0,
            ]);

            $start = Carbon::parse($r->period->start_time)->format('H:i');
            $end   = Carbon::parse($r->period->end_time)->format('H:i');

            if (!isset($mergedToday[$key])) {
                $mergedToday[$key] = [
                    "routine_ids" => [$r->id],
                    "start" => $start,
                    "end" => $end,
                    "routine" => $r,
                ];
            } else {
                if ($start < $mergedToday[$key]["start"]) $mergedToday[$key]["start"] = $start;
                if ($end   > $mergedToday[$key]["end"])   $mergedToday[$key]["end"]   = $end;
                $mergedToday[$key]["routine_ids"][] = $r->id;
            }
        }

        $mergedToday = array_values($mergedToday);

        $allFeedback = \App\Models\RoutineFeedback::whereIn('routine_id', $todayRoutines->pluck('id'))
            ->where('class_date', $currentDate)
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('routine_id');
    @endphp

    <div class="grid grid-cols-1 gap-4">
        <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white px-3 sm:px-4 py-3 shadow-sm">
            <h3 class="text-xs font-semibold text-slate-700 mb-1">Today's Routine</h3>
            <p class="text-[10px] sm:text-[11px] text-slate-500 mb-3">
                {{ $currentDayLabel }} &middot; {{ $currentDate }}
            </p>

            @if(count($mergedToday))
                <div class="space-y-3">
                    @foreach($mergedToday as $slot)
                   @php
    $r = $slot["routine"];
    $ids = $slot["routine_ids"];

    $start = Carbon::parse($slot["start"])->format("g:i A");
    $end   = Carbon::parse($slot["end"])->format("g:i A");

    $teacherNames = $r->teachers && $r->teachers->count()
        ? $r->teachers->pluck("name")->join(", ")
        : ($r->teacher->name ?? "—");

    // FIX: Better logic to determine Practical vs Theory
    // If there's a group (A or B) OR type is "Practical", it's Practical
    $isPractical = ($r->group && $r->group !== 'ALL') || strtolower($r->type) === 'practical';
    $typeLabel = $isPractical ? "(Practical)" : "(Theory)";

    $statuses = [];
    foreach ($ids as $id) {
        if (isset($allFeedback[$id])) {
            $statuses[] = $allFeedback[$id][0]->status;
        }
    }

    if (count($statuses) === 0) {
        $status = "Not Marked";
        $badgeClass = "bg-slate-200 text-slate-700";
    } elseif (count(array_unique($statuses)) === 1) {
        $status = ucfirst($statuses[0]);
        $badgeClass = $status === "Taught"
            ? "bg-emerald-200 text-emerald-700"
            : "bg-rose-200 text-rose-700";
    } else {
        $status = "Partially Marked";
        $badgeClass = "bg-amber-200 text-amber-700";
    }

    $nowTime = now()->format("H:i");
    $isNow = ($nowTime >= $slot["start"] && $nowTime < $slot["end"]);
@endphp

                        <div class="clickable-block border rounded-lg p-3 shadow-sm
                                    @if($isNow) bg-emerald-50 border-emerald-400 current-class-highlight @else bg-blue-50 border-blue-300 @endif"
                             data-ids="{{ implode(',', $ids) }}"
                             data-start="{{ $slot['start'] }}"
                             data-end="{{ $slot['end'] }}"
                             data-subject="{{ $r->subject->code ?? '' }} · {{ $r->subject->name ?? '' }}"
                             data-time="{{ $start }} – {{ $end }}"
                        >
                            <div class="flex justify-between items-center">
                                <div class="text-xs font-semibold text-slate-900">
                                    {{ $r->subject->code ?? '' }} · {{ $r->subject->name ?? '' }}
                                    <span class="text-[10px] text-slate-600 ml-1">{{ $typeLabel }}</span>
                                </div>

                                <span class="text-[10px] font-medium text-slate-700">
                                    {{ $start }} – {{ $end }}
                                </span>
                            </div>

                            <div class="mt-1 text-[10px] text-slate-700 flex gap-4 flex-wrap">
                                <div><span class="font-medium">Teacher:</span> {{ $teacherNames }}</div>
                                <div><span class="font-medium">Room:</span> {{ $r->room->room_no ?? '—' }}</div>
                                @if($r->group && $r->group !== "ALL")
                                    <div><span class="font-medium">Group:</span> {{ $r->group }}</div>
                                @endif
                            </div>

                            <div class="mt-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }} status-badge">
                                    {{ $status }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($student->isCr() || $student->isVcr())
                    <p class="mt-3 text-[10px] text-slate-500 bg-blue-50 px-2 py-1 rounded-lg">
                        As a Class Representative, you can click on each class block to mark the attendance status for your classmates.
                    </p>
                @endif
            @else
                <div class="text-[10px] sm:text-[11px] text-slate-500">
                    No classes scheduled for today.
                </div>
            @endif
        </div>
    </div>
</div>
<!-- ADD THIS MODAL HTML BEFORE THE CLOSING </div> (just before </body> tag) -->

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            
            <h3 class="text-lg font-semibold text-slate-900 text-center mb-2" id="modalTitle">
                Mark Class Status
            </h3>
            
            <div class="mb-4 text-sm text-slate-600 text-center">
                <p class="font-medium text-slate-800" id="modalSubject"></p>
                <p class="text-xs mt-1" id="modalTime"></p>
            </div>
            
            <p class="text-sm text-slate-600 text-center mb-6">
                Please select the status for this class:
            </p>
            
            <div class="flex gap-3">
                <button id="btnTaught" 
                        class="flex-1 px-4 py-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition-colors">
                    ✓ Taught
                </button>
                <button id="btnNotTaught" 
                        class="flex-1 px-4 py-3 rounded-lg bg-rose-500 hover:bg-rose-600 text-white font-semibold text-sm transition-colors">
                    ✗ Not Taught
                </button>
            </div>
            
            <button id="btnCancel" 
                    class="w-full mt-3 px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="modal-overlay" style="display: none;">
    <div class="bg-white rounded-lg p-6 shadow-xl">
        <div class="flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-slate-700 font-medium">Updating status...</span>
        </div>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('=== INITIAL PAGE LOAD ===');
    
    const modal = document.getElementById('confirmModal');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const modalSubject = document.getElementById('modalSubject');
    const modalTime = document.getElementById('modalTime');
    const btnTaught = document.getElementById('btnTaught');
    const btnNotTaught = document.getElementById('btnNotTaught');
    const btnCancel = document.getElementById('btnCancel');
    
    const blocks = document.querySelectorAll('.clickable-block');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const classDate = "{{ $currentDate }}";
    const isCrOrVcr = @json($student->isCr() || $student->isVcr());

    // DEBUG: Check initial state
    console.log('Modal element:', modal ? 'Found' : 'NOT FOUND');
    console.log('Number of clickable blocks:', blocks.length);
    console.log('CSRF Token:', csrfToken);
    console.log('Class Date:', classDate);
    console.log('Is CR/VCR:', isCrOrVcr);
    console.log('Student ID:', @json($student->id));

    let currentBlock = null;

    if (!isCrOrVcr) {
        console.log('User is NOT CR/VCR - blocks disabled');
        blocks.forEach(block => {
            block.classList.remove('clickable-block');
            block.style.cursor = 'default';
        });
        return;
    }

    console.log('User IS CR/VCR - enabling click handlers');

    // Open modal when clicking a block
    blocks.forEach((block, index) => {
        block.addEventListener('click', function () {
            console.log(`=== BLOCK ${index} CLICKED ===`);
            console.log('Block data:', {
                ids: block.dataset.ids,
                subject: block.dataset.subject,
                time: block.dataset.time
            });
            
            currentBlock = block;
            const subject = block.dataset.subject;
            const time = block.dataset.time;
            
            modalSubject.textContent = subject;
            modalTime.textContent = time;
            
            modal.classList.add('active');
        });
    });

    // Handle Taught button
    btnTaught.addEventListener('click', function () {
        console.log('Taught button clicked');
        updateStatus('taught');
    });

    // Handle Not Taught button
    btnNotTaught.addEventListener('click', function () {
        console.log('Not Taught button clicked');
        updateStatus('not_taught');
    });

    // Handle Cancel button
    btnCancel.addEventListener('click', function () {
        console.log('Cancel button clicked');
        closeModal();
    });

    // Close modal when clicking outside
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            console.log('Clicked outside modal - closing');
            closeModal();
        }
    });

    function closeModal() {
        modal.classList.remove('active');
        currentBlock = null;
    }

    function showLoading() {
        loadingOverlay.style.display = 'flex';
    }

    function hideLoading() {
        loadingOverlay.style.display = 'none';
    }

    function updateStatus(status) {
        if (!currentBlock) {
            console.error('ERROR: No current block selected');
            return;
        }

        const idsString = currentBlock.dataset.ids;
        const ids = idsString.split(",").map(id => parseInt(id.trim()));
        
        console.log('=== UPDATE STATUS REQUEST ===');
        console.log('IDs String:', idsString);
        console.log('IDs Array:', ids);
        console.log('Status:', status);
        console.log('Class Date:', classDate);
        console.log('CSRF Token:', csrfToken ? 'Present' : 'MISSING');
        
        const url = "{{ url('/student/routine-feedback/bulk') }}";
        console.log('Request URL:', url);
        
        const payload = {
            routine_ids: ids,
            status: status,
            class_date: classDate,
        };
        console.log('Request Payload:', JSON.stringify(payload, null, 2));
        
        closeModal();
        showLoading();

        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json"
            },
            body: JSON.stringify(payload),
        })
        .then(response => {
            console.log('=== RESPONSE RECEIVED ===');
            console.log('Status Code:', response.status);
            console.log('Status Text:', response.statusText);
            console.log('OK:', response.ok);
            console.log('Headers:', [...response.headers.entries()]);
            
            return response.text().then(text => {
                console.log('=== RAW RESPONSE BODY ===');
                console.log(text);
                
                try {
                    const data = JSON.parse(text);
                    console.log('=== PARSED JSON ===');
                    console.log(data);
                    return { status: response.status, ok: response.ok, data: data };
                } catch (e) {
                    console.error('=== JSON PARSE ERROR ===');
                    console.error(e);
                    console.error('Could not parse response as JSON');
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(result => {
            hideLoading();
            console.log('=== PROCESSING RESULT ===');
            console.log('Result:', result);
            
            if (result.ok && result.data.ok) {
                console.log('✓ Success! Updating UI...');
                
                // Check if currentBlock still exists before accessing it
                if (currentBlock) {
                    const badge = currentBlock.querySelector('.status-badge');
                    if (badge) {
                        if (status === 'taught') {
                            badge.textContent = 'Taught';
                            badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-200 text-emerald-700 status-badge';
                        } else {
                            badge.textContent = 'Not Taught';
                            badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-200 text-rose-700 status-badge';
                        }
                        console.log('Badge updated');
                    }
                }
                
                showNotification('Status updated successfully!', 'success');
                
                setTimeout(() => {
                    console.log('Reloading page...');
                    location.reload();
                }, 1500);
            } else {
                console.error('✗ Update failed');
                console.error('Server returned:', result.data);
                const errorMsg = result.data.message || result.data.error || 'Update failed';
                showNotification(errorMsg, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('=== FETCH ERROR ===');
            console.error('Error type:', error.name);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            showNotification('Failed: ' + error.message, 'error');
        });
    }

    function showNotification(message, type) {
        console.log('Showing notification:', type, '-', message);
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-emerald-500' : 'bg-red-500';
        
        notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
        notification.textContent = message;
        notification.style.animation = 'slideIn 0.3s ease-out';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

console.log('=== SCRIPT LOADED SUCCESSFULLY ===');
</script>

</body>
</html>




@extends('Backend.layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">

        {{-- Flash messages --}}
        @if (session('ok'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Page header --}}
        <div class="mb-5">
            <h1 class="text-lg sm:text-xl font-semibold text-slate-900">
                Class Routine
            </h1>
            <div class="mt-2 h-[2px] w-28 rounded-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-400"></div>
        </div>

        {{-- Filters (simple list filters) --}}
        <div class="mb-5 rounded-2xl border border-slate-900 bg-white p-3 sm:p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.routines.index') }}"
                class="grid grid-cols-1 sm:grid-cols-6 gap-3 sm:gap-4 text-[11px]">

                {{-- Faculty --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Faculty</label>
                    <select name="faculty_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($faculties as $f)
                            <option value="{{ $f->id }}" @selected($filters['faculty_id'] == $f->id)>
                                {{ $f->code ?? '' }} {{ $f->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Batch --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Batch</label>
                    <select name="batch"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($batches as $b)
                            <option value="{{ $b }}" @selected($filters['batch'] == $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Subject Batch (old/new syllabus) --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Subject Batch</label>
                    <select name="subject_batch"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                   focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="old" @selected($filters['subject_batch'] === 'old')>Old</option>
                        <option value="new" @selected($filters['subject_batch'] === 'new')>New</option>
                    </select>
                </div>


                {{-- Semester --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Semester</label>
                    <select name="semester"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem }}" @selected($filters['semester'] == $sem)>{{ $sem }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Section --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Section</label>
                    <select name="section_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($sections as $s)
                            <option value="{{ $s->id }}" @selected($filters['section_id'] == $s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Shift filter --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Shift</label>
                    <select name="shift"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="morning" @selected($filters['shift'] === 'morning')>Morning</option>
                        <option value="day" @selected($filters['shift'] === 'day')>Day</option>
                    </select>
                </div>


                {{-- Day --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Day</label>
                    <select name="day_of_week"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($days as $k => $v)
                            <option value="{{ $k }}" @selected($filters['day_of_week'] == $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Teacher --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-700">Teacher</label>
                    <select name="teacher_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected($filters['teacher_id'] == $t->id)>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-6 flex justify-end gap-2 pt-1">
                    <button type="submit"
                        class="rounded-lg bg-slate-900 px-1.5 py-1.5 text-[11px] font-semibold text-white
                               hover:bg-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        Filter
                    </button>
                    <a href="{{ route('admin.routines.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-1.5 py-1.5 text-[11px] font-medium
                          text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-1 focus:ring-slate-300">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Create form --}}
        <div class="mb-5 rounded-2xl border border-slate-900 bg-white p-3 sm:p-4 shadow-sm">
            <h2 class="mb-3 text-[13px] font-semibold text-slate-900">Add Routine Entry</h2>

            <form method="POST" action="{{ route('admin.routines.store') }}"
                class="grid grid-cols-1 sm:grid-cols-4 gap-3 sm:gap-4 text-[11px]">
                @csrf

                {{-- Batch --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Batch <span class="text-red-500">*</span>
                    </label>
                    <select name="batch" id="batch_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select</option>
                        @foreach ($batches as $b)
                            <option value="{{ $b }}" @selected(old('batch', $filters['batch']) == $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-4 border-b border-slate-900">

                </div>
                {{-- Faculty --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Faculty <span class="text-red-500">*</span>
                    </label>
                    <select name="faculty_id" id="faculty_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select</option>
                        @foreach ($faculties as $f)
                            <option value="{{ $f->id }}" @selected(old('faculty_id', $filters['faculty_id']) == $f->id)>
                                {{ $f->code ?? '' }} {{ $f->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Semester <span class="text-red-500">*</span>
                    </label>
                    <select name="semester" id="semester_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select</option>
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem }}" @selected(old('semester', $filters['semester']) == $sem)>{{ $sem }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Subject Batch (old/new syllabus for mapping) --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Subject Batch <span class="text-[10px] text-slate-500">(old/new syllabus)</span>
                    </label>
                    <select name="subject_batch" id="subject_batch_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                   focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">Default</option>
                        <option value="old" @selected(old('subject_batch', request('subject_batch')) === 'old')>Old</option>
                        <option value="new" @selected(old('subject_batch', request('subject_batch')) === 'new')>New</option>
                    </select>
                </div>


                {{-- Section (AJAX loaded) --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Section <span class="text-red-500">*</span>
                    </label>
                    <select name="section_id" id="section_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select faculty & semester first</option>
                    </select>
                </div>


                {{-- Subject (AJAX loaded) --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <select name="subject_id" id="subject_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select faculty & semester first</option>
                    </select>
                </div>

                {{-- Type TH/PR --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Type <span class="text-red-500">*</span>
                        <span id="type_hint" class="ml-1 text-[10px] text-slate-500"></span>
                    </label>
                    <select name="type" id="type_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="TH" @selected(old('type') == 'TH')>TH (Theory)</option>
                        <option value="PR" @selected(old('type') == 'PR')>PR (Practical)</option>
                    </select>
                </div>

                {{-- Group --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Group <span class="text-red-500">*</span>
                    </label>
                    <select name="group" id="group_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                   focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        {{-- JS will populate based on section + type --}}
                        <option value="ALL" @selected(old('group') == 'ALL')>ALL (Theory combined)</option>
                    </select>
                </div>

                {{-- Day --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Day <span class="text-red-500">*</span>
                    </label>
                    <select name="day_of_week"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        @foreach ($days as $k => $v)
                            <option value="{{ $k }}" @selected(old('day_of_week') == $k)>{{ $v }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Shift --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Shift <span class="text-red-500">*</span>
                    </label>
                    <select name="shift" id="shift_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                   focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select shift</option>
                        <option value="morning">Morning (7:00 – 12:00)</option>
                        <option value="day">Day (10:00 – 4:00)</option>
                    </select>
                </div>


                {{-- Start Period (filtered by shift) --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        Start Period <span class="text-red-500">*</span>
                    </label>
                    <select name="start_period_id" id="start_period_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                   focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select shift first</option>
                        @foreach ($periods as $p)
                            @php
                                $normalizedShift = strtolower(trim($p->shift ?? ''));
                            @endphp
                            <option value="{{ $p->id }}" data-shift="{{ $normalizedShift }}">
                                {{ $p->label }} ({{ $p->start_time }}–{{ $p->end_time }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- End Period (>= start) --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">
                        End Period <span class="text-red-500">*</span>
                    </label>
                    <select name="end_period_id" id="end_period_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                   focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                        <option value="">Select start period</option>
                        @foreach ($periods as $p)
                            @php
                                $normalizedShift = strtolower(trim($p->shift ?? ''));
                            @endphp
                            <option value="{{ $p->id }}" data-shift="{{ $normalizedShift }}">
                                {{ $p->label }} ({{ $p->start_time }}–{{ $p->end_time }})
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Academic year --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">Academic Year</label>
                    <input type="text" name="academic_year" value="{{ old('academic_year') }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                              focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        placeholder="2082/83">
                </div>


                {{-- Teacher search + select (all teachers, client-side search) --}}
                <div class="space-y-1 sm:col-span-2">
                    <label class="block font-medium text-slate-800">
                        Teacher <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="teacher_search" placeholder="Type to search teacher..."
                        class="mb-1 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
              focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">

                    {{-- Search results --}}
                    <div id="teacher_results"
                        class="max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/60 px-2 py-1.5 text-[11px]">
                        <div class="text-[10px] text-slate-500">Type above to search teachers…</div>
                    </div>

                    {{-- Selected teachers --}}
                    <div id="selected_teachers" class="mt-2 flex flex-wrap gap-1.5 text-[10px]">
                        {{-- JS will render chips + hidden inputs (teacher_ids[]) here --}}
                    </div>
                </div>

                {{-- Room --}}
                <div class="space-y-1">
                    <label class="block font-medium text-slate-800">Room</label>
                    <select name="room_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">--</option>
                        @foreach ($rooms as $r)
                            <option value="{{ $r->id }}" @selected(old('room_id') == $r->id)>
                                {{ $r->room_no }}
                            </option>
                        @endforeach
                    </select>
                </div>



                <div class="sm:col-span-4 flex items-end justify-end pt-1">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-1.5 text-[11px] font-semibold text-white
                               hover:bg-indigo-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        Add Entry
                    </button>
                </div>
            </form>
        </div>

        {{-- Routine table in timetable style --}}
        @php
            // Total can come from paginator or simple collection
            $totalRoutines = method_exists($routines, 'total') ? $routines->total() : $routines->count();
            $periodCount = $periods->count();
        @endphp

        {{-- Routine Grid --}}
        <div class="mt-6 rounded-2xl border border-slate-900 bg-white p-3 sm:p-4 shadow-sm">
            {{-- Header + Print --}}
            <div class="mb-3 flex items-start justify-between">
                <div class="flex-1 text-center text-[11px] leading-tight">
                    <div class="font-semibold tracking-wide">TRIBHUVAN UNIVERSITY</div>
                    <div class="font-semibold">INSTITUTE OF ENGINEERING</div>
                    <div class="font-semibold">PURWANCHAL CAMPUS</div>
                    @php
                        $currentFaculty = $filters['faculty_id']
                            ? $faculties->firstWhere('id', $filters['faculty_id'])
                            : null;
                    @endphp
                    @if ($currentFaculty)
                        <div class="mt-1 text-[11px]">
                            Faculty: <span class="font-semibold">{{ $currentFaculty->name }}</span>
                        </div>
                    @endif
                </div>

                <button type="button" onclick="window.print()"
                    class="ml-3 rounded-lg border border-slate-300 bg-white px-1 py-1.5 text-[11px] font-medium text-slate-700
                       hover:bg-slate-50 focus:outline-none focus:ring-1 focus:ring-slate-400">
                    Print
                </button>
            </div>

            @php
                $periodCount = $gridPeriods->count();
                $periodIndex = [];
                foreach ($gridPeriods as $idx => $p) {
                    $periodIndex[$p->id] = $idx;
                }
            @endphp

            <div class="overflow-x-auto rounded-xl border border-slate-900">
                <table class="min-w-full border-collapse text-[11px]">
                    <thead>
                        <tr class="bg-slate-50">
                            <th
                                class="border-b-2 border-slate-900 px-1 py-1 text-left align-middle font-semibold text-slate-700">
                                Days
                            </th>
                            <th
                                class="border-b-2 border-slate-900 px-1 py-1 text-center align-middle font-semibold text-slate-700">
                                Sem
                            </th>
                            @foreach ($gridPeriods as $p)
                                <th class="border-b-2 border-l border-slate-900 px-2 py-1 text-center align-middle">
                                    <div class="font-semibold text-slate-800">{{ $p->order }}</div>
                                    <div class="mt-0.5 text-[10px] text-slate-900">
                                        {{ \Carbon\Carbon::parse($p->start_time)->format('g:i') }}
                                        –
                                        {{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($days as $dayKey => $dayLabel)
                            @php
                                // 1) Build contiguous segments per subject+teacher+group+type+room
                                $segments = [];

                                foreach ($gridPeriods as $idx => $p) {
                                    $slot = $grid[$dayKey][$p->id] ?? [];

                                    if ($slot instanceof \Illuminate\Support\Collection) {
                                        $slot = $slot->all();
                                    } elseif (!is_array($slot)) {
                                        $slot = $slot ? [$slot] : [];
                                    }

                                    foreach ($slot as $r) {
                                        $segKey = implode('|', [
                                            $r->subject_id ?? 0,
                                            $r->teacher_id ?? 0,
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
                                                'key' => $segKey,
                                                'start' => $idx,
                                                'end' => $idx,
                                                'routine' => $r,
                                            ];
                                        }
                                    }
                                }

                                usort($segments, fn($a, $b) => $a['start'] <=> $b['start']);

                                // 2) Assign to lanes for overlapping labs
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
                                        <td class="border-r border-slate-900 px-1 py-3 font-semibold text-slate-800 bg-slate-50"
                                            rowspan="{{ $laneCount }}">
                                            <span class="hidden sm:inline">{{ $dayLabel }}</span>
                                            <span class="sm:hidden">{{ substr($dayLabel, 0, 3) }}</span>
                                        </td>
                                        <td class="border-r border-slate-900 px-1 py-3 text-center text-slate-800 bg-slate-50 font-medium"
                                            rowspan="{{ $laneCount }}">
                                            {{ $filters['semester'] ?: '-' }}
                                        </td>
                                    @endif

                                    @php $i = 0; @endphp
                                    @while ($i < $periodCount)
                                        @php $seg = $startMap[$i] ?? null; @endphp

                                        @if ($seg)
                                            @php
                                                $span = $seg['end'] - $seg['start'] + 1;
                                                $cell = $seg['routine'];

                                                // Teacher short name
                                                $name = $cell->teacher->name ?? ($cell->teacher->code ?? null);

                                                $shortName = null;

                                                if ($name) {
                                                    // Split by space
                                                    $parts = preg_split('/\s+/', trim($name));

                                                    // Take first character of each part
                                                    $initials = array_map(function ($part) {
                                                        return strtoupper($part[0]);
                                                    }, $parts);

                                                    $shortName = implode('', $initials); // e.g., SPP
                                                }

                                            @endphp

                                            <td colspan="{{ $span }}" class="px-2 py-1 align-top bg-white">
                                                <a href="{{ route('admin.routines.edit', $cell->id) }}"
                                                    class="block px-3 py-2 text-[10px] leading-snug hover:bg-green-100">
                                                    <div class="text-[11px] font-semibold text-slate-900 leading-tight">
                                                        {{ $cell->subject->name ?? '' }}

                                                        {{-- show teachers (short form) --}}
                                                        @php
                                                            $teacherShorts = $cell->teachers
                                                                ->map(function ($t) {
                                                                    $parts = preg_split('/\s+/', trim($t->name));
                                                                    $initials = array_map(
                                                                        fn($p) => strtoupper($p[0] ?? ''),
                                                                        $parts,
                                                                    );
                                                                    return implode('', $initials);
                                                                })
                                                                ->filter()
                                                                ->unique()
                                                                ->values()
                                                                ->toArray();
                                                        @endphp

                                                        @if (!empty($teacherShorts))
                                                            <span> · {{ implode(', ', $teacherShorts) }}</span>
                                                        @endif

                                                        <span> · {{ $cell->type }}</span>

                                                        @if ($cell->group && $cell->group !== 'ALL')
                                                            <span> · Group {{ $cell->group }}</span>
                                                        @endif

                                                        @if ($cell->room)
                                                            <span> · Rm {{ $cell->room->room_no }}</span>
                                                        @endif
                                                    </div>
                                                </a>
                                            </td>


                                            @php $i += $span; @endphp
                                        @else
                                            <td class="px-2 py-1 border-l border-slate-900 bg-white">
                                                <div class="h-8"></div>
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
        </div>



        {{-- Responsive notice for mobile --}}
        <div class="mt-2 sm:hidden">
            <div class="text-[10px] text-slate-500 text-center bg-slate-50 rounded-lg px-1 py-2 border border-slate-900">
                <span class="inline-block mr-1">💡</span>
                Swipe left/right to view all periods. Tap on a class for full details.
            </div>
        </div>

    </div>

    {{-- JS: shift → periods, faculty+sem(+batch) → sections+subjects (AJAX), subject → TH/PR, teacher search --}}





  {{-- Make teacher list available to JS --}}
<script>
    window.ALL_TEACHERS = @json(
        $teachers->map(function ($t) {
            return [
                'id'    => $t->id,
                'label' => $t->name . ($t->faculty?->code ? ' (' . $t->faculty->code . ')' : ''),
            ];
        })
    );

    // For create form we only keep old() selections
    window.PRESELECTED_TEACHERS = @json(old('teacher_ids', []));
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shiftSelect        = document.getElementById('shift_select');
        const subjectBatchSelect = document.getElementById('subject_batch_select');
        const groupSelect        = document.getElementById('group_select');

        const startPeriodSelect  = document.getElementById('start_period_select');
        const endPeriodSelect    = document.getElementById('end_period_select');

        const facultySelect      = document.getElementById('faculty_select');
        const semesterSelect     = document.getElementById('semester_select');
        const batchSelect        = document.getElementById('batch_select');
        const sectionSelect      = document.getElementById('section_select');
        const subjectSelect      = document.getElementById('subject_select');

        const typeSelect         = document.getElementById('type_select');
        const typeHint           = document.getElementById('type_hint');

        // ---- Teacher search (chips + results) ----
        const teacherSearch   = document.getElementById('teacher_search');   // 🔴 missing before
        const teacherResults  = document.getElementById('teacher_results');
        const selectedWrapper = document.getElementById('selected_teachers');

        const allTeachers = Array.isArray(window.ALL_TEACHERS) ? window.ALL_TEACHERS : [];
        const preselected = Array.isArray(window.PRESELECTED_TEACHERS) ? window.PRESELECTED_TEACHERS : [];

        // Keep selected teacher ids in a Set
        const selectedIds = new Set(preselected.map(id => parseInt(id, 10)));

        function renderSelectedTeachers() {
            selectedWrapper.innerHTML = '';

            if (!selectedIds.size) {
                const span = document.createElement('span');
                span.className = 'text-[10px] text-slate-400';
                span.textContent = 'No teacher selected.';
                selectedWrapper.appendChild(span);
                return;
            }

            selectedIds.forEach(id => {
                const teacher = allTeachers.find(t => t.id === id);
                if (!teacher) return;

                const chip = document.createElement('div');
                chip.className =
                    'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 ' +
                    'border border-slate-300 text-slate-800';

                const label = document.createElement('span');
                label.textContent = teacher.label;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = '×';
                removeBtn.className =
                    'ml-1 rounded-full border border-slate-400 px-1 text-[10px] leading-none ' +
                    'hover:bg-slate-200';
                removeBtn.addEventListener('click', function () {
                    selectedIds.delete(id);
                    renderSelectedTeachers();
                    renderTeacherResults();
                });

                chip.appendChild(label);
                chip.appendChild(removeBtn);

                // Hidden input for form submit
                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'teacher_ids[]';
                hidden.value = id;

                selectedWrapper.appendChild(chip);
                selectedWrapper.appendChild(hidden);
            });
        }

        function renderTeacherResults() {
            teacherResults.innerHTML = '';

            const term = (teacherSearch.value || '').toLowerCase().trim();

            // nothing typed → show hint only
            if (!term || term.length < 1) {
                const hint = document.createElement('div');
                hint.className = 'text-[10px] text-slate-400';
                hint.textContent = 'Type teacher name above to search…';
                teacherResults.appendChild(hint);
                return;
            }

            let filtered = allTeachers.filter(t => !selectedIds.has(t.id));
            filtered = filtered.filter(t => t.label.toLowerCase().includes(term));

            if (!filtered.length) {
                const empty = document.createElement('div');
                empty.className = 'text-[10px] text-slate-400';
                empty.textContent = 'No teacher found for this search.';
                teacherResults.appendChild(empty);
                return;
            }

            // show first 10 matches
            filtered.slice(0, 10).forEach(t => {
                const row = document.createElement('div');
                row.className =
                    'flex items-center justify-between rounded-md px-1.5 py-1 ' +
                    'hover:bg-slate-100 cursor-default';

                const label = document.createElement('span');
                label.className = 'text-[11px] text-slate-800';
                label.textContent = t.label;

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className =
                    'ml-2 inline-flex items-center justify-center rounded-full border border-emerald-500 ' +
                    'px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ' +
                    'hover:bg-emerald-50';
                addBtn.innerHTML = '+';

                addBtn.addEventListener('click', function () {
                    selectedIds.add(t.id);
                    teacherSearch.value = ''; // clear search
                    renderSelectedTeachers();
                    renderTeacherResults();
                });

                row.appendChild(label);
                row.appendChild(addBtn);
                teacherResults.appendChild(row);
            });
        }

        if (teacherSearch && teacherResults && selectedWrapper) {
            teacherSearch.addEventListener('input', renderTeacherResults);
            renderSelectedTeachers();
            renderTeacherResults();
        }

        // ---------- periods by shift ----------
        let startOptionsOriginal = [];
        let endOptionsOriginal   = [];

        if (startPeriodSelect && endPeriodSelect) {
            startOptionsOriginal = Array.from(startPeriodSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                shift: (opt.getAttribute('data-shift') || '').toLowerCase()
            }));

            endOptionsOriginal = Array.from(endPeriodSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                shift: (opt.getAttribute('data-shift') || '').toLowerCase()
            }));
        }

        function filterPeriodsByShift() {
            if (!shiftSelect || !startPeriodSelect || !endPeriodSelect) return;

            const shift = (shiftSelect.value || '').toLowerCase();
            startPeriodSelect.value = '';
            endPeriodSelect.value   = '';

            startPeriodSelect.innerHTML = '';
            startOptionsOriginal.forEach(opt => {
                if (!opt.value || !shift || opt.shift === shift) {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.text;
                    o.setAttribute('data-shift', opt.shift);
                    startPeriodSelect.appendChild(o);
                }
            });

            endPeriodSelect.innerHTML = '';
            endOptionsOriginal.forEach(opt => {
                if (!opt.value || !shift || opt.shift === shift) {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.text;
                    o.setAttribute('data-shift', opt.shift);
                    endPeriodSelect.appendChild(o);
                }
            });
        }

        if (shiftSelect) {
            shiftSelect.addEventListener('change', filterPeriodsByShift);
        }

        function filterEndByStart() {
            if (!startPeriodSelect || !endPeriodSelect) return;

            const startVal = startPeriodSelect.value;
            if (!startVal) {
                endPeriodSelect.value = '';
                return;
            }

            const shift      = (shiftSelect?.value || '').toLowerCase();
            const startIndex = startOptionsOriginal.findIndex(o => o.value === startVal);

            endPeriodSelect.innerHTML = '';
            endOptionsOriginal.forEach((opt, idx) => {
                if (!opt.value) {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.text;
                    endPeriodSelect.appendChild(o);
                } else if (idx >= startIndex && (!shift || opt.shift === shift)) {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.text;
                    o.setAttribute('data-shift', opt.shift);
                    endPeriodSelect.appendChild(o);
                }
            });
        }

        if (startPeriodSelect) {
            startPeriodSelect.addEventListener('change', filterEndByStart);
        }

        // ---------- meta load (faculty + sem + subject_batch) ----------
        function loadMeta() {
            if (!facultySelect || !semesterSelect || !sectionSelect || !subjectSelect) return;

            const facultyId = facultySelect.value;
            const sem       = semesterSelect.value;
            const batch     = batchSelect ? batchSelect.value : '';
            const subjBatch = subjectBatchSelect ? subjectBatchSelect.value : '';

            sectionSelect.innerHTML =
                '<option value="">Select faculty, semester & subject batch first</option>';
            subjectSelect.innerHTML =
                '<option value="">Select faculty, semester & subject batch first</option>';

            if (!facultyId || !sem || !subjBatch) return;

            const url = '/admin/routines/meta' +
                '?faculty_id=' + encodeURIComponent(facultyId) +
                '&semester=' + encodeURIComponent(sem) +
                '&batch=' + encodeURIComponent(batch || '') +
                '&subject_batch=' + encodeURIComponent(subjBatch);

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(data => {
                    let secHtml = '<option value="">Select section</option>';
                    (data.sections || []).forEach(sec => {
                        secHtml += `<option value="${sec.id}">${sec.name}</option>`;
                    });
                    sectionSelect.innerHTML = secHtml;

                    let subHtml = '<option value="">Select subject</option>';
                    (data.subjects || []).forEach(sub => {
                        subHtml += `<option value="${sub.id}" data-has-practical="${sub.has_practical ? 1 : 0}">
                            ${sub.code} - ${sub.name}
                        </option>`;
                    });
                    subjectSelect.innerHTML = subHtml;

                    updateTypeBySubject();
                    updateGroupOptions();
                })
                .catch(err => {
                    console.error('Error loading routine meta:', err);
                    alert('Failed to load sections/subjects. Check console for details.');
                });
        }

        if (facultySelect && semesterSelect) {
            facultySelect.addEventListener('change', loadMeta);
            semesterSelect.addEventListener('change', loadMeta);
            if (batchSelect)        batchSelect.addEventListener('change', loadMeta);
            if (subjectBatchSelect) subjectBatchSelect.addEventListener('change', loadMeta);
        }

        // ---------- subject -> TH/PR ----------
        function updateTypeBySubject() {
            if (!subjectSelect || !typeSelect) return;
            const selected     = subjectSelect.options[subjectSelect.selectedIndex];
            const hasPractical = selected && selected.getAttribute('data-has-practical') === '1';
            const prOption     = typeSelect.querySelector('option[value="PR"]');

            if (!hasPractical) {
                if (prOption) prOption.disabled = true;
                typeSelect.value = 'TH';
                if (typeHint) typeHint.textContent = '(no practical for this subject)';
            } else {
                if (prOption) prOption.disabled = false;
                if (typeHint) typeHint.textContent = '(TH or PR allowed)';
            }
        }

        // ---------- section + type -> group ----------
        function updateGroupOptions() {
            if (!groupSelect) return;

            const sectionText = sectionSelect && sectionSelect.selectedIndex > -1
                ? sectionSelect.options[sectionSelect.selectedIndex].text.trim()
                : '';

            const typeVal = typeSelect ? typeSelect.value : 'TH';

            groupSelect.innerHTML = '';

            if (typeVal === 'TH') {
                const optAll = document.createElement('option');
                optAll.value = 'ALL';
                optAll.textContent = 'ALL (Theory combined)';
                groupSelect.appendChild(optAll);
                groupSelect.value    = 'ALL';
                groupSelect.disabled = false;
                return;
            }

            const letters       = sectionText.replace(/[^A-Za-z]/g, '').split('');
            const uniqueLetters = [...new Set(letters)].slice(0, 4);

            if (!uniqueLetters.length) {
                ['A', 'B'].forEach(l => {
                    const o = document.createElement('option');
                    o.value = l;
                    o.textContent = l;
                    groupSelect.appendChild(o);
                });
            } else {
                uniqueLetters.forEach(l => {
                    const o = document.createElement('option');
                    o.value = l.toUpperCase();
                    o.textContent = l.toUpperCase();
                    groupSelect.appendChild(o);
                });
            }

            groupSelect.disabled = false;
        }

        if (subjectSelect) subjectSelect.addEventListener('change', updateTypeBySubject);
        if (sectionSelect) sectionSelect.addEventListener('change', updateGroupOptions);
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                updateTypeBySubject();
                updateGroupOptions();
            });
        }

        // initial
        if (sectionSelect) updateGroupOptions();
        if (subjectSelect) updateTypeBySubject();
    });
</script>

@endsection

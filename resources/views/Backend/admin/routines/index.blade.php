@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">

    {{-- Flash messages --}}
    @if(session('ok'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('ok') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
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
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.routines.index') }}"
              class="grid grid-cols-1 sm:grid-cols-6 gap-3 sm:gap-4 text-[11px]">

            {{-- Faculty --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Faculty</label>
                <select name="faculty_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" @selected($filters['faculty_id']==$f->id)>
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
                    @foreach($batches as $b)
                        <option value="{{ $b }}" @selected($filters['batch']==$b)>{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Semester --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Semester</label>
                <select name="semester"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All</option>
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}" @selected($filters['semester']==$sem)>{{ $sem }}</option>
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
                    @foreach($sections as $s)
                        <option value="{{ $s->id }}" @selected($filters['section_id']==$s->id)>
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
        <option value="morning" @selected($filters['shift']==='morning')>Morning</option>
        <option value="day"     @selected($filters['shift']==='day')>Day</option>
    </select>
</div>


            {{-- Day --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Day</label>
                <select name="day_of_week"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All</option>
                    @foreach($days as $k => $v)
                        <option value="{{ $k }}" @selected($filters['day_of_week']==$k)>{{ $v }}</option>
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
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected($filters['teacher_id']==$t->id)>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-6 flex justify-end gap-2 pt-1">
                <button type="submit"
                        class="rounded-lg bg-slate-900 px-3.5 py-1.5 text-[11px] font-semibold text-white
                               hover:bg-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    Filter
                </button>
                <a href="{{ route('admin.routines.index') }}"
                   class="rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-[11px] font-medium
                          text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-1 focus:ring-slate-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Create form --}}
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm">
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
                    @foreach($batches as $b)
                        <option value="{{ $b }}" @selected(old('batch', $filters['batch'])==$b)>{{ $b }}</option>
                    @endforeach
                </select>
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
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" @selected(old('faculty_id', $filters['faculty_id'])==$f->id)>
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
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}" @selected(old('semester', $filters['semester'])==$sem)>{{ $sem }}</option>
                    @endforeach
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

            {{-- Shift --}}
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

            {{-- Day --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-800">
                    Day <span class="text-red-500">*</span>
                </label>
                <select name="day_of_week"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                    @foreach($days as $k => $v)
                        <option value="{{ $k }}" @selected(old('day_of_week')==$k)>{{ $v }}</option>
                    @endforeach
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
        @foreach($periods as $p)
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
        @foreach($periods as $p)
            @php
                $normalizedShift = strtolower(trim($p->shift ?? ''));
            @endphp
            <option value="{{ $p->id }}" data-shift="{{ $normalizedShift }}">
                {{ $p->label }} ({{ $p->start_time }}–{{ $p->end_time }})
            </option>
        @endforeach
    </select>
</div>



            {{-- Group --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-800">
                    Group <span class="text-red-500">*</span>
                </label>
                <select name="group"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                    <option value="ALL" @selected(old('group')=='ALL')>ALL (Theory combined)</option>
                    <option value="A"   @selected(old('group')=='A')>A (Practical)</option>
                    <option value="B"   @selected(old('group')=='B')>B (Practical)</option>
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
                    <option value="TH" @selected(old('type')=='TH')>TH (Theory)</option>
                    <option value="PR" @selected(old('type')=='PR')>PR (Practical)</option>
                </select>
            </div>

            {{-- Teacher search + select (all teachers, client-side search) --}}
            <div class="space-y-1 sm:col-span-2">
                <label class="block font-medium text-slate-800">
                    Teacher <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="teacher_search"
                       placeholder="Type to search teacher..."
                       class="mb-1 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                              focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <select name="teacher_id" id="teacher_select"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required>
                    <option value="">Select teacher</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected(old('teacher_id')==$t->id)>
                            {{ $t->name }} @if($t->faculty?->code) ({{ $t->faculty->code }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Room --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Room</label>
                <select name="room_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                               focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">--</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}" @selected(old('room_id')==$r->id)>
                            {{ $r->room_no }}
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
        $periodCount   = $periods->count();
    @endphp

{{-- Routine Grid --}}
{{-- Routine Grid --}}
<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between text-[11px] text-slate-600">
        <h2 class="text-[13px] font-semibold text-slate-900">Routine Grid</h2>
        <span>Total: <span class="font-semibold text-slate-900">{{ $routines->total() }}</span></span>
    </div>

    @php
        $periodCount = $gridPeriods->count();
        $periodIndex = [];
        foreach ($gridPeriods as $idx => $p) {
            $periodIndex[$p->id] = $idx; // map period_id → index
        }

        // colour palette
        $palette = [
            'bg-cyan-50 border-cyan-400 text-cyan-900',
            'bg-emerald-50 border-emerald-400 text-emerald-900',
            'bg-indigo-50 border-indigo-400 text-indigo-900',
            'bg-purple-50 border-purple-400 text-purple-900',
            'bg-pink-50 border-pink-400 text-pink-900',
            'bg-amber-50 border-amber-400 text-amber-900',
            'bg-blue-50 border-blue-400 text-blue-900',
            'bg-red-50 border-red-400 text-red-900',
        ];
    @endphp

    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full border-collapse text-[11px]">
            <thead>
            <tr class="bg-gradient-to-r from-slate-50 to-slate-100">
                <th class="border-b-2 border-slate-300 px-3 py-2.5 text-left align-middle font-semibold text-slate-700">
                    Days
                </th>
                <th class="border-b-2 border-slate-300 px-3 py-2.5 text-center align-middle font-semibold text-slate-700">
                    Sem
                </th>
                @foreach($gridPeriods as $p)
                    <th class="border-b-2 border-slate-300 px-2 py-2.5 text-center align-middle">
                        <div class="font-semibold text-slate-800">{{ $p->order }}</div>
                        <div class="mt-0.5 text-[10px] text-slate-500">
                            {{ \Carbon\Carbon::parse($p->start_time)->format('g:i') }}
                            –
                            {{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}
                        </div>
                    </th>
                @endforeach
            </tr>
            </thead>

            <tbody>
            @foreach($days as $dayKey => $dayLabel)
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
                                $r->group      ?? '',
                                $r->type       ?? '',
                                $r->room_id    ?? 0,
                            ]);

                            // extend previous segment with same key if consecutive
                            $extended = false;
                            foreach ($segments as &$sg) {
                                if ($sg['key'] === $segKey && $sg['end'] === $idx - 1) {
                                    $sg['end'] = $idx;
                                    $extended  = true;
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

                    // sort by start index
                    usort($segments, function ($a, $b) {
                        return $a['start'] <=> $b['start'];
                    });

                    // 2) Assign segments to lanes (no overlap inside a lane)
                    $lanes = []; // each lane = array of segments
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
                        // at least one empty lane so day still shows
                        $lanes = [[]];
                    }

                    $laneCount = count($lanes);
                @endphp

                @for($laneIndex = 0; $laneIndex < $laneCount; $laneIndex++)
                    @php
                        $lane = $lanes[$laneIndex];

                        // map start index → segment for quick lookup when rendering cells
                        $startMap = [];
                        foreach ($lane as $seg) {
                            $startMap[$seg['start']] = $seg;
                        }
                    @endphp

                    <tr class="border-t border-slate-200 hover:bg-slate-50/40 transition-colors">
                        @if($laneIndex === 0)
                            {{-- Day & Sem cells span all lanes --}}
                            <td class="border-r border-slate-200 px-3 py-3 font-semibold text-slate-700 bg-slate-50/50"
                                rowspan="{{ $laneCount }}">
                                <span class="hidden sm:inline">{{ $dayLabel }}</span>
                                <span class="sm:hidden">{{ substr($dayLabel, 0, 3) }}</span>
                            </td>
                            <td class="border-r border-slate-200 px-3 py-3 text-center text-slate-700 bg-slate-50/30 font-medium"
                                rowspan="{{ $laneCount }}">
                                {{ $filters['semester'] ?: '-' }}
                            </td>
                        @endif

                        {{-- Period cells for this lane --}}
                        @php $i = 0; @endphp
                        @while($i < $periodCount)
                            @php
                                $seg = $startMap[$i] ?? null;
                            @endphp

                            @if($seg)
                                @php
                                    $span    = $seg['end'] - $seg['start'] + 1;
                                    $cell    = $seg['routine'];
                                    $subId   = $cell->subject_id ?? 0;
                                    $color   = $palette[$subId % count($palette)];

                                    // initials
                                    $nameParts = explode(' ', trim($cell->teacher->name ?? ''));
                                    $initials  = '';
                                    foreach ($nameParts as $part) {
                                        if ($part !== '') {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                    }
                                @endphp

                                <td colspan="{{ $span }}" class="px-2 py-2.5 align-top border-l border-slate-200 bg-white">
                                    <div class="rounded-xl border-l-4 {{ $color }} px-3 py-2 text-[10px] leading-snug shadow-[0_0_0_1px_rgba(15,23,42,0.02)]">
                                        {{-- SUBJECT NAME --}}
                                        <div class="text-[11px] font-semibold leading-tight">
                                            {{ $cell->subject->name ?? '' }}
                                        </div>

                                        {{-- Teacher + group + type + room --}}
                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                                            @if($initials)
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/80 text-[9px] font-bold">
                                                    {{ $initials }}
                                                </span>
                                            @endif

                                            <span class="text-slate-800">
                                                {{ $cell->teacher->name ?? '' }}
                                            </span>

                                            <span class="text-slate-500">· {{ $cell->type }}</span>

                                            @if($cell->group && $cell->group !== 'ALL')
                                                <span class="text-slate-500">· Group {{ $cell->group }}</span>
                                            @endif

                                            @if($cell->room)
                                                <span class="text-slate-500">· Rm {{ $cell->room->room_no }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                @php $i += $span; @endphp
                            @else
                                {{-- empty period in this lane --}}
                                <td class="px-2 py-2.5 border-l border-slate-100 bg-white">
                                    <div class="h-10"></div>
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

    {{-- Legend --}}
    <div class="mt-3 pt-3 border-t border-slate-200">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[10px] text-slate-600">
            <div class="flex items-center gap-1.5">
                <span class="font-semibold">TH</span><span>= Theory</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="font-semibold">PR</span><span>= Practical</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="font-semibold">Grp A/B</span><span>= Group divisions</span>
            </div>
        </div>
    </div>
</div>



{{-- Responsive notice for mobile --}}
<div class="mt-2 sm:hidden">
    <div class="text-[10px] text-slate-500 text-center bg-slate-50 rounded-lg px-3 py-2 border border-slate-200">
        <span class="inline-block mr-1">💡</span>
        Swipe left/right to view all periods. Tap on a class for full details.
    </div>
</div>

</div>

{{-- JS: shift → periods, faculty+sem(+batch) → sections+subjects (AJAX), subject → TH/PR, teacher search --}}





<script>
document.addEventListener('DOMContentLoaded', function () {
    const shiftSelect        = document.getElementById('shift_select');
    const startPeriodSelect  = document.getElementById('start_period_select');
    const endPeriodSelect    = document.getElementById('end_period_select');

    const facultySelect  = document.getElementById('faculty_select');
    const semesterSelect = document.getElementById('semester_select');
    const batchSelect    = document.getElementById('batch_select');
    const sectionSelect  = document.getElementById('section_select');
    const subjectSelect  = document.getElementById('subject_select');

    const typeSelect = document.getElementById('type_select');
    const typeHint   = document.getElementById('type_hint');

    const teacherSearch = document.getElementById('teacher_search');
    const teacherSelect = document.getElementById('teacher_select');

    // Store original options
    let startOptionsOriginal = [];
    let endOptionsOriginal = [];
    let teacherOptionsOriginal = [];
    
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

    // Store original teacher options for search
    if (teacherSelect) {
        teacherOptionsOriginal = Array.from(teacherSelect.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    }

    // --- 1) Shift -> filter start/end periods ---
    function filterPeriodsByShift() {
        if (!shiftSelect || !startPeriodSelect || !endPeriodSelect) {
            console.log('Missing elements:', {
                shiftSelect: !!shiftSelect,
                startPeriodSelect: !!startPeriodSelect,
                endPeriodSelect: !!endPeriodSelect
            });
            return;
        }

        const shift = (shiftSelect.value || '').toLowerCase();
        console.log('Selected shift:', shift);
        console.log('Original start options:', startOptionsOriginal.length);

        // Clear current selections
        startPeriodSelect.value = '';
        endPeriodSelect.value = '';

        // Rebuild start period options
        startPeriodSelect.innerHTML = '';
        let addedStart = 0;
        startOptionsOriginal.forEach(opt => {
            if (!opt.value || !shift || opt.shift === shift) {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                option.setAttribute('data-shift', opt.shift);
                startPeriodSelect.appendChild(option);
                addedStart++;
            }
        });
        console.log('Added start period options:', addedStart);

        // Rebuild end period options
        endPeriodSelect.innerHTML = '';
        let addedEnd = 0;
        endOptionsOriginal.forEach(opt => {
            if (!opt.value || !shift || opt.shift === shift) {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                option.setAttribute('data-shift', opt.shift);
                endPeriodSelect.appendChild(option);
                addedEnd++;
            }
        });
        console.log('Added end period options:', addedEnd);
    }

    if (shiftSelect) {
        shiftSelect.addEventListener('change', filterPeriodsByShift);
    }

    // ensure end_period >= start_period (same shift)
    function filterEndByStart() {
        if (!startPeriodSelect || !endPeriodSelect) return;

        const startVal = startPeriodSelect.value;
        if (!startVal) {
            endPeriodSelect.value = '';
            return;
        }

        const shift = (shiftSelect.value || '').toLowerCase();
        
        // Find start index in original array
        const startIndex = startOptionsOriginal.findIndex(o => o.value === startVal);

        // Rebuild end period with only valid options
        endPeriodSelect.innerHTML = '';
        endOptionsOriginal.forEach((opt, idx) => {
            if (!opt.value) {
                // Add placeholder
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                endPeriodSelect.appendChild(option);
            } else if (idx >= startIndex && (!shift || opt.shift === shift)) {
                // Add valid options (same or after start, matching shift)
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                option.setAttribute('data-shift', opt.shift);
                endPeriodSelect.appendChild(option);
            }
        });
    }

    if (startPeriodSelect) {
        startPeriodSelect.addEventListener('change', filterEndByStart);
    }

    // --- 2) Faculty + Semester (+Batch) -> AJAX load sections + subjects ---
    function loadMeta() {
        if (!facultySelect || !semesterSelect || !sectionSelect || !subjectSelect) return;

        const facultyId = facultySelect.value;
        const sem       = semesterSelect.value;
        const batch     = batchSelect ? batchSelect.value : '';

        sectionSelect.innerHTML = '<option value="">Select faculty & semester first</option>';
        subjectSelect.innerHTML = '<option value="">Select faculty & semester first</option>';

        if (!facultyId || !sem) return;

        console.log('Loading meta for:', { facultyId, sem, batch });

        // Correct URL path
        const url = '/admin/routines/meta'
            + '?faculty_id=' + encodeURIComponent(facultyId)
            + '&semester=' + encodeURIComponent(sem)
            + '&batch=' + encodeURIComponent(batch || '');

        console.log('Fetching from:', url);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => {
                console.log('Response status:', res.status);
                return res.json();
            })
            .then(data => {
                console.log('Received data:', data);

                // Sections
                let secHtml = '<option value="">Select section</option>';
                (data.sections || []).forEach(sec => {
                    secHtml += `<option value="${sec.id}">${sec.name}</option>`;
                });
                sectionSelect.innerHTML = secHtml;
                console.log('Loaded sections:', data.sections?.length || 0);

                // Subjects
                let subHtml = '<option value="">Select subject</option>';
                (data.subjects || []).forEach(sub => {
                    subHtml += `<option value="${sub.id}" data-has-practical="${sub.has_practical ? 1 : 0}">
                                    ${sub.code} - ${sub.name}
                                </option>`;
                });
                subjectSelect.innerHTML = subHtml;
                console.log('Loaded subjects:', data.subjects?.length || 0);

                updateTypeBySubject();
            })
            .catch(err => {
                console.error('Error loading routine meta:', err);
                alert('Failed to load sections/subjects. Check console for details.');
            });
    }

    if (facultySelect && semesterSelect) {
        facultySelect.addEventListener('change', loadMeta);
        semesterSelect.addEventListener('change', loadMeta);
        if (batchSelect) batchSelect.addEventListener('change', loadMeta);
    }

    // --- 3) Subject -> limit TH/PR based on has_practical ---
    function updateTypeBySubject() {
        if (!subjectSelect || !typeSelect) return;
        const selected = subjectSelect.options[subjectSelect.selectedIndex];
        const hasPractical = selected && selected.getAttribute('data-has-practical') === '1';
        const prOption = typeSelect.querySelector('option[value="PR"]');

        if (!hasPractical) {
            if (prOption) prOption.disabled = true;
            typeSelect.value = 'TH';
            if (typeHint) typeHint.textContent = '(no practical for this subject)';
        } else {
            if (prOption) prOption.disabled = false;
            if (typeHint) typeHint.textContent = '(TH or PR allowed)';
        }
    }

    if (subjectSelect) {
        subjectSelect.addEventListener('change', updateTypeBySubject);
    }

    // --- 4) Teacher search (client-side filtering) ---
    function filterTeachers() {
        if (!teacherSearch || !teacherSelect) return;
        const term = (teacherSearch.value || '').toLowerCase().trim();

        // Rebuild the select with filtered options
        teacherSelect.innerHTML = '';
        
        teacherOptionsOriginal.forEach(opt => {
            if (!opt.value) {
                // Always show placeholder
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                teacherSelect.appendChild(option);
            } else if (!term || opt.text.toLowerCase().includes(term)) {
                // Show matching options or all if no search term
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                teacherSelect.appendChild(option);
            }
        });

        // If only one result (plus placeholder), auto-select it
        if (teacherSelect.options.length === 2 && term) {
            teacherSelect.selectedIndex = 1;
        }
    }

    if (teacherSearch && teacherSelect) {
        teacherSearch.addEventListener('input', filterTeachers);
        
        // Clear search when teacher is manually selected
        teacherSelect.addEventListener('change', function() {
            if (this.value && teacherSearch) {
                const selectedText = this.options[this.selectedIndex].text;
                teacherSearch.value = selectedText;
            }
        });
    }
});
</script>
@endsection

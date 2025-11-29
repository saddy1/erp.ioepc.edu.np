@extends('Backend.layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-3 sm:p-4 text-xs">

    @if($errors->any())
        <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-base sm:text-lg font-semibold mb-3">Edit Routine Entry</h1>

    {{-- ===== Current Slot Summary ===== --}}
    @php
        $shift  = $routine->period?->shift;
        $pLabel = $routine->period?->label;
        $pStart = $routine->period?->start_time;
        $pEnd   = $routine->period?->end_time;
    @endphp

    <div class="mb-4 rounded-2xl border border-slate-900 bg-slate-900/95 text-[11px] text-slate-100 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2 mb-2">
            <div class="font-semibold tracking-wide text-[12px]">
                Current Slot Overview
            </div>
            <div class="flex flex-wrap gap-1.5">
                @if($routine->type)
                    <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] border border-emerald-400/60">
                        {{ $routine->type === 'TH' ? 'Theory' : 'Practical' }}
                    </span>
                @endif
                @if($routine->group)
                    <span class="inline-flex items-center rounded-full bg-sky-500/15 px-2 py-0.5 text-[10px] border border-sky-400/60">
                        Group: {{ $routine->group }}
                    </span>
                @endif
                @if($shift)
                    <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] border border-amber-400/60">
                        Shift: {{ ucfirst($shift) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6">
            <div>
                <div class="text-[10px] text-slate-300 uppercase tracking-wide">Faculty</div>
                <div class="font-medium">
                    {{ $routine->faculty?->code ? $routine->faculty->code . ' - ' : '' }}{{ $routine->faculty?->name }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <div class="text-[10px] text-slate-300 uppercase tracking-wide">Batch</div>
                    <div class="font-medium">{{ $routine->batch }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-300 uppercase tracking-wide">Semester</div>
                    <div class="font-medium">{{ $routine->semester }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-300 uppercase tracking-wide">Section</div>
                    <div class="font-medium">{{ $routine->section?->name }}</div>
                </div>
            </div>

            <div>
                <div class="text-[10px] text-slate-300 uppercase tracking-wide">Day</div>
                <div class="font-medium">
                    {{ $days[$routine->day_of_week] ?? strtoupper($routine->day_of_week) }}
                </div>
            </div>

            <div>
                <div class="text-[10px] text-slate-300 uppercase tracking-wide">Period</div>
                <div class="font-medium">
                    @if($pLabel)
                        {{ $pLabel }}
                        @if($pStart && $pEnd)
                            <span class="text-[10px] text-slate-300">
                                ({{ $pStart }} – {{ $pEnd }})
                            </span>
                        @endif
                    @else
                        -
                    @endif
                </div>
            </div>

            <div class="sm:col-span-2">
                <div class="text-[10px] text-slate-300 uppercase tracking-wide">Subject</div>
                <div class="font-medium">
                    @if($routine->subject)
                        {{ $routine->subject->code }} - {{ $routine->subject->name }}
                    @else
                        -
                    @endif
                </div>
            </div>

            <div>
                <div class="text-[10px] text-slate-300 uppercase tracking-wide">Room</div>
                <div class="font-medium">
                    {{ $routine->room?->room_no ?? 'Not assigned' }}
                </div>
            </div>

            <div>
                <div class="text-[10px] text-slate-300 uppercase tracking-wide">Academic Year</div>
                <div class="font-medium">
                    {{ $routine->academic_year ?? '-' }}
                </div>
            </div>

            <div class="sm:col-span-2 mt-1">
                <div class="text-[10px] text-slate-300 uppercase tracking-wide mb-1">Assigned Teachers</div>
                <div class="flex flex-wrap gap-1.5">
                    @php
                        $assignedTeachers = $routine->teachers ?? collect();
                    @endphp

                    @if($assignedTeachers->isEmpty())
                        <span class="text-[10px] text-slate-400">No teacher assigned.</span>
                    @else
                        @foreach($assignedTeachers as $t)
                            @php
                                $label = $t->name . ($t->faculty?->code ? ' ('.$t->faculty->code.')' : '');
                            @endphp
                            <span class="inline-flex items-center rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] border border-slate-600">
                                {{ $label }}
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- ===== /Current Slot Summary ===== --}}

    <form method="POST"
          action="{{ route('admin.routines.update', $routine) }}"
          class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px] rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">

        @csrf
        @method('PUT')

        {{-- Faculty --}}
        <div>
            <label class="block mb-1 font-medium">Faculty</label>
            <select name="faculty_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($faculties as $f)
                    <option value="{{ $f->id }}" @selected(old('faculty_id', $routine->faculty_id) == $f->id)>
                        {{ $f->code ?? '' }} {{ $f->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Batch --}}
        <div>
            <label class="block mb-1 font-medium">Batch</label>
            <select name="batch" class="w-full border-gray-300 rounded-lg" required>
                @foreach($batches as $b)
                    <option value="{{ $b }}" @selected(old('batch', $routine->batch) == $b)>{{ $b }}</option>
                @endforeach
            </select>
        </div>

        {{-- Semester --}}
        <div>
            <label class="block mb-1 font-medium">Semester</label>
            <select name="semester" class="w-full border-gray-300 rounded-lg" required>
                @foreach($semesters as $sem)
                    <option value="{{ $sem }}" @selected(old('semester', $routine->semester) == $sem)>{{ $sem }}</option>
                @endforeach
            </select>
        </div>

        {{-- Section --}}
        <div>
            <label class="block mb-1 font-medium">Section</label>
            <select name="section_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($sections as $s)
                    <option value="{{ $s->id }}" @selected(old('section_id', $routine->section_id) == $s->id)>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Day --}}
        <div>
            <label class="block mb-1 font-medium">Day</label>
            <select name="day_of_week" class="w-full border-gray-300 rounded-lg" required>
                @foreach($days as $k => $v)
                    <option value="{{ $k }}" @selected(old('day_of_week', $routine->day_of_week) == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>

        {{-- Period --}}
        <div>
            <label class="block mb-1 font-medium">Period</label>
            <select name="period_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" @selected(old('period_id', $routine->period_id) == $p->id)>
                        {{ ucfirst($p->shift) }} - {{ $p->label }}
                        ({{ $p->start_time }}–{{ $p->end_time }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Group --}}
        <div>
            <label class="block mb-1 font-medium">Group</label>
            <select name="group" class="w-full border-gray-300 rounded-lg" required>
                <option value="ALL" @selected(old('group', $routine->group) == 'ALL')>ALL (Theory combined)</option>
                <option value="A"   @selected(old('group', $routine->group) == 'A')>A (Practical)</option>
                <option value="B"   @selected(old('group', $routine->group) == 'B')>B (Practical)</option>
            </select>
        </div>

        {{-- Type --}}
        <div>
            <label class="block mb-1 font-medium">Type</label>
            <select name="type" class="w-full border-gray-300 rounded-lg" required>
                <option value="TH" @selected(old('type', $routine->type) == 'TH')>TH</option>
                <option value="PR" @selected(old('type', $routine->type) == 'PR')>PR</option>
            </select>
        </div>

        {{-- Subject --}}
        <div>
            <label class="block mb-1 font-medium">Subject</label>
            <select name="subject_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($subjects as $sub)
                    <option value="{{ $sub->id }}" @selected(old('subject_id', $routine->subject_id) == $sub->id)>
                        {{ $sub->code }} - {{ $sub->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Teachers (multi-select with search + chips) --}}
        <div class="sm:col-span-1">
            <label class="block mb-1 font-medium">Teachers</label>

            <input type="text" id="teacher_search"
                   placeholder="Type to search teacher..."
                   class="mb-1 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5
                          focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">

            <div id="teacher_results"
                 class="max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/60 px-2 py-1.5 text-[11px]">
                <div class="text-[10px] text-slate-500">Type above to search teachers…</div>
            </div>

            <div id="selected_teachers"
                 class="mt-2 flex flex-wrap gap-1.5 text-[10px]">
                {{-- JS will render chips + hidden inputs (teacher_ids[]) here --}}
            </div>
        </div>

        {{-- Room --}}
        <div>
            <label class="block mb-1 font-medium">Room</label>
            <select name="room_id" class="w-full border-gray-300 rounded-lg">
                <option value="">--</option>
                @foreach($rooms as $r)
                    <option value="{{ $r->id }}" @selected(old('room_id', $routine->room_id) == $r->id)>
                        {{ $r->room_no }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Academic Year --}}
        <div>
            <label class="block mb-1 font-medium">Academic Year</label>
            <input type="text" name="academic_year"
                   value="{{ old('academic_year', $routine->academic_year) }}"
                   class="w-full border-gray-300 rounded-lg"
                   placeholder="2081/82">
        </div>

        <div class="sm:col-span-2 flex justify-between items-center pt-2">
            <a href="{{ route('admin.routines.index') }}"
               class="px-3 py-1.5 rounded-lg border border-gray-300 text-[11px]">
                Back
            </a>
            <button type="submit"
                    class="px-4 py-1.5 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold">
                Save Changes
            </button>
        </div>
    </form>
</div>

{{-- Expose teachers for JS --}}
@php
    $teacherOptions = $teachers->map(function($t) {
        return [
            'id'    => $t->id,
            'name'  => $t->name,
            'label' => $t->name . ($t->faculty?->code ? ' ('.$t->faculty->code.')' : ''),
        ];
    })->values();

    $preselectedTeacherIds = old(
        'teacher_ids',
        ($routine->teachers ?? collect())->pluck('id')->toArray()
    );
@endphp

<script>
    window.ALL_TEACHERS = @json($teacherOptions);
    window.PRESELECTED_TEACHERS = @json($preselectedTeacherIds);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const teacherSearch   = document.getElementById('teacher_search');
    const teacherResults  = document.getElementById('teacher_results');
    const selectedWrapper = document.getElementById('selected_teachers');

    const allTeachers = Array.isArray(window.ALL_TEACHERS) ? window.ALL_TEACHERS : [];
    const preselected = Array.isArray(window.PRESELECTED_TEACHERS) ? window.PRESELECTED_TEACHERS : [];

    const selectedIds = new Set(preselected.map(id => parseInt(id, 10)));

    function renderSelectedTeachers() {
        if (!selectedWrapper) return;
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
                'ml-1 rounded-full border border-slate-400 px-1 text-[10px] leading-none hover:bg-slate-200';

            removeBtn.addEventListener('click', function () {
                selectedIds.delete(id);
                renderSelectedTeachers();
                renderTeacherResults();
            });

            chip.appendChild(label);
            chip.appendChild(removeBtn);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'teacher_ids[]';
            hidden.value = id;

            selectedWrapper.appendChild(chip);
            selectedWrapper.appendChild(hidden);
        });
    }

    function renderTeacherResults() {
        if (!teacherResults) return;

        const term = (teacherSearch?.value || '').toLowerCase().trim();
        teacherResults.innerHTML = '';

        let filtered = allTeachers.filter(t => !selectedIds.has(t.id));
        if (term) {
            filtered = filtered.filter(t => t.label.toLowerCase().includes(term));
        }

        if (!filtered.length) {
            const empty = document.createElement('div');
            empty.className = 'text-[10px] text-slate-400';
            empty.textContent = term
                ? 'No teacher found for this search.'
                : 'Type above to search teachers…';
            teacherResults.appendChild(empty);
            return;
        }

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
                'px-2 py-0.5 text-[10px] font-semibold text-emerald-700 hover:bg-emerald-50';
            addBtn.innerHTML = '+';

            addBtn.addEventListener('click', function () {
                selectedIds.add(t.id);
                if (teacherSearch) teacherSearch.value = '';
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
});
</script>
@endsection

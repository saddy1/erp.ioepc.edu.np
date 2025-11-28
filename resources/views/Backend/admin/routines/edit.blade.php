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

    <form method="POST" action="{{ route('admin.routines.update', $routine) }}"
          class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px] rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">

        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-medium">Faculty</label>
            <select name="faculty_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($faculties as $f)
                    <option value="{{ $f->id }}" @selected($routine->faculty_id==$f->id)>
                        {{ $f->code ?? '' }} {{ $f->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Batch</label>
            <select name="batch" class="w-full border-gray-300 rounded-lg" required>
                @foreach($batches as $b)
                    <option value="{{ $b }}" @selected($routine->batch==$b)>{{ $b }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Semester</label>
            <select name="semester" class="w-full border-gray-300 rounded-lg" required>
                @foreach($semesters as $sem)
                    <option value="{{ $sem }}" @selected($routine->semester==$sem)>{{ $sem }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Section</label>
            <select name="section_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($sections as $s)
                    <option value="{{ $s->id }}" @selected($routine->section_id==$s->id)>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Day</label>
            <select name="day_of_week" class="w-full border-gray-300 rounded-lg" required>
                @foreach($days as $k => $v)
                    <option value="{{ $k }}" @selected($routine->day_of_week==$k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Period</label>
            <select name="period_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" @selected($routine->period_id==$p->id)>
                        {{ ucfirst($p->shift) }} - {{ $p->label }}
                        ({{ $p->start_time }}–{{ $p->end_time }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Group</label>
            <select name="group" class="w-full border-gray-300 rounded-lg" required>
                <option value="ALL" @selected($routine->group=='ALL')>ALL (Theory combined)</option>
                <option value="A"   @selected($routine->group=='A')>A (Practical)</option>
                <option value="B"   @selected($routine->group=='B')>B (Practical)</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Type</label>
            <select name="type" class="w-full border-gray-300 rounded-lg" required>
                <option value="TH" @selected($routine->type=='TH')>TH</option>
                <option value="PR" @selected($routine->type=='PR')>PR</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Subject</label>
            <select name="subject_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($subjects as $sub)
                    <option value="{{ $sub->id }}" @selected($routine->subject_id==$sub->id)>
                        {{ $sub->code }} - {{ $sub->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Teacher</label>
            <select name="teacher_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" @selected($routine->teacher_id==$t->id)>
                        {{ $t->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Room</label>
            <select name="room_id" class="w-full border-gray-300 rounded-lg">
                <option value="">--</option>
                @foreach($rooms as $r)
                    <option value="{{ $r->id }}" @selected($routine->room_id==$r->id)>
                        {{ $r->room_no }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Academic Year</label>
            <input type="text" name="academic_year"
                   value="{{ $routine->academic_year }}"
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
@endsection

@extends('Backend.layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-4 text-sm">
  @if(session('ok'))
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
      {{ session('ok') }}
    </div>
  @endif
  @if($errors->any())
    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc list-inside text-xs">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <h1 class="text-lg font-semibold mb-4">Bulk Semester Upgrade (Year / Part)</h1>

  <p class="text-[12px] text-gray-600 mb-4">
    Example: Promote all students of <b>Batch</b> <i>2081</i> from <b>1st Year 1st Part</b> (Sem 1)
    to <b>1st Year 2nd Part</b> (Sem 2). Next time you can promote from (1,2) → (2,1), and so on.
  </p>

  <form method="POST" action="{{ route('students.upgrade') }}" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <label class="text-xs text-gray-700 mb-1 block">Batch <span class="text-red-500">*</span></label>
        <input type="text" name="batch" value="{{ old('batch') }}"
               class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="e.g. 2081" required>
      </div>

      <div>
        <label class="text-xs text-gray-700 mb-1 block">From Year <span class="text-red-500">*</span></label>
        <input type="number" name="from_year" value="{{ old('from_year', 1) }}" min="1" max="8"
               class="w-full rounded-lg border px-3 py-2 text-sm" required>
      </div>

      <div>
        <label class="text-xs text-gray-700 mb-1 block">From Part <span class="text-red-500">*</span></label>
        <select name="from_part" class="w-full rounded-lg border px-3 py-2 text-sm" required>
          <option value="1" @selected(old('from_part') == 1)>Part 1</option>
          <option value="2" @selected(old('from_part') == 2)>Part 2</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="text-xs text-gray-700 mb-1 block">Faculty (optional)</label>
        <select name="faculty_id" class="w-full rounded-lg border px-3 py-2 text-sm">
          <option value="">All Faculties</option>
          @foreach($faculties as $f)
            <option value="{{ $f->id }}" @selected(old('faculty_id') == $f->id)>
              {{ $f->code }} — {{ $f->name }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="pt-2">
      <button type="submit"
              class="rounded-xl bg-gray-900 text-white px-6 py-2 text-sm font-semibold hover:bg-gray-800"
              onclick="return confirm('Are you sure you want to upgrade all matching students to the next semester?');">
        Upgrade Semester for Batch
      </button>
    </div>
  </form>
</div>
@endsection

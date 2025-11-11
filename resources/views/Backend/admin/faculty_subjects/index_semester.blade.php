@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">
  
  {{-- ✅ Success Message --}}
  @if(session('ok'))
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 text-xs">
      {{ session('ok') }}
    </div>
  @endif

  {{-- ⚠️ Error Message --}}
  @if($errors->any())
    <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 text-xs">
      <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- 🧭 Header Section --}}
  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="text-base font-semibold text-gray-800">Subjects by Faculty</h1>
      <p class="text-[11px] text-gray-500">Select <b>Semester</b> and <b>Batch</b> to manage subjects</p>
    </div>

    <form class="grid grid-cols-2 gap-2 sm:grid-cols-4" method="GET">
      <div>
        <label class="block text-[10px] text-gray-600 mb-1">Semester</label>
        <input type="number" min="1" max="12" name="semester" value="{{ $semester }}"
               class="w-full rounded border px-2 py-1 text-xs focus:ring-1 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-[10px] text-gray-600 mb-1">Batch</label>
        <select name="batch" class="w-full rounded border px-2 py-1 text-xs focus:ring-1 focus:ring-blue-400">
          <option value="">— Select —</option>
          <option value="1" @selected(($batch ?? null)==1)>New</option>
          <option value="2" @selected(($batch ?? null)==2)>Old</option>
        </select>
      </div>
      <button class="rounded border px-2 py-1 text-xs font-medium bg-gray-100 hover:bg-gray-200">Filter</button>
      <a href="{{ route('faculty_subjects.index') }}"
         class="rounded border px-2 py-1 text-center text-xs font-medium bg-white hover:bg-gray-100">Reset</a>
    </form>
  </div>

  {{-- ⚙️ Batch Not Selected Warning --}}
  @php $batchSelected = !empty($batch); @endphp
  @unless($batchSelected)
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 text-xs">
      Please select a <b>Batch</b> above to add or edit subjects.
    </div>
  @endunless

  {{-- 📘 Faculty Cards Grid - 2 Columns --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    @foreach($faculties as $f)
      <div class="rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow transition p-3">
        
        {{-- Card Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-2">
          <div>
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Faculty</p>
            <p class="font-medium text-gray-800 text-xs">{{ $f->code }} — {{ $f->name }}</p>
          </div>
          <div class="text-right">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Sem / Batch</p>
            <p class="font-medium text-xs">
              {{ $semester ?? '—' }} / {{ ($batch ?? null)==1 ? 'New' : ( ($batch ?? null)==2 ? 'Old' : '—' ) }}
            </p>
          </div>
        </div>

        {{-- Add Subject Form --}}
        <form method="POST" action="{{ route('faculty_subjects.store') }}" class="mb-2 bg-slate-50 rounded p-2 border border-slate-200">
          @csrf
          <input type="hidden" name="faculty_id" value="{{ $f->id }}">
          <input type="hidden" name="semester" value="{{ $semester }}">
          <input type="hidden" name="batch" value="{{ $batch }}">

          <div class="flex items-center gap-1.5">
            <input name="subject_code" placeholder="Code"
                   class="flex-shrink-0 w-20 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-400"
                   @disabled(!$batchSelected) required>
            <input name="subject_name" placeholder="Subject Name"
                   class="flex-1 min-w-0 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-400"
                   @disabled(!$batchSelected) required>
            <button class="flex-shrink-0 rounded bg-slate-700 text-white text-xs font-medium hover:bg-slate-800 px-3 py-1 transition"
                    @disabled(!$batchSelected)
                    title="{{ $batchSelected ? 'Add Subject' : 'Select Batch First' }}">
              + Add
            </button>
          </div>
        </form>

        {{-- Subjects List - Compact Row Display --}}
        <div class="space-y-1">
          @php
            $rows = $f->semesterSubjects
              ->where('semester', $semester)
              ->when($batchSelected, fn($c)=>$c->where('batch', $batch))
              ->values();
          @endphp

          @forelse($rows as $i => $row)
            <div class="flex items-center gap-1.5 rounded border border-gray-200 bg-gray-50 p-1.5">
              {{-- Serial Number --}}
              <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-full bg-blue-100 text-blue-700 text-[10px] font-semibold">
                {{ $i+1 }}
              </span>
              
              {{-- Update Form --}}
              <form action="{{ route('faculty_subjects.update', $row) }}" method="POST" 
                    class="flex items-center gap-1.5 flex-1 min-w-0">
                @csrf @method('PUT')
                
                {{-- Subject Code Input --}}
                <input name="subject_code" value="{{ $row->subject_code }}"
                       class="flex-shrink-0 w-20 rounded border border-gray-300 px-1.5 py-0.5 text-[11px] focus:ring-1 focus:ring-blue-400"
                       placeholder="Code"
                       @disabled(!$batchSelected)>
                
                {{-- Subject Name Input --}}
                <input name="subject_name" value="{{ $row->subject_name }}"
                       class="flex-1 min-w-0 rounded border border-gray-300 px-1.5 py-0.5 text-[11px] focus:ring-1 focus:ring-blue-400"
                       placeholder="Name"
                       @disabled(!$batchSelected)>
                
                {{-- Save Button --}}
                <button type="submit"
                        class="flex-shrink-0 rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-0.5 text-[10px] transition"
                        @disabled(!$batchSelected)
                        title="Save">
                  💾
                </button>
              </form>
              
              {{-- Delete Button --}}
              <form action="{{ route('faculty_subjects.destroy', $row) }}" method="POST"
                    onsubmit="return confirm('Delete {{ $row->subject_code }}?')">
                @csrf @method('DELETE')
                <button class="flex-shrink-0 rounded bg-rose-600 hover:bg-rose-700 text-white px-2 py-0.5 text-[10px] transition"
                        @disabled(!$batchSelected)
                        title="Delete">
                  🗑️
                </button>
              </form>
            </div>
          @empty
            <div class="rounded border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center">
              <p class="text-gray-500 text-xs">
                @if($batchSelected)
                  No subjects for Sem {{ $semester }}, {{ $batch==1?'New':'Old' }} batch
                @else
                  Select a Batch to view subjects
                @endif
              </p>
            </div>
          @endforelse
        </div>

      </div>
    @endforeach
  </div>
</div>
<script>
// Subject code to name mapping
const subjectMap = @json($existingSubjects ?? []);

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('input[name="subject_code"]').forEach(input => {
    input.addEventListener('input', function(e) {
      const code = e.target.value.trim().toUpperCase();
      const form = e.target.closest('form');
      const nameInput = form.querySelector('input[name="subject_name"]');
      
      if (code && subjectMap[code] && !nameInput.value) {
        nameInput.value = subjectMap[code];
        nameInput.classList.add('bg-yellow-50');
        setTimeout(() => nameInput.classList.remove('bg-yellow-50'), 1000);
      }
    });
  });
});
</script>
@endsection
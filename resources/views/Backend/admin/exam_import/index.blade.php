@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">

  {{-- Enhanced scrollbar styling --}}
  <style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Custom thin scrollbar for subject names */
    .subject-scroll::-webkit-scrollbar {
      height: 2px;
    }
    .subject-scroll::-webkit-scrollbar-track {
      background: transparent;
    }
    .subject-scroll::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 2px;
    }
    .subject-scroll::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    .subject-scroll {
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }
  </style>

  {{-- Flash --}}
  @if(session('ok'))
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 text-xs">
      {{ session('ok') }}
    </div>
  @endif

  {{-- Header --}}
  <div class="mb-4">
    <div class="flex gap-7 items-center justify-between">
      <div>
        <h1 class="text-base sm:text-lg font-semibold text-gray-900">Exam Students</h1>
      </div>
      <div>
        <a href="{{ route('exam.import.create') }}"
           class="w-full rounded-lg bg-green-900 text-white px-3 py-1.5 text-xs font-semibold hover:bg-gray-800">
          Add Students
        </a>
      </div>
    </div>

    <p class="text-[11px] text-gray-500 mt-1">
      Filter by <b>Exam</b>, <b>Batch</b>, <b>Semester</b> and view students grouped faculty-wise.
    </p>
  </div>

  {{-- Filters --}}
  <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
    <form method="GET" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 items-end">
      {{-- Exam --}}
      <div>
        <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam</label>
        <select name="exam_id" class="w-full rounded-lg border px-2 py-1.5 text-xs">
          <option value="">All Exams</option>
          @foreach($exams as $e)
            <option value="{{ $e->id }}" @selected(request('exam_id') == $e->id)>
              {{ $e->exam_title }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Batch --}}
      <div>
        <label class="block text-[10px] font-medium text-gray-700 mb-1">Batch</label>
        <select name="batch" class="w-full rounded-lg border px-2 py-1.5 text-xs">
          <option value="">All</option>
          <option value="1" @selected(request('batch')==='1')>New</option>
          <option value="2" @selected(request('batch')==='2')>Old</option>
        </select>
      </div>

      {{-- Semester --}}
      <div>
        <label class="block text-[10px] font-medium text-gray-700 mb-1">Semester</label>
        <select name="semester" class="w-full rounded-lg border px-2 py-1.5 text-xs">
          <option value="">All</option>
          @for($i=1; $i<=12; $i++)
            <option value="{{ $i }}" @selected(request('semester')==$i)>Semester {{ $i }}</option>
          @endfor
        </select>
      </div>

      {{-- Faculty --}}
      <div>
        <label class="block text-[10px] font-medium text-gray-700 mb-1">Faculty</label>
        <select name="faculty_id" class="w-full rounded-lg border px-2 py-1.5 text-xs">
          <option value="">All Faculties</option>
          @foreach($faculties as $f)
            <option value="{{ $f->id }}" @selected(request('faculty_id')==$f->id)>
              {{ $f->code }} — {{ $f->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="flex gap-2">
        <button type="submit"
                class="w-full rounded-lg bg-gray-900 text-white px-3 py-1.5 text-xs font-semibold hover:bg-gray-800">
          Filter
        </button>
      </div>

      <div class="flex gap-2">
        <a href="{{ route('students.index') }}"
           class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-center font-medium hover:bg-gray-50">
          Reset
        </a>
      </div>
    </form>
  </div>

  @php
    $hasFilter = request()->filled('exam_id') && request()->filled('batch');
  @endphp

  @if(!$hasFilter)
    <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
      <p class="text-gray-600 text-sm font-medium mb-1">
        Select Exam and Batch to view students.
      </p>
      <p class="text-[11px] text-gray-500">
        Semester and Faculty are optional filters.
      </p>
    </div>
  @else
    @php
      $facMap = $faculties->keyBy('id');
    @endphp

    @if($byFaculty->isEmpty())
      <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
        <p class="text-gray-500 text-sm">No students found for the selected criteria.</p>
      </div>
    @else
      {{-- Summary on top --}}
      <div class="mb-3 flex flex-wrap gap-3 text-[11px] text-gray-700">
        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-50 border border-blue-100">
          <span class="font-semibold">Total Students:</span>
          <span>{{ $totalStudents }}</span>
        </span>

        @if(!empty($subjectCounts))
          <span class="font-semibold">Subject-wise Count (current page):</span>
          @foreach($subjectCounts as $code => $cnt)
            @php
              $sName = $subjectNames[$code] ?? null;
            @endphp
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-50 border border-gray-200 max-w-xs">
              <span class="font-mono">{{ $code }}</span>
              @if($sName)
                <span class="text-[10px] text-gray-600 max-w-[140px] overflow-x-auto whitespace-nowrap no-scrollbar">
                  — {{ $sName }}
                </span>
              @endif
              <span>({{ $cnt }})</span>
            </span>
          @endforeach
        @endif
      </div>

      {{-- Faculty cards --}}
      <div class="space-y-3">
        @foreach($byFaculty as $fid => $regsOfFaculty)
          @php
            $fac = $facMap->get($fid);
          @endphp
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            {{-- Faculty header (click to toggle) --}}
            <button type="button"
                    class="w-full flex items-center justify-between px-3 py-2 text-left text-xs sm:text-sm bg-gray-50 hover:bg-gray-100 border-b"
                    data-faculty-toggle="fac-{{ $fid }}">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-800 text-white text-[10px] font-semibold">
                  {{ $fac?->code ?? $fid }}
                </span>
                <div>
                  <div class="font-semibold text-gray-900">
                    {{ $fac?->code ?? 'Faculty '.$fid }} — {{ $fac?->name ?? '' }}
                  </div>
                  <div class="text-[10px] text-gray-500">
                    Students on this page: {{ $regsOfFaculty->count() }}
                  </div>
                </div>
              </div>
              <svg class="w-4 h-4 text-gray-500 transition-transform" data-faculty-icon="fac-{{ $fid }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            {{-- Students table --}}
            <div class="overflow-x-auto" id="fac-{{ $fid }}">
              <table class="min-w-full text-[11px]">
                <thead>
                  <tr class="bg-gray-50 border-b">
                    <th class="px-2 py-1.5 text-left font-semibold">SN</th>
                    <th class="px-2 py-1.5 text-left font-semibold whitespace-nowrap">Campus Roll</th>
                    <th class="px-2 py-1.5 text-left font-semibold whitespace-nowrap">Exam Roll</th>
                    <th class="px-2 py-1.5 text-left font-semibold">Name</th>
                    <th class="px-2 py-1.5 text-left font-semibold">Subjects (Exam Taken)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($regsOfFaculty as $idx => $reg)
                    @php
                      $stu = $reg->student;
                    @endphp
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                      <td class="px-2 py-2 align-top text-gray-600">{{ $idx+1 }}</td>
                      <td class="px-2 py-2 align-top font-mono text-gray-900">
                        {{ $stu->campus_roll_no ?? '—' }}
                      </td>
                      <td class="px-2 py-2 align-top font-mono text-gray-900">
                        {{ $reg->exam_roll_no ?? '—' }}
                      </td>
                      <td class="px-2 py-2 align-top text-gray-900">
                        {{ $stu->name ?? '—' }}
                      </td>
                      <td class="px-2 py-2 align-top">
                        @php
                          $takenSubs = $reg->subjects->filter(fn($s) => $s->th_taking || $s->p_taking);
                        @endphp

                        @if($takenSubs->isEmpty())
                          <span class="text-gray-400">—</span>
                        @else
                          <div class="flex gap-1.5 overflow-x-auto subject-scroll pb-1">
                            @foreach($takenSubs as $sub)
                              @php
                                $fss   = $sub->fss;
                                $label = $sub->subject_code;
                                $name  = optional(optional($fss)->subject)->name;
                                $flags = [];
                                if ($sub->th_taking) $flags[] = 'TH';
                                if ($sub->p_taking)  $flags[] = 'P';
                              @endphp
                              <div class="inline-flex flex-col px-2 py-1.5 rounded-md bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 shadow-sm hover:shadow-md transition-shadow w-[95px] flex-shrink-0">
                                <span class="font-mono text-[10px] font-bold text-slate-800">{{ $label }}</span>
                                @if($name)
                                  <div class="text-[9px] leading-tight text-gray-600 overflow-x-auto whitespace-nowrap subject-scroll mt-0.5 pr-1" title="{{ $name }}">
                                    {{ $name }}
                                  </div>
                                @endif
                                @if($flags)
                                  <span class="text-[9px] font-semibold text-emerald-700 mt-1 inline-flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ implode(' / ', $flags) }}
                                  </span>
                                @endif
                              </div>
                            @endforeach
                          </div>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endforeach
      </div>

      {{-- Pagination --}}
      @if($registrations instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3">
          {{ $registrations->links() }}
        </div>
      @endif
    @endif
  @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-faculty-toggle]').forEach(btn => {
    const id = btn.getAttribute('data-faculty-toggle');
    const panel = document.getElementById(id);
    const icon  = document.querySelector(`[data-faculty-icon="${id}"]`);

    if (panel) panel.style.display = 'block';

    btn.addEventListener('click', () => {
      if (!panel) return;
      if (panel.style.display === 'none') {
        panel.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(0deg)';
      } else {
        panel.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(-90deg)';
      }
    });
  });
});
</script>
@endsection
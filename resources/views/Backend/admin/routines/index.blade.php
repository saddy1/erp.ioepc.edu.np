@extends('Backend.layouts.app')

@section('content')
<style>
  /* ---------- Screen tweaks stay the same ---------- */
  .print-header { display: none; }

  /* ---------- A4 fit-to-width print rules ---------- */
  @page {
    size: A4 portrait;
    margin: 10mm; /* tight, but readable; adjust if needed */
  }

  @media print {
    /* Ensure browser prints colors & backgrounds decently */
    * {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    /* Remove everything except printable-area */
    body * { visibility: hidden; }
    #printable-area, #printable-area * { visibility: visible; }

    /* Fix page width to A4 content box */
    html, body {
      width: 210mm;
      height: 297mm;
      margin: 0;
      padding: 0;
      overflow: visible !important;
    }

    /* Fit container to page width */
    #printable-area {
      position: static !important;
      left: 0; top: 0;
      width: 100% !important;
      max-width: 190mm; /* 210mm - margins (approx) */
      margin: 0 auto !important;
    }

    /* Show a simple header in print */
    .print-header {
      display: block !important;
      text-align: center;
      margin: 0 0 8mm 0;
    }

    /* Neutralize layout that can push width */
    .no-print { display: none !important; }
    .shadow-sm, .shadow, .shadow-md, .shadow-lg { box-shadow: none !important; }
    .border { border-color: #ddd !important; }
    .bg-white, .bg-gray-50, .bg-gradient-to-r { background: #fff !important; }
    .from-blue-50, .to-indigo-50 { background: #fff !important; }
    .overflow-x-auto { overflow: visible !important; }

    /* Remove sticky columns in print (can break width) */
    .sticky { position: static !important; }

    /* Tighten typography for print */
    #printable-area, #printable-area table {
      font-size: 10px !important;
      line-height: 1.25 !important;
    }

    /* Table should never exceed page width */
    table {
      width: 100% !important;
      table-layout: fixed !important; /* crucial for fit */
      border-collapse: collapse !important;
    }

    thead tr th, tbody tr td {
      padding: 4px 6px !important; /* tighter cells for fit */
      word-wrap: break-word !important;
      overflow-wrap: anywhere !important;
      white-space: normal !important;
      border: 1px solid #ccc !important;
    }

    /* Remove min-width that tailwind might inject from classes */
    [class*="min-w-"] { min-width: 0 !important; }

    /* Smaller badges */
    .rounded-lg, .rounded-md, .rounded-xl { border-radius: 4px !important; }
    .px-2\.5 { padding-left: 6px !important; padding-right: 6px !important; }
    .py-1\.5 { padding-top: 3px !important; padding-bottom: 3px !important; }

    /* Avoid breaking rows or tables across pages */
    table, thead, tbody, tr { page-break-inside: avoid !important; }
    .semester-block { page-break-after: always; }
    .semester-block:last-of-type { page-break-after: auto; }
  }
</style>

@php

  // Define the time conversion function
  if (!function_exists('convertTo12Hour')) {
    function convertTo12Hour($time) {
      $time = substr($time, 0, 5);
      list($h, $m) = explode(':', $time);
      $h = (int)$h;
      $period = $h >= 12 ? 'PM' : 'AM';
      $h = $h % 12 ?: 12;
      return sprintf('%d:%s %s', $h, $m, $period);
    }
  }
@endphp

<style>
  @media print {
    body * { visibility: hidden; }
    #printable-area, #printable-area * { visibility: visible; }
    #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
  }
  .print-header { display: none; }
</style>

<div class="max-w-7xl mx-auto p-4 sm:p-6">
  {{-- Flash --}}
  @if(session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 no-print">
      {{ session('ok') }}
    </div>
  @endif

  {{-- Header --}}
  <div class="mb-4 no-print">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Examination Schedule</h1>
    <p class="text-sm text-gray-600 mt-1">
      Select <span class="font-semibold">Exam Title</span> and <span class="font-semibold">Batch</span> to view the full exam routine. Semester is optional.
    </p>
  </div>

  {{-- Filter --}}
  <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm no-print">
    <form method="GET" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Exam Title <span class="text-red-500">*</span></label>
        <select name="exam_title" id="exam_title" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
          <option value="">Select Exam</option>
          @foreach(($examTitles ?? []) as $t)
            <option value="{{ $t }}" @selected(request('exam_title')===$t)>{{ $t }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Batch <span class="text-red-500">*</span></label>
        <select name="batch" id="batch" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
          <option value="">Select Batch</option>
          <option value="1" @selected(request('batch')==='1')>New</option>
          <option value="2" @selected(request('batch')==='2')>Old</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1.5">Semester (Optional)</label>
        <select name="semester" id="semester" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">All Semesters</option>
          @for($i=1; $i<=12; $i++)
            <option value="{{ $i }}" @selected(request('semester')==$i)>Semester {{ $i }}</option>
          @endfor
        </select>
      </div>

      <div class="flex items-end">
        <button type="submit" class="w-full rounded-lg bg-gray-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-gray-800 transition shadow-sm">
          Filter
        </button>
      </div>

      <div class="flex items-end">
        <a href="{{ route('routines.index') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-center hover:bg-gray-50 transition">
          Reset
        </a>
      </div>
    </form>
  </div>

  @php
    $hasRequired = request()->filled('batch') && request()->filled('exam_title');
  @endphp

  @if(!$hasRequired)
    <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center no-print">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <p class="text-gray-600 text-lg font-medium mb-2">Select Exam and Batch</p>
      <p class="text-sm text-gray-500">Please select <span class="font-semibold">Exam Title</span> and <span class="font-semibold">Batch</span> to view the routine.</p>
      <p class="text-xs text-gray-400 mt-1">Semester is optional — leave blank to see all semesters.</p>
    </div>
  @else
    @php
      $collection = ($slots instanceof \Illuminate\Pagination\LengthAwarePaginator)
        ? $slots->getCollection()
        : collect($slots);

      $bySemester = $collection->groupBy('semester')->sortKeys();
    @endphp

    @if($bySemester->isEmpty())
      <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-gray-500 text-lg">No routine slots found</p>
        <p class="text-sm text-gray-400 mt-1">No data available for the selected criteria.</p>
      </div>
    @else
      {{-- Print Button --}}
      <div class="mb-3 flex justify-end no-print">
        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          Print Routine
        </button>
      </div>

      <div id="printable-area">
        {{-- Print Header --}}
        <div class="print-header">
          <h1 class="text-2xl font-bold">Examination Schedule</h1>
          <p class="text-sm mt-1">Exam: {{ request('exam_title') }} | Batch: {{ request('batch')=='1' ? 'New' : 'Old' }}</p>
          @if(request('semester'))
            <p class="text-sm">Semester: {{ request('semester') }}</p>
          @endif
        </div>

        <div class="space-y-4">
          @foreach($bySemester as $sem => $semSlots)
            @php
              // Normalize (Carbon|string) -> string key
              $norm = function ($v) {
                return $v instanceof \Illuminate\Support\Carbon ? $v->format('Y-m-d') : (string) $v;
              };

              // Distinct date keys
              $dates = $semSlots->pluck('exam_date')->map($norm)->unique()->values();

              // Build matrix[dateKey][faculty_id] = ['code' => ..., 'name' => ...]
              $matrix = [];
              $subjectNames = [];
              foreach ($semSlots as $slot) {
                $dk = $norm($slot->exam_date);
                foreach ($slot->subjects as $sub) {
                  $subjectName = $sub->subjectName->subject_name ?? '';
                  $matrix[$dk][$sub->faculty_id] = [
                    'code' => $sub->subject_code,
                    'name' => $subjectName,
                  ];
                  $subjectNames[$sub->subject_code] = $subjectName;
                }
              }

              // metaByDate (time/title/batch) from first slot on that date
              $metaByDate = [];
              foreach ($dates as $dk) {
                $first = $semSlots->first(function($s) use ($norm, $dk) {
                  return $norm($s->exam_date) === $dk;
                });
                if ($first) {
                  $start = convertTo12Hour($first->start_time);
                  $end = convertTo12Hour($first->end_time);
                  $metaByDate[$dk] = [
                    'exam_title' => $first->exam_title,
                    'batch'      => $first->batch,
                    'time'       => $start . ' – ' . $end,
                  ];
                }
              }

              // Generate color palette for subjects
              $allSubjects = [];
              foreach($matrix as $date => $facData) {
                foreach($facData as $subData) {
                  if(!in_array($subData['code'], $allSubjects)) $allSubjects[] = $subData['code'];
                }
              }
              $colors = [
                'bg-blue-100 text-blue-800', 'bg-green-100 text-green-800', 'bg-purple-100 text-purple-800', 
                'bg-pink-100 text-pink-800', 'bg-yellow-100 text-yellow-800', 'bg-indigo-100 text-indigo-800',
                'bg-orange-100 text-orange-800', 'bg-teal-100 text-teal-800', 'bg-cyan-100 text-cyan-800',
                'bg-lime-100 text-lime-800', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-800'
              ];
              $subjectColors = [];
              foreach($allSubjects as $idx => $code) {
                $subjectColors[$code] = $colors[$idx % count($colors)];
              }
            @endphp

            {{-- Semester table --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
              <div class="px-4 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 border-b">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-600 text-white text-sm font-bold">
                      {{ $sem }}
                    </span>
                    <span class="font-semibold text-gray-900">Semester {{ $sem }}</span>
                  </div>
                  <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      <span class="text-gray-500">Exam:</span>
                      <span class="font-medium text-gray-900">{{ request('exam_title') }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                      </svg>
                      <span class="text-gray-500">Batch:</span>
                      <span class="font-medium text-gray-900">{{ request('batch')=='1' ? 'New' : 'Old' }}</span>
                    </span>
                  </div>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full">
                  <thead>
                    <tr class="bg-gray-50 border-b">
                      <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10 min-w-[120px]">
                        <div>Date</div>
                        <div class="text-[10px] font-normal text-gray-500">Time</div>
                      </th>
                      @foreach($faculties as $f)
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 whitespace-nowrap min-w-[100px]">{{ $f->code }}</th>
                      @endforeach
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($dates as $dk)
                      @php $meta = $metaByDate[$dk] ?? null; @endphp
                      <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-3 py-2 sticky left-0 bg-white z-10 border-r">
                          <div class="text-xs font-medium text-gray-900">{{ $dk }}</div>
                          <div class="text-[10px] text-gray-500">{{ $meta['time'] ?? '' }}</div>
                        </td>
                        @foreach($faculties as $f)
                          <td class="px-2 py-2">
                            @php
                              $subData = $matrix[$dk][$f->id] ?? null;
                            @endphp
                            @if($subData)
                              <div class="inline-block rounded-md px-2 py-1 {{ $subjectColors[$subData['code']] ?? 'bg-gray-100 text-gray-800' }}">
                                <div class="font-semibold text-xs">{{ $subData['code'] }}</div>
                                @if($subData['name'])
                                  <div class="text-[9px] leading-tight mt-0.5 opacity-90">{{ $subData['name'] }}</div>
                                @endif
                              </div>
                            @else
                              <span class="text-gray-400 text-xs">—</span>
                            @endif
                          </td>
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endforeach
        </div>

        @if($slots instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <div class="mt-4 no-print">
            {{ $slots->links() }}
          </div>
        @endif
      </div>
    @endif
  @endif
</div>

{{-- ===== JS: Autofill batch & restrict semester (odd/even) ===== --}}
<script>
  const ODD  = [1,3,5,7,9];
  const EVEN = [2,4,6,8,10];
  const PRESELECT_SEM = "{{ request('semester') }}";

  function renderSemesterOptions(type, preselect = null) {
    const sel = document.getElementById('semester');
    if (!sel) return;

    const frag = document.createDocumentFragment();
    const blank = document.createElement('option');
    blank.value = '';
    blank.textContent = 'All Semesters';
    frag.appendChild(blank);

    const list = type === 'odd'
      ? ODD
      : (type === 'even' ? EVEN : Array.from({length:12}, (_,i)=>i+1));

    list.forEach(n => {
      const opt = document.createElement('option');
      opt.value = String(n);
      opt.textContent = 'Semester ' + String(n);
      if (preselect && String(preselect) === String(n)) opt.selected = true;
      frag.appendChild(opt);
    });

    sel.innerHTML = '';
    sel.appendChild(frag);
  }

  async function fetchExamMetaAndPopulate() {
    const titleSel = document.getElementById('exam_title');
    const batchSel = document.getElementById('batch');
    const semSel   = document.getElementById('semester');
    if (!titleSel || !batchSel || !semSel) return;

    const title = titleSel.value;
    if (!title) {
      renderSemesterOptions(null, PRESELECT_SEM);
      batchSel.value = '';
      return;
    }

    try {
      const url = `{{ route('exams.meta') }}?title=${encodeURIComponent(title)}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('meta not found');
      const json = await res.json();
      if (!json.ok) throw new Error('meta not ok');

      const { batch, semester_type } = json.data;
      batchSel.value = String(batch);
      renderSemesterOptions(semester_type, PRESELECT_SEM);
    } catch (err) {
      console.error(err);
      renderSemesterOptions(null, PRESELECT_SEM);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const titleSel = document.getElementById('exam_title');
    if (titleSel) {
      titleSel.addEventListener('change', fetchExamMetaAndPopulate);
      if (titleSel.value) fetchExamMetaAndPopulate();
    }
  });
</script>
@endsection
@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">
  
  {{-- Success Message --}}
  @if(session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <p class="text-emerald-800 text-sm font-medium">{{ session('ok') }}</p>
        </div>
      </div>
    </div>
  @endif

  {{-- Error Messages --}}
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <ul class="space-y-1 text-sm text-red-800">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Header --}}
  <div class="mb-4">
    <h1 class="text-base sm:text-lg font-semibold text-gray-900">Room Allocation</h1>
    <p class="text-[11px] text-gray-500 mt-1">
      Allocate students to rooms for each exam date. Room capacity and student counts are validated automatically.
    </p>
  </div>

  {{-- Filter --}}
  <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
    <form method="GET" class="space-y-3">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        {{-- Exam --}}
        <div>
          <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam <span class="text-red-500">*</span></label>
          <select name="exam_id" 
                  class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  onchange="this.form.submit()">
            <option value="">— Select Exam —</option>
            @foreach($exams as $e)
              <option value="{{ $e->id }}" @selected($examId == $e->id)>
                {{ $e->exam_title }} ({{ ucfirst($e->batch) }})
              </option>
            @endforeach
          </select>
        </div>

        {{-- Date (only show if exam is selected) --}}
        @if($exam && !empty($examDates))
          <div>
            <label class="block text-[10px] font-medium text-gray-700 mb-1">Exam Date <span class="text-red-500">*</span></label>
            <select name="exam_date" 
                    class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    onchange="this.form.submit()">
              <option value="">— Select Date —</option>
              @foreach($examDates as $date)
                <option value="{{ $date }}" @selected($examDate === $date)>
                  {{ $date }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Batch --}}
          <div>
            <label class="block text-[10px] font-medium text-gray-700 mb-1">Batch</label>
            <select name="batch" 
                    class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    onchange="this.form.submit()">
              <option value="">Auto</option>
              <option value="1" @selected(($batch ?? '') == 1)>New</option>
              <option value="2" @selected(($batch ?? '') == 2)>Old</option>
            </select>
          </div>
        @endif
      </div>

      @if($exam && empty($examDates))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
          <p class="text-amber-800 text-xs">
            No exam dates found in the routine for this exam.
          </p>
        </div>
      @endif
    </form>
  </div>

  {{-- Messages / states --}}
  @if(!$examId)
    <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
      <p class="text-gray-600 text-sm font-medium">
        Select an exam to begin room allocation
      </p>
    </div>
  @elseif(!$examDate)
    <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
      <p class="text-gray-600 text-sm font-medium">
        Select an exam date
      </p>
    </div>
  @elseif(empty($papers))
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
      <p class="text-amber-800 text-sm font-medium">
        No subjects scheduled for this date
      </p>
      <p class="text-[11px] text-amber-700">
        There are no routine slots found for {{ $exam->exam_title }} on {{ $examDate }}
      </p>
    </div>
  @else
    {{-- Allocation Grid --}}
    <form method="POST" action="{{ route('room_allocations.store') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="exam_id" value="{{ $examId }}">
      <input type="hidden" name="exam_date" value="{{ $examDate }}">
      <input type="hidden" name="batch" value="{{ $batch }}">

      {{-- PHP: Create color mapping for subjects --}}
      @php
        $colors = [
          'blue' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'header' => 'bg-blue-100'],
          'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'header' => 'bg-emerald-100'],
          'purple' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'header' => 'bg-purple-100'],
          'amber' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'header' => 'bg-amber-100'],
          'rose' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'header' => 'bg-rose-100'],
          'cyan' => ['bg' => 'bg-cyan-50', 'border' => 'border-cyan-200', 'header' => 'bg-cyan-100'],
          'indigo' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'header' => 'bg-indigo-100'],
          'pink' => ['bg' => 'bg-pink-50', 'border' => 'border-pink-200', 'header' => 'bg-pink-100'],
        ];
        
        $subjectColors = [];
        $colorIndex = 0;
        $colorKeys = array_keys($colors);
        
        foreach($papers as $paperKey => $paper) {
          $subjectCode = $paper['subject_code'];
          if (!isset($subjectColors[$subjectCode])) {
            $subjectColors[$subjectCode] = $colors[$colorKeys[$colorIndex % count($colorKeys)]];
            $colorIndex++;
          }
        }
      @endphp

      {{-- Summary Stats --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
          <div class="flex items-center justify-between">
            <span class="text-xs text-blue-700 font-medium">Exam Date</span>
            <span class="text-sm font-bold text-blue-900">{{ $examDate }}</span>
          </div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
          <div class="flex items-center justify-between">
            <span class="text-xs text-emerald-700 font-medium">Total Students</span>
            <span class="text-sm font-bold text-emerald-900">{{ $totalStudents }}</span>
          </div>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50 px-4 py-3">
          <div class="flex items-center justify-between">
            <span class="text-xs text-purple-700 font-medium">Subjects Today</span>
            <span class="text-sm font-bold text-purple-900">{{ count($papers) }}</span>
          </div>
        </div>
      </div>

      {{-- Allocation Table --}}
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full text-[11px]">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-3 py-2 text-left font-semibold sticky left-0 bg-gray-50 z-10">Room</th>
                @foreach($papers as $paperKey => $paper)
                  @php
                    $fac = $faculties->firstWhere('id', $paper['faculty_id']);
                    $subjectColor = $subjectColors[$paper['subject_code']];
                  @endphp
                  <th class="px-2 py-2 text-left font-semibold min-w-[120px] {{ $subjectColor['header'] }} {{ $subjectColor['border'] }} border-x-2">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-gray-900">{{ $paper['subject_code'] }}</span>
                      <span class="text-[9px] text-gray-500 font-normal line-clamp-1">{{ $paper['subject_name'] }}</span>
                      <span class="text-[9px] text-blue-600 font-medium">{{ $fac?->code ?? 'N/A' }}</span>
                      <span class="text-[9px] text-gray-400">Total: {{ $paper['total_students'] }}</span>
                    </div>
                  </th>
                @endforeach
                <th class="px-3 py-2 text-center font-semibold bg-gray-100 sticky right-0 z-10">Room Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rooms as $room)
                <tr class="border-b hover:bg-gray-100 transition-colors">
                  <td class="px-3 py-2 font-semibold sticky left-0 bg-white z-10">
                    <div class="flex flex-col">
                      <span class="text-gray-900">{{ $room->room_no }}</span>
                      <span class="text-[9px] text-gray-500">Cap: {{ $room->computed_total_seats }}</span>
                    </div>
                  </td>
                  @foreach($papers as $paperKey => $paper)
                    @php
                      $val = $allocByRoom[$room->id][$paperKey] ?? 0;
                      $subjectColor = $subjectColors[$paper['subject_code']];
                    @endphp
                    <td class="px-2 py-2 {{ $subjectColor['bg'] }} {{ $subjectColor['border'] }} border-x-2">
                      <input type="number"
                             name="alloc[{{ $room->id }}][{{ $paperKey }}]"
                             value="{{ $val }}"
                             min="0"
                             max="{{ $paper['total_students'] }}"
                             class="w-full rounded border border-gray-300 px-2 py-1 text-center text-xs focus:ring-2 focus:ring-gray-900 focus:border-transparent bg-white"
                             placeholder="0">
                    </td>
                  @endforeach
                  <td class="px-3 py-2 text-center font-semibold bg-gray-50 sticky right-0 z-10">
                    @php
                      $roomTotal = $totalsByRoom[$room->id] ?? 0;
                      $roomCap = $room->computed_total_seats;
                      $isOverCapacity = $roomTotal > $roomCap;
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full {{ $isOverCapacity ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                      {{ $roomTotal }} / {{ $roomCap }}
                    </span>
                  </td>
                </tr>
              @endforeach
              {{-- Subject Totals Row --}}
              <tr class="bg-gray-100 font-semibold border-t-2">
                <td class="px-3 py-2 sticky left-0 bg-gray-100 z-10">Subject Total</td>
                @foreach($papers as $paperKey => $paper)
                  @php
                    $paperTotal = $totalsByPaper[$paperKey] ?? 0;
                    $paperMax = $paper['total_students'];
                    $isOverAllocated = $paperTotal > $paperMax;
                    $subjectColor = $subjectColors[$paper['subject_code']];
                  @endphp
                  <td class="px-2 py-2 text-center {{ $subjectColor['bg'] }} {{ $subjectColor['border'] }} border-x-2">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full {{ $isOverAllocated ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                      {{ $paperTotal }} / {{ $paperMax }}
                    </span>
                  </td>
                @endforeach
                <td class="px-3 py-2 text-center bg-gray-200 sticky right-0 z-10">—</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex items-center justify-end gap-3">
        <button type="submit"
                class="rounded-xl bg-gray-900 text-white px-6 py-2.5 text-sm font-semibold hover:bg-gray-800 transition-colors">
          Save Allocation
        </button>
      </div>
    </form>
  @endif
</div>
@endsection
{{-- resources/views/seatplan/show.blade.php --}}
@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
  <div class="mb-6">
    <h1 class="text-2xl font-bold">Seat Plan — {{ $exam->exam_date->format('Y-m-d') }} | Sem {{ $exam->semester }} | {{ $exam->subject_code }}</h1>
    @if($exam->meta && ($exam->meta['start_time'] || $exam->meta['duration_min'] || $exam->meta['notes']))
      <div class="text-sm text-slate-600 mt-1">
        @if($exam->meta['start_time']) Starts: {{ $exam->meta['start_time'] }} @endif
        @if($exam->meta['duration_min']) | Duration: {{ $exam->meta['duration_min'] }} min @endif
        @if($exam->meta['notes']) | Note: {{ $exam->meta['notes'] }} @endif
      </div>
    @endif
  </div>

  @foreach($rooms as $room)
    <div class="mb-10">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Room {{ $room['room_no'] }}</h2>
        <div class="text-sm text-slate-600">
          Observers:
          @foreach(($invigs[$room['room_id']] ?? collect()) as $ia)
            <span class="inline-block bg-slate-100 px-2 py-0.5 rounded border mr-1">
              {{ $ia->invigilator->name }} ({{ $ia->invigilator->type }})
            </span>
          @endforeach
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        @for($col=1;$col<=3;$col++)
          <div class="border rounded-lg p-3">
            <div class="font-semibold mb-2">Column {{ $col }}</div>
            <div class="grid gap-2">
              @for($row=1; $row <= ($room['rows'][$col] ?? 0); $row++)
                @php
                  $bench = ($seats[$room['room_id']][$col][$row] ?? collect())->keyBy('side');
                  $left = $bench->get('L');
                  $right= $bench->get('R');
                  $ls = $left?->student;
                  $rs = $right?->student;
                @endphp
                <div class="flex items-stretch">
                  <div class="flex-1 border rounded-l-lg px-2 py-1">
                    <div class="text-xs text-slate-500">Row {{ $row }} • L</div>
                    <div class="text-sm font-medium">
                      {{ $ls?->symbol_no ?? '—' }}
                      <span class="text-slate-500 ml-1">{{ $ls?->faculty_id ? $facById[$ls->faculty_id] : '' }}</span>
                    </div>
                  </div>
                  <div class="flex-1 border border-l-0 rounded-r-lg px-2 py-1">
                    <div class="text-xs text-slate-500">Row {{ $row }} • R</div>
                    <div class="text-sm font-medium">
                      {{ $rs?->symbol_no ?? '—' }}
                      <span class="text-slate-500 ml-1">{{ $rs?->faculty_id ? $facById[$rs->faculty_id] : '' }}</span>
                    </div>
                  </div>
                </div>
              @endfor
            </div>
          </div>
        @endfor
      </div>
    </div>
  @endforeach
</div>
@endsection

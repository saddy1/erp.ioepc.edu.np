@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 text-xs">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900">Exam Seat Plan – Room Wise</h1>
            <p class="text-[11px] text-gray-500">
                Room-wise list of exam roll numbers (symbol numbers) for the selected exam and date.
            </p>
        </div>

        @if($examId && $examDate && $hasData)
            <a href="{{ route('rooms.exam_seat_plan.print', ['exam_id' => $examId, 'exam_date' => $examDate, 'batch' => $batch]) }}"
               target="_blank"
               class="inline-flex items-center rounded-lg bg-gray-900 text-white px-3 py-2 text-[11px] font-semibold hover:bg-gray-800">
                Print Sheet
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
        <form method="GET" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Exam --}}
                <div>
                    <label class="block text-[10px] font-medium text-gray-700 mb-1">
                        Exam <span class="text-red-500">*</span>
                    </label>
                    <select name="exam_id"
                        class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900"
                        onchange="this.form.submit()">
                        <option value="">— Select Exam —</option>
                        @foreach($exams as $e)
                            <option value="{{ $e->id }}" @selected($examId == $e->id)>
                                {{ $e->exam_title }} ({{ ucfirst($e->batch) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date --}}
                @if($examId && !empty($allocatedDates))
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">
                            Exam Date <span class="text-red-500">*</span>
                        </label>
                        <select name="exam_date"
                            class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900"
                            onchange="this.form.submit()">
                            <option value="">— Select Date —</option>
                            @foreach($allocatedDates as $date)
                                <option value="{{ $date }}" @selected($examDate === $date)>{{ $date }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Batch --}}
                    <div>
                        <label class="block text-[10px] font-medium text-gray-700 mb-1">Batch</label>
                        <select name="batch"
                            class="w-full rounded-lg border px-2 py-1.5 text-xs focus:ring-2 focus:ring-gray-900"
                            onchange="this.form.submit()">
                            <option value="">Auto</option>
                            <option value="1" @selected(($batch ?? '') == 1)>New</option>
                            <option value="2" @selected(($batch ?? '') == 2)>Old</option>
                        </select>
                    </div>
                @endif
            </div>

            @if($examId && empty($allocatedDates))
                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                    <p class="text-amber-800 text-xs">
                        No room allocations found for this exam. Please create allocations first.
                    </p>
                </div>
            @endif
        </form>
    </div>

    {{-- States --}}
    @if(!$examId)
        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
            <p class="text-gray-600 text-sm font-medium">Select an exam to view room-wise seat plan.</p>
        </div>
    @elseif(!$examDate)
        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
            <p class="text-gray-600 text-sm font-medium">Select an exam date.</p>
        </div>
    @elseif(!$hasData)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
            <p class="text-amber-800 text-sm font-medium">
                No allocations found for {{ $exam?->exam_title }} on {{ $examDate }}.
            </p>
        </div>
    @else
        {{-- Header info similar to your sheet --}}
        <div class="mb-3 text-center text-[11px]">
            <div class="font-semibold text-gray-900 text-sm">
                EXAM SEAT PLAN – {{ $exam?->exam_title }}
            </div>
            <div class="text-gray-600">
                DATE: {{ $examDate }}
                @if($exam?->semester)
                    &nbsp; | &nbsp; Semester: {{ $exam->semester }}
                @endif
                @if($batch)
                    &nbsp; | &nbsp; Batch: {{ $batch == 1 ? 'New' : 'Old' }}
                @endif
            </div>
        </div>

        {{-- Room-wise table --}}
        <div class="rounded-2xl border border-gray-300 bg-white shadow-sm overflow-hidden">
            <table class="w-full border-collapse text-[11px]">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="border px-2 py-1 text-center w-20">Room No.</th>
                        <th class="border px-2 py-1 text-center w-32">Faculty</th>
                        <th class="border px-2 py-1 text-left">Exam Roll No. (Symbol No.)</th>
                        <th class="border px-2 py-1 text-center w-16">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roomSummaries as $roomId => $info)
                        @php
                            $room = $info['room'];
                            $rows = $info['rows'];
                        @endphp

                        @foreach($rows as $row)
                            <tr>
                                <td class="border px-2 py-1 text-center align-top">
                                    {{ $room->room_no }}
                                </td>
                                <td class="border px-2 py-1 align-top text-center">
                                    @if($row['faculty'])
                                        {{ $row['faculty']->code }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="border px-2 py-1 align-top leading-4">
                                    {{ implode(',', $row['rolls']) }}
                                </td>
                                <td class="border px-2 py-1 text-center align-top">
                                    {{ $row['total'] }}
                                </td>
                            </tr>
                        @endforeach

                        {{-- per-room subtotal row --}}
                        <tr class="bg-gray-50">
                            <td class="border px-2 py-1 text-right font-semibold" colspan="3">
                                Room {{ $room->room_no }} Total
                            </td>
                            <td class="border px-2 py-1 text-center font-semibold">
                                {{ $info['room_total'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Routine – CR / VCR Panel</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen max-w-5xl mx-auto p-4 sm:p-6">
        {{-- HEADER --}}
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $student->name }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $section->faculty->code ?? '' }} – {{ $section->name ?? '' }}
                    • Batch {{ $student->batch ?? '-' }}
                </p>
                <p class="mt-1 text-xs">
                    Role:
                    @if ($isCr)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] text-emerald-800 border border-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Class Representative (CR)
                        </span>
                    @endif
                    @if ($isVcr)
                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] text-sky-800 border border-sky-200 ml-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                            Vice CR
                        </span>
                    @endif
                    @unless($isCr || $isVcr)
                        <span class="text-xs text-slate-500">Normal student</span>
                    @endunless
                </p>
            </div>

            <div class="text-right text-xs">
                <div class="font-semibold text-slate-700">
                    {{ $currentDayLabel }}
                </div>
                <div class="text-slate-500">
                    {{ $currentDate }}
                </div>
                <p class="mt-1 text-[11px] text-slate-500 max-w-xs">
                    Only CR / VCR can mark classes as
                    <span class="font-semibold">Taught / Not taught</span> for monitoring by Campus Chief.
                </p>
            </div>
        </div>

        {{-- INFO BANNER --}}
        @if ($isCr || $isVcr)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-900">
                Mark **Taught** only if the teacher actually came and taught the class.
                Once saved, this will be visible in the teacher report & admin reports.
            </div>
        @else
            <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[11px] text-slate-700">
                You can view the routine, but only CR / VCR can update "Taught / Not taught" status.
            </div>
        @endif

        {{-- TODAY'S ROUTINE TABLE --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">
                    Today’s Routine ({{ $currentDayLabel }})
                </h2>
                <span class="text-[11px] text-slate-500">
                    Total periods: {{ $todayRoutines->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-slate-50">
                        <tr class="text-slate-700">
                            <th class="px-3 py-2 text-left font-semibold">Period</th>
                            <th class="px-3 py-2 text-left font-semibold">Time</th>
                            <th class="px-3 py-2 text-left font-semibold">Subject</th>
                            <th class="px-3 py-2 text-left font-semibold">Teacher</th>
                            <th class="px-3 py-2 text-left font-semibold">Room</th>
                            <th class="px-3 py-2 text-center font-semibold">Status</th>
                            <th class="px-3 py-2 text-center font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($todayRoutines as $row)
                            @php
                                $status = $taughtFlags[$row->id] ?? null; // true = taught, false = not taught, null = not set
                            @endphp
                            <tr>
                                <td class="px-3 py-2 align-middle">
                                    <span class="font-semibold text-slate-800">
                                        {{ $row->period_order }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 align-middle text-slate-700">
                                    {{ \Carbon\Carbon::parse($row->period_start)->format('g:i A') }}
                                    –
                                    {{ \Carbon\Carbon::parse($row->period_end)->format('g:i A') }}
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-semibold text-slate-800">
                                        {{ $row->subject_code }}
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        {{ $row->subject_name }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle text-slate-800">
                                    {{ $row->teacher_name }}
                                </td>
                                <td class="px-3 py-2 align-middle text-slate-700">
                                    {{ $row->room_no ?? '-' }}
                                </td>
                                <td class="px-3 py-2 align-middle text-center">
                                    @if ($status === true)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-800 border border-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Taught
                                        </span>
                                    @elseif ($status === false)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-medium text-rose-800 border border-rose-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Not taught
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">
                                            Not marked
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-middle text-center">
                                    @if ($isCr || $isVcr)
                                        <form method="POST"
                                              action="{{ route('student.taught.toggle', $row->id) }}"
                                              class="inline-flex items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="date" value="{{ $currentDate }}">

                                            <button name="is_taught" value="1"
                                                class="px-2 py-1 rounded-lg text-[10px] font-semibold border
                                                       {{ $status === true ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' }}">
                                                Taught
                                            </button>

                                            <button name="is_taught" value="0"
                                                class="px-2 py-1 rounded-lg text-[10px] font-semibold border
                                                       {{ $status === false ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}">
                                                Not taught
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-400">
                                            CR / VCR only
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-[11px] text-slate-500">
                                    No routine found for today.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-3 text-[10px] text-slate-400">
            Note: Once marked, admins can see how many classes were actually taught vs. scheduled.
        </p>
    </div>
</body>
</html>

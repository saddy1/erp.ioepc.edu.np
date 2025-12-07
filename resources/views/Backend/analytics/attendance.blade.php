@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs sm:text-sm">

    <div class="mb-4 flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-2">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-slate-900">
                Attendance & Class Performance Analytics
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500">
                View daily, weekly and monthly statistics of student attendance and class status (taught / not taught).
            </p>
        </div>

        <a id="exportBtn"
           href="#"
           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] sm:text-xs font-semibold hover:bg-emerald-700">
            Download Detailed CSV
        </a>
    </div>

    {{-- Filters --}}
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
        <form id="filtersForm" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-[11px] sm:text-xs">

            {{-- Mode --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Mode</label>
                <select name="mode" id="mode" class="w-full border-slate-300 rounded-lg">
                    <option value="daily"   {{ $defaultMode === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="weekly"  {{ $defaultMode === 'weekly' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="monthly" {{ $defaultMode === 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom"  {{ $defaultMode === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">From</label>
                <input type="date" name="from" id="from"
                       value="{{ $defaultFrom }}"
                       class="w-full border-slate-300 rounded-lg">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">To</label>
                <input type="date" name="to" id="to"
                       value="{{ $defaultTo }}"
                       class="w-full border-slate-300 rounded-lg">
            </div>

            {{-- Faculty --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Faculty</label>
                <select name="faculty_id" id="faculty_id" class="w-full border-slate-300 rounded-lg">
                    <option value="">All Faculties</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}">{{ $f->code }} – {{ $f->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Teacher --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Teacher</label>
                <select name="teacher_id" id="teacher_id" class="w-full border-slate-300 rounded-lg">
                    <option value="">All Teachers</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Student --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Student (optional)</label>
                <select name="student_id" id="student_id" class="w-full border-slate-300 rounded-lg">
                    <option value="">All Students</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->symbol_no }} – {{ $s->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[10px] text-slate-400">
                    Selecting a student shows their personal CA / attendance trend.
                </p>
            </div>

            {{-- Semester --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Semester</label>
                <input type="number" name="semester" id="semester"
                       class="w-full border-slate-300 rounded-lg"
                       placeholder="e.g. 1,2,...,8">
            </div>

            {{-- Batch --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Batch</label>
                <input type="text" name="batch" id="batch"
                       class="w-full border-slate-300 rounded-lg"
                       placeholder="e.g. 2080">
            </div>

            {{-- Section --}}
            <div>
                <label class="block mb-1 font-medium text-slate-700">Section ID</label>
                <input type="number" name="section_id" id="section_id"
                       class="w-full border-slate-300 rounded-lg"
                       placeholder="Section ID (simple filter)">
            </div>

            {{-- Apply button --}}
            <div class="sm:col-span-4 flex items-end justify-end">
                <button type="button" id="applyFilters"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white text-[11px] sm:text-xs font-semibold hover:bg-slate-800">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4" id="summaryCards">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-3">
            <p class="text-[10px] text-slate-500">Total Marked</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-slate-900" id="totalMarked">–</p>
        </div>
        <div class="rounded-xl bg-white border border-emerald-200 shadow-sm p-3">
            <p class="text-[10px] text-emerald-700">Present</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-emerald-700" id="totalPresent">–</p>
            <p class="text-[10px] text-emerald-500" id="presentRate">–</p>
        </div>
        <div class="rounded-xl bg-white border border-rose-200 shadow-sm p-3">
            <p class="text-[10px] text-rose-700">Absent</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-rose-700" id="totalAbsent">–</p>
            <p class="text-[10px] text-rose-500" id="absentRate">–</p>
        </div>
        <div class="rounded-xl bg-white border border-indigo-200 shadow-sm p-3">
            <p class="text-[10px] text-indigo-700">Class Taught Rate</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-indigo-700" id="taughtRate">–</p>
            <p class="text-[10px] text-slate-500" id="taughtCount">–</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="space-y-4">
        {{-- Trend line chart --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-xs sm:text-sm font-semibold text-slate-900">
                    Attendance trend (Present / Absent vs Date)
                </h2>
            </div>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        {{-- Present vs Absent pie, Taught vs Not-taught doughnut --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
                <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">
                    Present vs Absent
                </h2>
                <div class="h-64">
                    <canvas id="presentAbsentChart"></canvas>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
                <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">
                    Taught vs Not Taught
                </h2>
                <div class="h-64">
                    <canvas id="taughtChart"></canvas>
                </div>
            </div>
        </div>

        {{-- By Faculty (bar) --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">
                Attendance by Faculty
            </h2>
            <div class="h-72">
                <canvas id="facultyChart"></canvas>
            </div>
        </div>

        {{-- By Teacher (horizontal bar) --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">
                Attendance by Teacher
            </h2>
            <div class="h-96 overflow-x-auto">
                <canvas id="teacherChart"></canvas>
            </div>
        </div>

        {{-- Student-specific timeline (if any student selected) --}}
        <div id="studentTimelineBlock" class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">
                Student Attendance Timeline
            </h2>
            <p class="text-[10px] text-slate-500 mb-2">
                Shows daily attendance (CA) trend for the selected student.
            </p>
            <div class="h-64">
                <canvas id="studentTimelineChart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filtersForm = document.getElementById('filtersForm');
        const applyBtn    = document.getElementById('applyFilters');
        const exportBtn   = document.getElementById('exportBtn');

        const summaryEls = {
            totalMarked: document.getElementById('totalMarked'),
            totalPresent: document.getElementById('totalPresent'),
            totalAbsent: document.getElementById('totalAbsent'),
            presentRate: document.getElementById('presentRate'),
            absentRate: document.getElementById('absentRate'),
            taughtRate: document.getElementById('taughtRate'),
            taughtCount: document.getElementById('taughtCount'),
        };

        const studentTimelineBlock = document.getElementById('studentTimelineBlock');

        // Charts
        let trendChart, presentAbsentChart, taughtChart, facultyChart, teacherChart, studentTimelineChart;

        function buildUrl(base) {
            const params = new URLSearchParams(new FormData(filtersForm));
            return base + '?' + params.toString();
        }

        async function loadData() {
            const url = buildUrl("{{ route('admin.analytics.attendance.data') }}");

            const res = await fetch(url);
            const json = await res.json();
            updateSummary(json);
            updateCharts(json);

            // Update export button href
            exportBtn.href = buildUrl("{{ route('admin.analytics.attendance.export') }}");
        }

        function updateSummary(data) {
            const g = data.global;
            const t = data.taughtStats;

            summaryEls.totalMarked.textContent = g.total;
            summaryEls.totalPresent.textContent = g.present;
            summaryEls.totalAbsent.textContent = g.absent;
            summaryEls.presentRate.textContent = g.presentRate + ' %';
            summaryEls.absentRate.textContent  = g.absentRate + ' %';

            summaryEls.taughtRate.textContent  = t.taughtRate + ' %';
            summaryEls.taughtCount.textContent = `${t.taught} / ${t.totalClasses} classes`;
        }

        function destroyChart(chart) {
            if (chart) chart.destroy();
        }

        function updateCharts(data) {
            const trend = data.trendByDate;
            const byFaculty = data.byFaculty;
            const byTeacher = data.byTeacher;
            const g = data.global;
            const t = data.taughtStats;

            // Trend over time (line)
            destroyChart(trendChart);
            trendChart = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: trend.map(x => x.day),
                    datasets: [
                        {
                            label: 'Present',
                            data: trend.map(x => x.present),
                            fill: false,
                            tension: 0.3,
                        },
                        {
                            label: 'Absent',
                            data: trend.map(x => x.absent),
                            fill: false,
                            tension: 0.3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { font: { size: 10 } } },
                        y: { beginAtZero: true }
                    }
                }
            });

            // Present vs Absent (pie)
            destroyChart(presentAbsentChart);
            presentAbsentChart = new Chart(document.getElementById('presentAbsentChart'), {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent'],
                    datasets: [{
                        data: [g.present, g.absent],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });

            // Taught vs Not Taught (doughnut)
            destroyChart(taughtChart);
            taughtChart = new Chart(document.getElementById('taughtChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Taught', 'Not Taught'],
                    datasets: [{
                        data: [t.taught, t.notTaught],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });

            // By Faculty (bar)
            destroyChart(facultyChart);
            facultyChart = new Chart(document.getElementById('facultyChart'), {
                type: 'bar',
                data: {
                    labels: byFaculty.map(x => x.faculty_code ?? 'N/A'),
                    datasets: [
                        {
                            label: 'Present',
                            data: byFaculty.map(x => x.present),
                            stack: 'stack1',
                        },
                        {
                            label: 'Absent',
                            data: byFaculty.map(x => x.absent),
                            stack: 'stack1',
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, ticks: { font: { size: 10 } } },
                        y: { stacked: true, beginAtZero: true }
                    }
                }
            });

            // By Teacher (horizontal bar)
            destroyChart(teacherChart);
            teacherChart = new Chart(document.getElementById('teacherChart'), {
                type: 'bar',
                data: {
                    labels: byTeacher.map(x => x.teacher_name),
                    datasets: [
                        {
                            label: 'Present',
                            data: byTeacher.map(x => x.present),
                            stack: 'stack1',
                        },
                        {
                            label: 'Absent',
                            data: byTeacher.map(x => x.absent),
                            stack: 'stack1',
                        },
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, beginAtZero: true },
                        y: { stacked: true, ticks: { font: { size: 10 } } }
                    }
                }
            });

            // Student timeline (if any)
            const timeline = data.studentTimeline || [];
            if (timeline.length > 0) {
                studentTimelineBlock.classList.remove('hidden');
                destroyChart(studentTimelineChart);
                studentTimelineChart = new Chart(document.getElementById('studentTimelineChart'), {
                    type: 'line',
                    data: {
                        labels: timeline.map(x => x.day),
                        datasets: [
                            {
                                label: 'Present',
                                data: timeline.map(x => x.present),
                                tension: 0.3,
                            },
                            {
                                label: 'Absent',
                                data: timeline.map(x => x.absent),
                                tension: 0.3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { font: { size: 10 } } },
                            y: { beginAtZero: true }
                        }
                    }
                });
            } else {
                studentTimelineBlock.classList.add('hidden');
                destroyChart(studentTimelineChart);
                studentTimelineChart = null;
            }
        }

        applyBtn.addEventListener('click', () => {
            loadData();
        });

        // If mode is daily/weekly/monthly, we can ignore manual date edits (optional)
        document.getElementById('mode').addEventListener('change', () => {
            // You can auto-reset from/to here or let backend handle defaults.
        });

        // Initial load
        loadData();
    });
</script>
@endsection

@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs sm:text-sm">

    {{-- Header --}}
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
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white shadow-sm px-3 py-4 sm:px-4 sm:py-5">
        <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-3">Filters</h2>

        <form id="filtersForm" class="grid grid-cols-1 lg:grid-cols-4 gap-3 sm:gap-4 text-[11px] sm:text-xs">

            {{-- Mode --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Mode</label>
                <select name="mode" id="mode" class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="daily"   {{ $defaultMode === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="weekly"  {{ $defaultMode === 'weekly' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="monthly" {{ $defaultMode === 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom"  {{ $defaultMode === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            {{-- Date From --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">From</label>
                <input type="date" name="from" id="from"
                       value="{{ $defaultFrom }}"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
            </div>

            {{-- Date To --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">To</label>
                <input type="date" name="to" id="to"
                       value="{{ $defaultTo }}"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
            </div>

            {{-- Faculty --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Faculty</label>
                <select name="faculty_id" id="faculty_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="">All Faculties</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}">{{ $f->code }} – {{ $f->name }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400">
                    Select specific faculty to enable section & subject filters.
                </p>
            </div>

            {{-- Section --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Section</label>
                <select name="section_id" id="section_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty first</option>
                </select>
            </div>

            {{-- Semester --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Semester</label>
                <input type="number" name="semester" id="semester"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5"
                       placeholder="e.g. 1..8">
                <p class="text-[10px] text-slate-400">
                    After selecting faculty + semester, subjects will load.
                </p>
            </div>

            {{-- Subject --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Subject</label>
                <select name="subject_id" id="subject_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty & semester first</option>
                </select>
            </div>

            {{-- Group --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Group</label>
                <select name="group_id" id="group_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="">All Groups</option>
                    <option value="1">Group 1</option>
                    <option value="2">Group 2</option>
                    {{-- adjust as per your real groups --}}
                </select>
                <p class="text-[10px] text-slate-400">
                    Students will be filtered by faculty + section + group.
                </p>
            </div>

            {{-- Teacher --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Teacher</label>
                <select name="teacher_id" id="teacher_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="">All Teachers</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Student --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Student</label>
                <select name="student_id" id="student_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty / section / group first</option>
                </select>
                <p class="text-[10px] text-slate-400">
                    Selecting a student shows their personal CA / attendance timeline.
                </p>
            </div>

            {{-- Batch --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Batch</label>
                <input type="text" name="batch" id="batch"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5"
                       placeholder="e.g. 2080">
            </div>

            {{-- Apply button --}}
            <div class="lg:col-span-4 flex items-end justify-end mt-1">
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

        {{-- Student-wise attendance (bar) --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">
                Student Attendance (Filtered by Faculty / Subject / Group)
            </h2>
            <p class="text-[10px] text-slate-500 mb-2">
                Shows present vs absent count for each student under current filters.
            </p>
            <div class="h-96 overflow-x-auto">
                <canvas id="studentChart"></canvas>
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

        const facultySelect = document.getElementById('faculty_id');
        const sectionSelect = document.getElementById('section_id');
        const semesterInput = document.getElementById('semester');
        const subjectSelect = document.getElementById('subject_id');
        const groupSelect   = document.getElementById('group_id');
        const studentSelect = document.getElementById('student_id');

        const summaryEls = {
            totalMarked:  document.getElementById('totalMarked'),
            totalPresent: document.getElementById('totalPresent'),
            totalAbsent:  document.getElementById('totalAbsent'),
            presentRate:  document.getElementById('presentRate'),
            absentRate:   document.getElementById('absentRate'),
            taughtRate:   document.getElementById('taughtRate'),
            taughtCount:  document.getElementById('taughtCount'),
        };

        const studentTimelineBlock = document.getElementById('studentTimelineBlock');

        // Charts
        let trendChart,
            presentAbsentChart,
            taughtChart,
            facultyChart,
            teacherChart,
            studentTimelineChart,
            studentChart;

        function setDisabled(select, disabled, placeholder) {
            select.disabled = disabled;
            select.classList.toggle('bg-slate-50', disabled);

            if (placeholder !== undefined) {
                select.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholder;
                select.appendChild(opt);
            }
        }

        function buildUrl(base) {
            const params = new URLSearchParams(new FormData(filtersForm));
            return base + '?' + params.toString();
        }

        async function loadData() {
            try {
                const url = buildUrl("{{ route('admin.analytics.attendance.data') }}");
                const res = await fetch(url);
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }

                const data = await res.json();
                updateSummary(data);
                updateCharts(data);

                // Update export button href
                exportBtn.href = buildUrl("{{ route('admin.analytics.attendance.export') }}");
            } catch (error) {
                console.error('Error loading data:', error);
                alert('Failed to load analytics data. Please check console for details.');
            }
        }

        function updateSummary(data) {
            const g = data.global;
            const t = data.taughtStats;

            summaryEls.totalMarked.textContent  = g.total;
            summaryEls.totalPresent.textContent = g.present;
            summaryEls.totalAbsent.textContent  = g.absent;
            summaryEls.presentRate.textContent  = g.presentRate + ' %';
            summaryEls.absentRate.textContent   = g.absentRate + ' %';

            summaryEls.taughtRate.textContent   = t.taughtRate + ' %';
            summaryEls.taughtCount.textContent  = `${t.taught} / ${t.totalClasses} classes`;
        }

        function destroyChart(chart) {
            if (chart) chart.destroy();
        }

        function updateCharts(data) {
            const trend     = data.trendByDate || [];
            const byFaculty = data.byFaculty || [];
            const byTeacher = data.byTeacher || [];
            const byStudent = data.byStudent || [];
            const g         = data.global || {};
            const t         = data.taughtStats || {};
            const timeline  = data.studentTimeline || [];

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
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: false,
                            tension: 0.3,
                        },
                        {
                            label: 'Absent',
                            data: trend.map(x => x.absent),
                            borderColor: 'rgb(244, 63, 94)',
                            backgroundColor: 'rgba(244, 63, 94, 0.1)',
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
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
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
                        data: [g.present || 0, g.absent || 0],
                        backgroundColor: [
                            'rgb(16, 185, 129)',
                            'rgb(244, 63, 94)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });

            // Taught vs Not Taught (doughnut)
            destroyChart(taughtChart);
            taughtChart = new Chart(document.getElementById('taughtChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Taught', 'Not Taught'],
                    datasets: [{
                        data: [t.taught || 0, t.notTaught || 0],
                        backgroundColor: [
                            'rgb(99, 102, 241)',
                            'rgb(148, 163, 184)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });

            // By Faculty (stacked bar)
            destroyChart(facultyChart);
            facultyChart = new Chart(document.getElementById('facultyChart'), {
                type: 'bar',
                data: {
                    labels: byFaculty.map(x => x.faculty_code ?? 'N/A'),
                    datasets: [
                        {
                            label: 'Present',
                            data: byFaculty.map(x => x.present),
                            backgroundColor: 'rgb(16, 185, 129)',
                            stack: 'stack1',
                        },
                        {
                            label: 'Absent',
                            data: byFaculty.map(x => x.absent),
                            backgroundColor: 'rgb(244, 63, 94)',
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
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });

            // By Teacher (horizontal stacked bar)
            destroyChart(teacherChart);
            teacherChart = new Chart(document.getElementById('teacherChart'), {
                type: 'bar',
                data: {
                    labels: byTeacher.map(x => x.teacher_name),
                    datasets: [
                        {
                            label: 'Present',
                            data: byTeacher.map(x => x.present),
                            backgroundColor: 'rgb(16, 185, 129)',
                            stack: 'stack1',
                        },
                        {
                            label: 'Absent',
                            data: byTeacher.map(x => x.absent),
                            backgroundColor: 'rgb(244, 63, 94)',
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
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });

            // Student timeline (if any)
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
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.3,
                            },
                            {
                                label: 'Absent',
                                data: timeline.map(x => x.absent),
                                borderColor: 'rgb(244, 63, 94)',
                                backgroundColor: 'rgba(244, 63, 94, 0.1)',
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
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            } else {
                studentTimelineBlock.classList.add('hidden');
                destroyChart(studentTimelineChart);
                studentTimelineChart = null;
            }

            // Student attendance bar (filtered)
            destroyChart(studentChart);
            const studentsLimited = byStudent.slice(0, 40); // limit for readability
            studentChart = new Chart(document.getElementById('studentChart'), {
                type: 'bar',
                data: {
                    labels: studentsLimited.map(s => (s.symbol_no || '') + ' ' + (s.student_name || '')),
                    datasets: [
                        {
                            label: 'Present',
                            data: studentsLimited.map(s => s.present),
                            backgroundColor: 'rgb(16, 185, 129)',
                            stack: 'stack1',
                        },
                        {
                            label: 'Absent',
                            data: studentsLimited.map(s => s.absent),
                            backgroundColor: 'rgb(244, 63, 94)',
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
                        y: { stacked: true, ticks: { font: { size: 9 } } }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        // ---------------- DEPENDENT FILTER LOGIC ----------------

        async function fetchJson(url, params) {
            const usp = new URLSearchParams(params || {});
            const res = await fetch(url + '?' + usp.toString());
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        }

        async function loadSections() {
            const facultyId = facultySelect.value;
            if (!facultyId) {
                setDisabled(sectionSelect, true, 'Select faculty first');
                return;
            }

            try {
                setDisabled(sectionSelect, false, 'All Sections');
                const data = await fetchJson("{{ route('admin.analytics.attendance.sections') }}", {
                    faculty_id: facultyId
                });

                sectionSelect.innerHTML = '';
                let optAll = document.createElement('option');
                optAll.value = '';
                optAll.textContent = 'All Sections';
                sectionSelect.appendChild(optAll);

                data.forEach(sec => {
                    const opt = document.createElement('option');
                    opt.value = sec.id;
                    opt.textContent = sec.name;
                    sectionSelect.appendChild(opt);
                });
            } catch (e) {
                console.error('Error loading sections', e);
                setDisabled(sectionSelect, true, 'Error loading sections');
            }
        }

        async function loadSubjects() {
            const facultyId = facultySelect.value;
            const semester  = semesterInput.value;

            if (!facultyId || !semester) {
                setDisabled(subjectSelect, true, 'Select faculty & semester first');
                return;
            }

            try {
                setDisabled(subjectSelect, false, 'All Subjects');
                const data = await fetchJson("{{ route('admin.analytics.attendance.subjects') }}", {
                    faculty_id: facultyId,
                    semester: semester
                });

                subjectSelect.innerHTML = '';
                let optAll = document.createElement('option');
                optAll.value = '';
                optAll.textContent = 'All Subjects';
                subjectSelect.appendChild(optAll);

                data.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = `${sub.code} – ${sub.name}`;
                    subjectSelect.appendChild(opt);
                });
            } catch (e) {
                console.error('Error loading subjects', e);
                setDisabled(subjectSelect, true, 'Error loading subjects');
            }
        }

        async function loadStudents() {
            const facultyId = facultySelect.value;
            const sectionId = sectionSelect.value;
            const groupId   = groupSelect.value;

            if (!facultyId && !sectionId && !groupId) {
                setDisabled(studentSelect, true, 'Select faculty / section / group first');
                return;
            }

            try {
                setDisabled(studentSelect, false, 'All Students');
                const data = await fetchJson("{{ route('admin.analytics.attendance.students') }}", {
                    faculty_id: facultyId,
                    section_id: sectionId,
                    group_id: groupId
                });

                studentSelect.innerHTML = '';
                let optAll = document.createElement('option');
                optAll.value = '';
                optAll.textContent = 'All Students';
                studentSelect.appendChild(optAll);

                data.forEach(st => {
                    const opt = document.createElement('option');
                    opt.value = st.id;
                    opt.textContent = `${st.symbol_no} – ${st.name}`;
                    studentSelect.appendChild(opt);
                });
            } catch (e) {
                console.error('Error loading students', e);
                setDisabled(studentSelect, true, 'Error loading students');
            }
        }

        // When faculty changes: enable section + reload sections & students; reset subject
        facultySelect.addEventListener('change', () => {
            if (!facultySelect.value) {
                setDisabled(sectionSelect, true, 'Select faculty first');
                setDisabled(subjectSelect, true, 'Select faculty & semester first');
                setDisabled(studentSelect, true, 'Select faculty / section / group first');
            } else {
                loadSections();
                loadStudents();
                if (semesterInput.value) {
                    loadSubjects();
                }
            }
        });

        // When semester changes: if faculty selected, reload subjects
        semesterInput.addEventListener('change', () => {
            if (facultySelect.value && semesterInput.value) {
                loadSubjects();
            } else {
                setDisabled(subjectSelect, true, 'Select faculty & semester first');
            }
        });

        // When section or group changes, reload students
        sectionSelect.addEventListener('change', loadStudents);
        groupSelect.addEventListener('change', loadStudents);

        applyBtn.addEventListener('click', () => {
            loadData();
        });

        // Initial state: disabled child selects
        setDisabled(sectionSelect, true, 'Select faculty first');
        setDisabled(subjectSelect, true, 'Select faculty & semester first');
        setDisabled(studentSelect, true, 'Select faculty / section / group first');

        // Initial data load
        loadData();
    });
</script>
@endsection

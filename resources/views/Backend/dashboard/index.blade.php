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
                Track attendance, identify patterns, and detect data contradictions
            </p>
        </div>
        <a id="exportBtn" href="#"
           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] sm:text-xs font-semibold hover:bg-emerald-700">
            Download CSV Report
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

            {{-- From --}}
            <div class="space-y-1" id="fromWrapper">
                <label class="block font-medium text-slate-700">From</label>
                <input type="date" name="from" id="from" value="{{ $defaultFrom }}"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
            </div>

            {{-- To --}}
            <div class="space-y-1" id="toWrapper">
                <label class="block font-medium text-slate-700">To</label>
                <input type="date" name="to" id="to" value="{{ $defaultTo }}"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
            </div>

            {{-- Faculty --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Faculty</label>
                <select name="faculty_id" id="faculty_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="">All Faculties</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}">{{ $f->code }} – {{ $f->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Section --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Section</label>
                <select name="section_id" id="section_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty first</option>
                </select>
            </div>

            {{-- Semester --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Semester</label>
                <input type="number" name="semester" id="semester"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5"
                       placeholder="e.g. 1..8">
            </div>

            {{-- Batch --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Batch</label>
                <input type="text" name="batch" id="batch"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5"
                       placeholder="e.g. 2080">
            </div>

            {{-- Group --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Group</label>
                <input type="number" name="group_id" id="group_id"
                       class="w-full border border-slate-300 rounded-lg px-2 py-1.5"
                       placeholder="e.g. 1, 2">
            </div>

            {{-- Subject --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Subject</label>
                <select name="subject_id" id="subject_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty & semester first</option>
                </select>
            </div>

            {{-- Teacher --}}
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Teacher</label>
                <select name="teacher_id" id="teacher_id"
                        class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
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
                    <option value="">Select faculty first</option>
                </select>
            </div>

            {{-- Apply --}}
            <div class="lg:col-span-4 flex items-end justify-end mt-1">
                <button type="button" id="applyFilters"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white text-[11px] sm:text-xs font-semibold hover:bg-slate-800">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        {{-- Total slots --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-3">
            <p class="text-[10px] text-slate-500">Total Attendance Records</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-slate-900" id="totalSlots">–</p>
            <p class="text-[10px] text-slate-500">Each record = one student in one class</p>
        </div>

        {{-- Distinct students --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-3">
            <p class="text-[10px] text-slate-500">Distinct Students</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-slate-900" id="uniqueStudents">–</p>
        </div>

        {{-- Present --}}
        <div class="rounded-xl bg-white border border-emerald-200 shadow-sm p-3">
            <p class="text-[10px] text-emerald-700">Present</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-emerald-700" id="totalPresent">–</p>
            <p class="text-[10px] text-emerald-500" id="presentRate">–</p>
        </div>

        {{-- Absent --}}
        <div class="rounded-xl bg-white border border-rose-200 shadow-sm p-3">
            <p class="text-[10px] text-rose-700">Absent</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-rose-700" id="totalAbsent">–</p>
            <p class="text-[10px] text-rose-500" id="absentRate">–</p>
        </div>
    </div>

    {{-- Taught card --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
        <div class="rounded-xl bg-white border border-indigo-200 shadow-sm p-3">
            <p class="text-[10px] text-indigo-700">Class Taught Rate (CR/VCR feedback)</p>
            <p class="mt-1 text-lg sm:text-xl font-semibold text-indigo-700" id="taughtRate">–</p>
            <p class="text-[10px] text-slate-500" id="taughtCount">–</p>
        </div>
    </div>

    {{-- Contradictions Alert --}}
    <div id="contradictionsAlert" class="hidden mb-4 rounded-xl border-2 border-amber-300 bg-amber-50 p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-amber-900">Data Contradictions Detected</h3>
                <p class="text-xs text-amber-700 mt-1">
                    Found <span id="contradictionCount">0</span> classes marked as "taught" but with missing or low attendance records.
                </p>
                <button id="viewContradictions"
                        class="mt-2 text-xs font-medium text-amber-900 underline hover:text-amber-800">
                    View Details →
                </button>
            </div>
        </div>
    </div>

    {{-- Contradictions Table --}}
    <div id="contradictionsTable" class="hidden mb-4 rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Contradiction Details</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Date</th>
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Subject</th>
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Teacher</th>
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Section</th>
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Semester</th>
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Attendance Count</th>
                        <th class="text-left py-2 px-2 font-semibold text-slate-700">Issue</th>
                    </tr>
                </thead>
                <tbody id="contradictionsBody"></tbody>
            </table>
        </div>
    </div>

    {{-- Charts --}}
    <div class="space-y-4">

        {{-- Trend Chart --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Attendance Trend Over Time</h2>
            <div class="h-64"><canvas id="trendChart"></canvas></div>
        </div>

        {{-- Pie charts --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
                <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Present vs Absent</h2>
                <div class="h-64"><canvas id="presentAbsentChart"></canvas></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
                <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Taught vs Not Taught</h2>
                <div class="h-64"><canvas id="taughtChart"></canvas></div>
            </div>
        </div>

        {{-- By Subject --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Attendance by Subject (TH / PR)</h2>
            <p class="text-[10px] text-slate-500 mb-2">
                Shows subjects with highest absence rates and cross-subject mismatch (present elsewhere, absent here).
            </p>
            <div class="h-72"><canvas id="subjectChart"></canvas></div>
        </div>

        {{-- By Section --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Attendance by Section</h2>
            <div class="h-64"><canvas id="sectionChart"></canvas></div>
        </div>

        {{-- By Faculty --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Attendance by Faculty</h2>
            <div class="h-72"><canvas id="facultyChart"></canvas></div>
        </div>

        {{-- By Teacher --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Attendance by Teacher</h2>
            <div class="h-96"><canvas id="teacherChart"></canvas></div>
        </div>

        {{-- Student Timeline --}}
        <div id="studentTimelineBlock"
             class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Student Attendance Timeline</h2>
            <p class="text-[10px] text-slate-500 mb-2">Daily pattern for the selected student.</p>
            <div class="h-64"><canvas id="studentTimelineChart"></canvas></div>
        </div>

        {{-- By Student --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Student-wise Attendance</h2>
            <p class="text-[10px] text-slate-500 mb-2">Top 40 students by symbol number (after filters).</p>
            <div class="h-96"><canvas id="studentChart"></canvas></div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const applyBtn     = document.getElementById('applyFilters');
    const exportBtn    = document.getElementById('exportBtn');

    const modeSelect   = document.getElementById('mode');
    const fromInput    = document.getElementById('from');
    const toInput      = document.getElementById('to');

    const facultySelect = document.getElementById('faculty_id');
    const sectionSelect = document.getElementById('section_id');
    const semesterInput = document.getElementById('semester');
    const batchInput    = document.getElementById('batch');
    const groupInput    = document.getElementById('group_id');
    const subjectSelect = document.getElementById('subject_id');
    const teacherSelect = document.getElementById('teacher_id');
    const studentSelect = document.getElementById('student_id');

    const contradictionsAlert = document.getElementById('contradictionsAlert');
    const contradictionsTable = document.getElementById('contradictionsTable');
    const viewContradictionsBtn = document.getElementById('viewContradictions');

    let charts = {};

    /* Helpers */
    function setDisabled(select, disabled, placeholder = null) {
        select.disabled = disabled;
        select.classList.toggle('bg-slate-50', disabled);
        if (placeholder) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
        }
    }

    function buildUrl(base) {
        const params = new URLSearchParams(new FormData(document.getElementById('filtersForm')));
        return `${base}?${params.toString()}`;
    }

    async function fetchJson(url, params = {}) {
        const usp = new URLSearchParams(params);
        const res = await fetch(`${url}?${usp.toString()}`);
        if (!res.ok) throw new Error("HTTP " + res.status);
        return await res.json();
    }

    function formatDate(d) {
        return d.toISOString().slice(0, 10);
    }

    function updateDateInputsForMode() {
        const mode   = modeSelect.value;
        const today  = new Date();
        let fromDate = new Date(today);

        if (mode === 'weekly') {
            fromDate.setDate(today.getDate() - 6);
        } else if (mode === 'monthly') {
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
        } else if (mode === 'custom') {
            // Do not override – user will set manually
            return;
        } else {
            // daily
            fromDate = new Date(today);
        }

        fromInput.value = formatDate(fromDate);
        toInput.value   = formatDate(today);
    }

    /* Dependent dropdowns */

    async function loadSections() {
        const facultyId = facultySelect.value;

        if (!facultyId) {
            setDisabled(sectionSelect, true, "Select faculty first");
            return;
        }

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.sections') }}", {
                faculty_id: facultyId
            });

            sectionSelect.innerHTML = `<option value="">All Sections</option>`;
            data.forEach(s => {
                sectionSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
            });
            setDisabled(sectionSelect, false);
        } catch (err) {
            console.error(err);
            setDisabled(sectionSelect, true, "Error loading sections");
        }
    }

    async function loadSubjects() {
        const facultyId = facultySelect.value;
        const semester  = semesterInput.value;
        const batch     = batchInput.value;

        if (!facultyId || !semester) {
            setDisabled(subjectSelect, true, "Select faculty & semester first");
            return;
        }

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.subjects') }}", {
                faculty_id: facultyId,
                semester:   semester,
                batch:      batch
            });

            subjectSelect.innerHTML = `<option value="">All Subjects</option>`;
            data.forEach(s => {
                subjectSelect.innerHTML += `<option value="${s.id}">${s.code} – ${s.name}</option>`;
            });

            setDisabled(subjectSelect, false);
        } catch (err) {
            console.error(err);
            setDisabled(subjectSelect, true, "Error loading subjects");
        }
    }

    async function loadTeachers() {
        const facultyId = facultySelect.value;
        const sectionId = sectionSelect.value;
        const semester  = semesterInput.value;
        const subjectId = subjectSelect.value;

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.teachers') }}", {
                faculty_id: facultyId,
                section_id: sectionId,
                semester:   semester,
                subject_id: subjectId
            });

            teacherSelect.innerHTML = `<option value="">All Teachers</option>`;
            data.forEach(t => {
                teacherSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`;
            });
        } catch (err) {
            console.error(err);
        }
    }

    async function loadStudents() {
        const facultyId = facultySelect.value;
        const sectionId = sectionSelect.value;
        const groupId   = groupInput.value;

        if (!facultyId) {
            setDisabled(studentSelect, true, "Select faculty first");
            return;
        }

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.students') }}", {
                faculty_id: facultyId,
                section_id: sectionId,
                group_id:   groupId
            });

            studentSelect.innerHTML = `<option value="">All Students</option>`;
            data.forEach(st => {
                studentSelect.innerHTML += `<option value="${st.id}">${st.symbol_no} – ${st.name}</option>`;
            });

            setDisabled(studentSelect, false);
        } catch (err) {
            console.error(err);
            setDisabled(studentSelect, true, "Error loading students");
        }
    }

    /* Load analytics data */

    async function loadData() {
        const url = buildUrl("{{ route('admin.analytics.attendance.data') }}");

        try {
            const res = await fetch(url);
            const data = await res.json();

            if (!res.ok || data.error) {
                console.error('API error response:', data);
                alert("Failed to load data from server.");
                return;
            }

            try {
                if (data.global) {
                    updateSummary(data);
                }
                updateContradictions(data.contradictions || []);
                updateCharts(data);

                // CSV export link
                exportBtn.href = buildUrl("{{ route('admin.analytics.attendance.export') }}");
            } catch (renderErr) {
                console.error('Render error:', renderErr);
            }

        } catch (networkErr) {
            console.error('Network error:', networkErr);
            alert("Failed to load data (network error).");
        }
    }

    /* Summary cards */

    function updateSummary(data) {
        const g = data.global || {};
        const t = data.taughtStats || {};

        document.getElementById("totalSlots").textContent     = g.totalSlots ?? '0';
        document.getElementById("uniqueStudents").textContent = g.uniqueStudents ?? '0';
        document.getElementById("totalPresent").textContent   = g.present ?? '0';
        document.getElementById("totalAbsent").textContent    = g.absent ?? '0';
        document.getElementById("presentRate").textContent    = (g.presentRate ?? 0) + "%";
        document.getElementById("absentRate").textContent     = (g.absentRate ?? 0) + "%";

        document.getElementById("taughtRate").textContent  = (t.taughtRate ?? 0) + "%";
        document.getElementById("taughtCount").textContent =
            `${t.taught ?? 0} taught / ${t.totalClasses ?? 0} total classes`;
    }

    /* Contradictions */

    function updateContradictions(list) {
        const tbody = document.getElementById("contradictionsBody");

        if (!list || list.length === 0) {
            contradictionsAlert.classList.add("hidden");
            contradictionsTable.classList.add("hidden");
            tbody.innerHTML = "";
            return;
        }

        contradictionsAlert.classList.remove("hidden");
        document.getElementById("contradictionCount").textContent = list.length;

        tbody.innerHTML = list.map(c => `
            <tr class="border-b">
                <td class="p-2">${c.class_date}</td>
                <td class="p-2">${(c.subject_code || '')} ${c.subject_name || ''}</td>
                <td class="p-2">${c.teacher_name || ''}</td>
                <td class="p-2">${c.section_name || ''}</td>
                <td class="p-2">${c.semester || ''}</td>
                <td class="p-2 text-center">${c.attendance_count}</td>
                <td class="p-2">${c.issue_type}</td>
            </tr>
        `).join("");
    }

    viewContradictionsBtn.addEventListener("click", () => {
        contradictionsTable.classList.toggle("hidden");
    });

    /* Charts */

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function updateCharts(data) {
        const trend          = data.trendByDate || [];
        const bySubject      = data.bySubject || [];
        const bySection      = data.bySection || [];
        const byFaculty      = data.byFaculty || [];
        const byTeacher      = data.byTeacher || [];
        const byStudent      = data.byStudent || [];
        const timeline       = data.studentTimeline || [];
        const subjectContrast = data.subjectContrast || [];
        const g              = data.global || {};
        const t              = data.taughtStats || {};

        /* 1. Trend line */
        destroyChart('trendChart');
        if (trend.length) {
            const labels  = trend.map(r => r.day);
            const present = trend.map(r => r.present);
            const absent  = trend.map(r => r.absent);

            charts.trendChart = new Chart(
                document.getElementById('trendChart').getContext('2d'),
                {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Present',
                                data: present,
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 2
                            },
                            {
                                label: 'Absent',
                                data: absent,
                                tension: 0.3,
                                borderWidth: 2,
                                pointRadius: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { maxRotation: 45, minRotation: 45 } },
                            y: { beginAtZero: true }
                        }
                    }
                }
            );
        }

        /* 2. Present vs Absent */
        destroyChart('presentAbsentChart');
        if ((g.present || 0) + (g.absent || 0) > 0) {
            charts.presentAbsentChart = new Chart(
                document.getElementById('presentAbsentChart').getContext('2d'),
                {
                    type: 'doughnut',
                    data: {
                        labels: ['Present', 'Absent'],
                        datasets: [{
                            data: [g.present || 0, g.absent || 0]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                }
            );
        }

        /* 3. Taught vs Not Taught */
        destroyChart('taughtChart');
        if ((t.taught || 0) + (t.notTaught || 0) > 0) {
            charts.taughtChart = new Chart(
                document.getElementById('taughtChart').getContext('2d'),
                {
                    type: 'doughnut',
                    data: {
                        labels: ['Taught', 'Not Taught'],
                        datasets: [{
                            data: [t.taught || 0, t.notTaught || 0]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                }
            );
        }

        /* 4. Subject-wise (TH/PR + mismatch) */
        destroyChart('subjectChart');
        if (bySubject.length) {
            // Sort by absent_rate desc, top 10
            const sorted = [...bySubject].sort(
                (a, b) => (b.absent_rate || 0) - (a.absent_rate || 0)
            ).slice(0, 10);

            const labels       = [];
            const absentRates  = [];
            const mismatchData = [];

            // Build map for subjectContrast (mismatch_count per subject_id)
            const mismatchMap = {};
            subjectContrast.forEach(sc => {
                mismatchMap[sc.subject_id] = sc.mismatch_count || 0;
            });

            sorted.forEach(s => {
                const typeLabel = s.class_type === 'Practical' ? 'PR' :
                                  (s.class_type === 'Theory' ? 'TH' : (s.class_type || ''));
                const label = `${s.subject_code || ''} ${s.subject_name || ''} (Sem ${s.semester}, ${typeLabel})`;

                labels.push(label);
                absentRates.push(s.absent_rate || 0);
                mismatchData.push(mismatchMap[s.subject_id] || 0);
            });

            charts.subjectChart = new Chart(
                document.getElementById('subjectChart').getContext('2d'),
                {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Absence %',
                                data: absentRates
                            },
                            {
                                label: 'Mismatch count (Present elsewhere, Absent in this)',
                                data: mismatchData
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: { beginAtZero: true }
                        }
                    }
                }
            );
        }

        /* 5. Section-wise */
        destroyChart('sectionChart');
        if (bySection.length) {
            const labels = bySection.map(s => s.section_name || '—');
            const rate   = bySection.map(s => s.present_rate || 0);

            charts.sectionChart = new Chart(
                document.getElementById('sectionChart').getContext('2d'),
                {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Present %',
                            data: rate
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                }
            );
        }

        /* 6. Faculty-wise */
        destroyChart('facultyChart');
        if (byFaculty.length) {
            const labels = byFaculty.map(f => f.faculty_code || f.faculty_name || '');
            const rate   = byFaculty.map(f => f.present_rate || 0);

            charts.facultyChart = new Chart(
                document.getElementById('facultyChart').getContext('2d'),
                {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Present %',
                            data: rate
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                }
            );
        }

        /* 7. Teacher-wise */
        destroyChart('teacherChart');
        if (byTeacher.length) {
            const labels = byTeacher.map(t => t.teacher_name || '');
            const rate   = byTeacher.map(t => t.present_rate || 0);

            charts.teacherChart = new Chart(
                document.getElementById('teacherChart').getContext('2d'),
                {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Present %',
                            data: rate
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                }
            );
        }

        /* 8. Student timeline */
        const timelineBlock = document.getElementById('studentTimelineBlock');
        destroyChart('studentTimelineChart');

        if (timeline.length) {
            timelineBlock.classList.remove('hidden');

            const labels  = timeline.map(d => d.day);
            const present = timeline.map(d => d.present);
            const absent  = timeline.map(d => d.absent);

            charts.studentTimelineChart = new Chart(
                document.getElementById('studentTimelineChart').getContext('2d'),
                {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Present',
                                data: present,
                                borderWidth: 2,
                                tension: 0.3,
                                pointRadius: 2
                            },
                            {
                                label: 'Absent',
                                data: absent,
                                borderWidth: 2,
                                tension: 0.3,
                                pointRadius: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                }
            );
        } else {
            timelineBlock.classList.add('hidden');
        }

        /* 9. Student-wise bar */
        destroyChart('studentChart');
        if (byStudent.length) {
            const limited = byStudent.slice(0, 40);

            const labels = limited.map(s => `${s.symbol_no || ''} – ${s.student_name || ''}`);
            const rate   = limited.map(s => s.present_rate || 0);

            charts.studentChart = new Chart(
                document.getElementById('studentChart').getContext('2d'),
                {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Present %',
                            data: rate
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                }
            );
        }
    }

    /* Event listeners */

    modeSelect.addEventListener('change', () => {
        updateDateInputsForMode();
    });

    facultySelect.addEventListener("change", () => {
        loadSections();
        loadStudents();
        if (semesterInput.value) loadSubjects();
        loadTeachers();
    });

    sectionSelect.addEventListener("change", () => {
        loadStudents();
        loadTeachers();
    });

    semesterInput.addEventListener("input", () => {
        if (facultySelect.value) {
            loadSubjects();
            loadTeachers();
        }
    });

    batchInput.addEventListener("input", () => {
        if (facultySelect.value && semesterInput.value) {
            loadSubjects();
            loadTeachers();
        }
    });

    groupInput.addEventListener("input", () => {
        loadStudents();
    });

    subjectSelect.addEventListener("change", () => {
        loadTeachers();
    });

    applyBtn.addEventListener("click", loadData);

    /* Init */
    setDisabled(sectionSelect, true, "Select faculty first");
    setDisabled(subjectSelect, true, "Select faculty & semester first");
    setDisabled(studentSelect, true, "Select faculty first");

    updateDateInputsForMode();
    loadData();
});
</script>
@endsection

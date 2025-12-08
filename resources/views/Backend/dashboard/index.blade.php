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

    {{-- Filters (same as before) --}}
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white shadow-sm px-3 py-4 sm:px-4 sm:py-5">
        <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-3">Filters</h2>
        <form id="filtersForm" class="grid grid-cols-1 lg:grid-cols-4 gap-3 sm:gap-4 text-[11px] sm:text-xs">
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Mode</label>
                <select name="mode" id="mode" class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="daily" {{ $defaultMode === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="weekly" {{ $defaultMode === 'weekly' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="monthly" {{ $defaultMode === 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $defaultMode === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
   <div id="fromWrap" class="space-y-1">
    <label class="block font-medium text-slate-700">From</label>
    <input type="date" name="from" id="from" value="{{ $defaultFrom }}"
           class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
</div>
<div id="toWrap" class="space-y-1">
    <label class="block font-medium text-slate-700">To</label>
    <input type="date" name="to" id="to" value="{{ $defaultTo }}"
           class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
</div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Faculty</label>
                <select name="faculty_id" id="faculty_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="">All Faculties</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}">{{ $f->code }} – {{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Section</label>
                <select name="section_id" id="section_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty first</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Semester</label>
                <input type="number" name="semester" id="semester" class="w-full border border-slate-300 rounded-lg px-2 py-1.5" placeholder="e.g. 1..8">
            </div>
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Subject</label>
                <select name="subject_id" id="subject_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty & semester first</option>
                </select>
            </div>
       
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Teacher</label>
                <select name="teacher_id" id="teacher_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5">
                    <option value="">All Teachers</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Student</label>
                <select name="student_id" id="student_id" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 bg-slate-50" disabled>
                    <option value="">Select faculty first</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Batch</label>
                <input type="text" name="batch" id="batch" class="w-full border border-slate-300 rounded-lg px-2 py-1.5" placeholder="e.g. 2080">
            </div>
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
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-3">
            <p class="text-[10px] text-slate-500">Total Records</p>
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

    {{-- Contradictions Alert --}}
    <div id="contradictionsAlert" class="hidden mb-4 rounded-xl border-2 border-amber-300 bg-amber-50 p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-amber-900">Data Contradictions Detected</h3>
                <p class="text-xs text-amber-700 mt-1">
                    Found <span id="contradictionCount">0</span> classes marked as "taught" but with missing or low attendance records.
                </p>
                <button id="viewContradictions" class="mt-2 text-xs font-medium text-amber-900 underline hover:text-amber-800">
                    View Details →
                </button>
            </div>
        </div>
    </div>

    {{-- Contradictions Table (initially hidden) --}}
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
                <tbody id="contradictionsBody">
                </tbody>
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

        {{-- Pie Charts --}}
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
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Attendance by Subject</h2>
            <p class="text-[10px] text-slate-500 mb-2">Shows which subjects have the highest absence rates</p>
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
        <div id="studentTimelineBlock" class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Student Attendance Timeline</h2>
            <p class="text-[10px] text-slate-500 mb-2">Daily attendance pattern for selected student</p>
            <div class="h-64"><canvas id="studentTimelineChart"></canvas></div>
        </div>

        {{-- By Student --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-3 sm:p-4">
            <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">Student-wise Attendance</h2>
            <p class="text-[10px] text-slate-500 mb-2">Showing top 40 students (filtered by faculty/section/group)</p>
            <div class="h-96"><canvas id="studentChart"></canvas></div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const applyBtn   = document.getElementById('applyFilters');
    const exportBtn  = document.getElementById('exportBtn');

    const modeSelect = document.getElementById('mode');
    const fromWrap   = document.getElementById('fromWrap');
    const toWrap     = document.getElementById('toWrap');
    const fromInput  = document.getElementById('from');
    const toInput    = document.getElementById('to');

    const facultySelect = document.getElementById('faculty_id');
    const sectionSelect = document.getElementById('section_id');
    const semesterInput = document.getElementById('semester');
    const subjectSelect = document.getElementById('subject_id');
    const teacherSelect = document.getElementById('teacher_id');
    const studentSelect = document.getElementById('student_id');
    const batchInput    = document.getElementById('batch');

    const contradictionsAlert = document.getElementById('contradictionsAlert');
    const contradictionsTable = document.getElementById('contradictionsTable');
    const viewContradictionsBtn = document.getElementById('viewContradictions');

    let charts = {};

    /* ----------------- helpers ------------------ */

    function setDisabled(select, disabled, placeholder = null) {
        if (!select) return;
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
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return await res.json();
    }

    function formatDate(d) {
        return d.toISOString().slice(0, 10);
    }

    /* --------- mode => date range + visibility --------- */

    function applyModeRange() {
        const mode = modeSelect.value;
        const today = new Date();

        if (mode === 'custom') {
            // show date inputs, don't touch existing values
            fromWrap.classList.remove('hidden');
            toWrap.classList.remove('hidden');
            return;
        }

        // other modes: hide date inputs & set range from today
        fromWrap.classList.add('hidden');
        toWrap.classList.add('hidden');

        let fromDate = new Date(today);
        let toDate   = new Date(today);

        if (mode === 'weekly') {
            fromDate.setDate(fromDate.getDate() - 6);        // last 7 days including today
        } else if (mode === 'monthly') {
            fromDate.setMonth(fromDate.getMonth() - 1);      // last 1 month
        }

        fromInput.value = formatDate(fromDate);
        toInput.value   = formatDate(toDate);
    }

    /* ----------------- dropdown loaders ------------------ */

    // sections by faculty
    async function loadSections() {
        const facultyId = facultySelect.value;
        if (!facultyId) {
            setDisabled(sectionSelect, true, 'Select faculty first');
            return;
        }

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.sections') }}", {
                faculty_id: facultyId
            });

            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            data.forEach(s => {
                sectionSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
            });
            setDisabled(sectionSelect, false);
        } catch (e) {
            console.error(e);
            setDisabled(sectionSelect, true, 'Error loading sections');
        }
    }

    // subjects by faculty + semester + batch
    async function loadSubjects() {
        const facultyId = facultySelect.value;
        const semester  = semesterInput.value;
        const batch     = batchInput.value;

        if (!facultyId || !semester) {
            setDisabled(subjectSelect, true, 'Select faculty & semester first');
            return;
        }

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.subjects') }}", {
                faculty_id: facultyId,
                semester:  semester,
                batch:     batch
            });

            subjectSelect.innerHTML = '<option value="">All Subjects</option>';
            data.forEach(s => {
                subjectSelect.innerHTML += `<option value="${s.id}">${s.code} – ${s.name}</option>`;
            });
            setDisabled(subjectSelect, false);
        } catch (e) {
            console.error(e);
            setDisabled(subjectSelect, true, 'Error loading subjects');
        }
    }

    // teachers by faculty + section (+optional subject)
    async function loadTeachers() {
        const facultyId = facultySelect.value;
        const sectionId = sectionSelect.value;
        const subjectId = subjectSelect.value;

        // if no filters, just leave as "All Teachers"
        if (!facultyId && !sectionId && !subjectId) return;

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.teachers') }}", {
                faculty_id: facultyId,
                section_id: sectionId,
                subject_id: subjectId
            });

            teacherSelect.innerHTML = '<option value="">All Teachers</option>';
            data.forEach(t => {
                teacherSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`;
            });
        } catch (e) {
            console.error(e);
            // fallback: do nothing, keep previous options
        }
    }

    // students by faculty + section
    async function loadStudents() {
        const facultyId = facultySelect.value;
        const sectionId = sectionSelect.value;

        if (!facultyId) {
            setDisabled(studentSelect, true, 'Select faculty first');
            return;
        }

        try {
            const data = await fetchJson("{{ route('admin.analytics.attendance.students') }}", {
                faculty_id: facultyId,
                section_id: sectionId
            });

            studentSelect.innerHTML = '<option value="">All Students</option>';
            data.forEach(st => {
                studentSelect.innerHTML += `<option value="${st.id}">${st.symbol_no} – ${st.name}</option>`;
            });
            setDisabled(studentSelect, false);
        } catch (e) {
            console.error(e);
            setDisabled(studentSelect, true, 'Error loading students');
        }
    }

    /* ----------------- load analytics data ------------------ */

    async function loadData() {
        try {
            const url = buildUrl("{{ route('admin.analytics.attendance.data') }}");
            const res = await fetch(url);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            console.log('analytics data', data);

            updateSummary(data);
            updateContradictions(data.contradictions || []);
            updateCharts(data);

            exportBtn.href = buildUrl("{{ route('admin.analytics.attendance.export') }}");
        } catch (e) {
            console.error(e);
            alert('Failed to load data.');
        }
    }

    /* ----------------- summary cards ------------------ */

    function updateSummary(data) {
        const g = data.global || { total: 0, present: 0, absent: 0, presentRate: 0, absentRate: 0 };
        const t = data.taughtStats || { totalClasses: 0, taught: 0, notTaught: 0, taughtRate: 0 };

        document.getElementById('totalMarked').textContent  = g.total;
        document.getElementById('totalPresent').textContent = g.present;
        document.getElementById('totalAbsent').textContent  = g.absent;
        document.getElementById('presentRate').textContent  = g.presentRate + '%';
        document.getElementById('absentRate').textContent   = g.absentRate + '%';
        document.getElementById('taughtRate').textContent   = t.taughtRate + '%';
        document.getElementById('taughtCount').textContent  = `${t.taught} / ${t.totalClasses} classes`;
    }

    /* ----------------- contradictions ------------------ */

    function updateContradictions(list) {
        const tbody = document.getElementById('contradictionsBody');

        if (!list || list.length === 0) {
            contradictionsAlert.classList.add('hidden');
            contradictionsTable.classList.add('hidden');
            tbody.innerHTML = '';
            return;
        }

        contradictionsAlert.classList.remove('hidden');
        document.getElementById('contradictionCount').textContent = list.length;

        tbody.innerHTML = list.map(c => `
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="py-2 px-2">${c.class_date || c.date}</td>
                <td class="py-2 px-2">${(c.subject_code || '')} ${(c.subject_name || '')}</td>
                <td class="py-2 px-2">${c.teacher_name || 'N/A'}</td>
                <td class="py-2 px-2">${c.section_name || 'N/A'}</td>
                <td class="py-2 px-2">${c.semester || 'N/A'}</td>
                <td class="py-2 px-2 text-center">${c.attendance_count}</td>
                <td class="py-2 px-2">${c.issue_type}</td>
            </tr>
        `).join('');
    }

    viewContradictionsBtn.addEventListener('click', () => {
        contradictionsTable.classList.toggle('hidden');
    });

    /* ----------------- charts (unchanged logic) ------------------ */

    function destroyChart(key) {
        if (charts[key]) {
            charts[key].destroy();
            delete charts[key];
        }
    }

    function updateCharts(data) {
        // trend
        destroyChart('trend');
        charts.trend = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: (data.trendByDate || []).map(x => x.day),
                datasets: [
                    {
                        label: 'Present',
                        data: (data.trendByDate || []).map(x => x.present),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Absent',
                        data: (data.trendByDate || []).map(x => x.absent),
                        borderColor: 'rgb(244, 63, 94)',
                        backgroundColor: 'rgba(244, 63, 94, 0.1)',
                        tension: 0.3
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        // present vs absent
        destroyChart('presentAbsent');
        charts.presentAbsent = new Chart(document.getElementById('presentAbsentChart'), {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [data.global.present || 0, data.global.absent || 0],
                    backgroundColor: ['rgb(16, 185, 129)', 'rgb(244, 63, 94)']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // taught vs not taught
        destroyChart('taught');
        charts.taught = new Chart(document.getElementById('taughtChart'), {
            type: 'doughnut',
            data: {
                labels: ['Taught', 'Not Taught'],
                datasets: [{
                    data: [data.taughtStats.taught || 0, data.taughtStats.notTaught || 0],
                    backgroundColor: ['rgb(99, 102, 241)', 'rgb(148, 163, 184)']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // subject, section, faculty, teacher, student charts
        // (same as you already had – omitted here for brevity,
        //  but keep your existing updateCharts body)
        // ...
        // make sure they all use the filtered 'data' from backend
    }

    /* ----------------- events ------------------ */

    // when mode changes
    modeSelect.addEventListener('change', () => {
        applyModeRange();
        // only auto-load for non-custom; custom waits for user to pick dates and click Apply
        if (modeSelect.value !== 'custom') {
            loadData();
        }
    });

    facultySelect.addEventListener('change', () => {
        loadSections();
        loadStudents();
        loadTeachers();
        if (semesterInput.value) loadSubjects();
    });

    sectionSelect.addEventListener('change', () => {
        loadStudents();
        loadTeachers();
    });

    subjectSelect.addEventListener('change', () => {
        loadTeachers();
    });

    semesterInput.addEventListener('input', () => {
        if (facultySelect.value) loadSubjects();
    });

    batchInput.addEventListener('input', () => {
        if (facultySelect.value && semesterInput.value) loadSubjects();
    });

    applyBtn.addEventListener('click', () => {
        // for custom, user may have changed dates; just send whatever is in inputs
        loadData();
    });

    /* ----------------- init ------------------ */

    setDisabled(sectionSelect, true, 'Select faculty first');
    setDisabled(subjectSelect, true, 'Select faculty & semester first');
    setDisabled(studentSelect, true, 'Select faculty first');

    // initial date range based on default mode
    applyModeRange();
    loadData();
});
</script>


@endsection
@extends('Backend.layouts.app') 
{{-- Assuming you have a main layout file --}}

@section('title', 'Campus Performance Monitor')

@section('content')
<div class="min-h-screen bg-gray-50 p-6">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-university text-blue-600"></i> Campus Chief Monitor
        </h2>
        <button onclick="fetchAnalytics()" 
            class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow flex items-center gap-2 transition duration-150">
            <i class="fas fa-sync-alt"></i> Refresh Data
        </button>
    </div>

  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">From Date</label>
            <input type="date" name="from" value="{{ $defaultFrom }}" 
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
        </div>
        
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">To Date</label>
            <input type="date" name="to" value="{{ $defaultTo }}" 
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Faculty</label>
            <select name="faculty_id" id="facultySelect" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
                <option value="">All Faculties</option>
                @foreach($faculties as $f)
                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Section</label>
            <select name="section_id" id="sectionSelect" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm bg-gray-50">
                <option value="">All Sections</option>
                </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Teacher</label>
            <select name="teacher_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
                <option value="">All Teachers</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Semester</label>
            <select name="semester" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm">
                <option value="">All Semesters</option>
                @foreach(range(1, 8) as $s)
                    <option value="{{ $s }}">Semester {{ $s }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-white rounded-lg shadow border-l-4 border-green-500 p-4">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-green-600 uppercase mb-1">Avg Attendance</div>
                    <div class="text-2xl font-bold text-gray-800" id="kpi-present-rate">--%</div>
                    <div class="text-xs text-gray-500 mt-1" id="kpi-total-slots">Total Classes: --</div>
                </div>
                <div class="text-gray-300 text-3xl"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow border-l-4 border-blue-500 p-4">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-blue-600 uppercase mb-1">Classes Taught</div>
                    <div class="text-2xl font-bold text-gray-800" id="kpi-taught-rate">--%</div>
                    <div class="text-xs text-gray-500 mt-1" id="kpi-taught-raw">-- / -- logs</div>
                </div>
                <div class="text-gray-300 text-3xl"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow border-l-4 border-red-500 p-4">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-red-600 uppercase mb-1">Missed/Cancelled</div>
                    <div class="text-2xl font-bold text-gray-800" id="kpi-not-taught">--</div>
                    <div class="text-xs text-gray-500 mt-1">Reported by CR</div>
                </div>
                <div class="text-gray-300 text-3xl"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow border-l-4 border-yellow-500 p-4">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-yellow-600 uppercase mb-1">Data Red Flags</div>
                    <div class="text-2xl font-bold text-gray-800" id="kpi-contradictions">--</div>
                    <div class="text-xs text-gray-500 mt-1">Integrity Issues</div>
                </div>
                <div class="text-gray-300 text-3xl"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Attendance Trends (Daily)</h3>
            <div class="relative h-72">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">By Faculty</h3>
            <div class="relative h-72">
                <canvas id="facultyChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px" aria-label="Tabs">
                <button onclick="switchTab('contradictions', this)" 
                    class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm border-blue-500 text-blue-600 hover:text-blue-800 active-tab">
                    ⚠️ Red Flags
                </button>
                <button onclick="switchTab('notTaught', this)" 
                    class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    ❌ Not Taught
                </button>
                <button onclick="switchTab('teacherPerf', this)" 
                    class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    👨‍🏫 Teachers
                </button>
                <button onclick="switchTab('studentPerf', this)" 
                    class="tab-btn w-1/4 py-4 px-1 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    🎓 Students
                </button>
            </nav>
        </div>

        <div class="p-6">
            
         <div id="contradictions" class="tab-content block">
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
        <div class="ml-3">
            <p class="text-sm text-yellow-700">
                These records show discrepancies between Teacher Logs and CR Feedback.
            </p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="table-contradictions">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faculty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                </tbody>
        </table>
    </div>
</div>

<div id="notTaught" class="tab-content hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="table-not-taught">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faculty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                </tbody>
        </table>
    </div>
</div>

            <div id="notTaught" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="table-not-taught">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            </tbody>
                    </table>
                </div>
            </div>

            <div id="teacherPerf" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="table-teachers">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Teacher Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Total Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Present Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Avg Attendance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            </tbody>
                    </table>
                </div>
            </div>

            <div id="studentPerf" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="table-students">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Symbol No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Section</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    // 1. Pass PHP data to JavaScript
    const allSections = @json($sections);

    document.addEventListener("DOMContentLoaded", function() {
        const facultySelect = document.getElementById('facultySelect');
        const sectionSelect = document.getElementById('sectionSelect');

        // Initialize Section Dropdown
        populateSections(''); 

        // 2. Listen for Faculty Change
        facultySelect.addEventListener('change', function() {
            const selectedFacultyId = this.value;
            populateSections(selectedFacultyId);
            
            // Trigger the analytics fetch immediately after changing
            fetchAnalytics(); 
        });

        // Initial Data Fetch
        fetchAnalytics();

        // Listen for changes on the whole form to trigger updates
        document.getElementById('filterForm').addEventListener('change', function(e) {
            // Avoid double fetching if the change event came from facultySelect 
            // (since we handled it manually above)
            if (e.target.id !== 'facultySelect') {
                fetchAnalytics();
            }
        });
    });

    // 3. Helper to filter and repopulate sections
    function populateSections(facultyId) {
        const sectionSelect = document.getElementById('sectionSelect');
        const currentSelection = sectionSelect.value; // Remember selection if possible

        // Clear existing options
        sectionSelect.innerHTML = '<option value="">All Sections</option>';

        // Filter sections
        // Note: Ensure your Section model has a 'faculty_id' field. 
        // If sections are generic (not linked to faculty), remove the filter logic.
        const filtered = facultyId 
            ? allSections.filter(s => s.faculty_id == facultyId) 
            : allSections;

        // Populate new options
        filtered.forEach(s => {
            const option = document.createElement('option');
            option.value = s.id;
            option.textContent = s.name;
            sectionSelect.appendChild(option);
        });

        // Visual cue: Change background if active
        if(facultyId) {
            sectionSelect.classList.remove('bg-gray-50');
            sectionSelect.classList.add('bg-white');
        } else {
            sectionSelect.classList.add('bg-gray-50');
            sectionSelect.classList.remove('bg-white');
        }
    }
    // --- TAB SWITCHING LOGIC (Tailwind Version) ---
    function switchTab(tabId, btnElement) {
        // 1. Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('hidden');
            el.classList.remove('block');
        });

        // 2. Show selected tab content
        const target = document.getElementById(tabId);
        if(target) {
            target.classList.remove('hidden');
            target.classList.add('block');
        }

        // 3. Reset all buttons to inactive state
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        });

        // 4. Set active button style
        btnElement.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        btnElement.classList.add('border-blue-500', 'text-blue-600');
    }

    // --- ANALYTICS LOGIC (Same as before) ---
    let trendChartCtx = null;
    let facultyChartCtx = null;

    document.addEventListener("DOMContentLoaded", function() {
        fetchAnalytics();

        const form = document.getElementById('filterForm');
        form.addEventListener('change', function() {
            fetchAnalytics();
        });
    });

    function fetchAnalytics() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams(formData);

        document.body.style.cursor = 'wait';

        // NOTE: Ensure this route name matches your web.php
        fetch("{{ route('analytics.data') }}?" + params.toString())
            .then(response => response.json())
            .then(data => {
                updateKPIs(data);
                updateCharts(data);
                updateTables(data);
                document.body.style.cursor = 'default';
            })
            .catch(error => {
                console.error('Error:', error);
                document.body.style.cursor = 'default';
            });
    }

    function updateKPIs(data) {
        document.getElementById('kpi-present-rate').innerText = data.global.presentRate + '%';
        document.getElementById('kpi-total-slots').innerText = 'Total Classes: ' + data.global.totalSlots;
        
        document.getElementById('kpi-taught-rate').innerText = data.taughtStats.taughtRate + '%';
        document.getElementById('kpi-taught-raw').innerText = `${data.taughtStats.taught} / ${data.taughtStats.totalClasses} logs`;
        
        document.getElementById('kpi-not-taught').innerText = data.taughtStats.notTaught;
        document.getElementById('kpi-contradictions').innerText = data.contradictions.length;
    }

    function updateCharts(data) {
        // Trend Chart
        const dates = data.trendByDate.map(i => i.day);
        const presentCounts = data.trendByDate.map(i => i.present);
        
        const ctx1 = document.getElementById('trendChart').getContext('2d');
        if(trendChartCtx) trendChartCtx.destroy();
        
        trendChartCtx = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Present Students',
                    data: presentCounts,
                    borderColor: '#3b82f6', // Tailwind blue-500
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { maintainAspectRatio: false }
        });

        // Faculty Chart
        const facNames = data.byFaculty.map(f => f.faculty_code);
        const facRates = data.byFaculty.map(f => f.present_rate);

        const ctx2 = document.getElementById('facultyChart').getContext('2d');
        if(facultyChartCtx) facultyChartCtx.destroy();

        facultyChartCtx = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: facNames,
                datasets: [{
                    label: 'Attendance %',
                    data: facRates,
                    backgroundColor: '#10b981', // Tailwind green-500
                }]
            },
            options: { 
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }

    function updateTables(data) {
    const clearTbody = (id) => document.querySelector(`#${id} tbody`).innerHTML = '';
    const addRow = (id, html) => document.querySelector(`#${id} tbody`).insertAdjacentHTML('beforeend', html);

    // 1. Contradictions Table
    clearTbody('table-contradictions');
    if(data.contradictions.length === 0) {
        // Colspan increased to 6 to account for new column
        addRow('table-contradictions', '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No red flags found!</td></tr>');
    } else {
        data.contradictions.forEach(row => {
            let html = `
                <tr class="bg-red-50 hover:bg-red-100 transition">
                    <td class="px-6 py-4 whitespace-nowrap">${row.class_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-700">${row.faculty_code}</td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${row.teacher_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">${row.subject_code}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-red-600 font-bold text-xs">${row.issue_type}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.attendance_count}</td>
                </tr>`;
            addRow('table-contradictions', html);
        });
    }

// 2. Not Taught Table
clearTbody('table-not-taught');
data.notTaughtDetails.forEach(row => {
    let html = `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 whitespace-nowrap">${row.class_date}</td>
            <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-700">${row.faculty_code}</td>
            <td class="px-6 py-4 whitespace-nowrap">${row.teacher_name}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${row.subject_name}</div>
                <div class="text-xs text-gray-500">Periods: ${row.periods}</div> </td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500">${row.section_name} (${row.class_label})</td>
            <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Not Taught</span></td>
        </tr>`;
    addRow('table-not-taught', html);
});
    // 3. Teachers
    clearTbody('table-teachers');
    data.byTeacher.forEach(row => {
        let rateColor = row.present_rate < 50 ? 'text-red-600 font-bold' : 'text-green-600';
        let html = `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${row.teacher_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">${row.total}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">${row.present}</td>
                <td class="px-6 py-4 whitespace-nowrap ${rateColor}">${row.present_rate}%</td>
            </tr>`;
        addRow('table-teachers', html);
    });

    // 4. Students
    clearTbody('table-students');
    data.byStudent.forEach(row => {
        let color = row.present_rate < 75 ? 'bg-red-500' : 'bg-green-500';
        let html = `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">${row.symbol_no}</td>
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${row.student_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">${row.section_name}</td>
                <td class="px-6 py-4 whitespace-nowrap align-middle">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="${color} h-2.5 rounded-full" style="width: ${row.present_rate}%"></div>
                    </div>
                    <span class="text-xs text-gray-500 mt-1 block">${row.present_rate}%</span>
                </td>
            </tr>`;
        addRow('table-students', html);
    });
}
</script>
@endsection
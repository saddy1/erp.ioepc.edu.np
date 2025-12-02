@extends('Backend.layouts.app')

@section('content')
@php
    $selectedFaculty = $selectedFaculty ?? null;
    $selectedSection = $selectedSection ?? null;
    $selectedYear    = $selectedYear ?? '';
    $selectedSem     = $selectedSem ?? '';
    $currentCrId     = $currentCrId ?? null;
    $currentVcrId    = $currentVcrId ?? null;
@endphp

<div class="max-w-6xl mx-auto p-6 text-sm">

    {{-- Flash / Errors --}}
    @if(session('ok'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('ok') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-xl font-semibold mb-4">
        Assign Class Representative (CR) & Vice CR (VCR)
    </h1>

    {{-- FILTER FORM --}}
    <form method="GET" action="{{ route('admin.cr_roles.index') }}"
          class="mb-5 grid grid-cols-1 sm:grid-cols-5 gap-3">
        {{-- Faculty --}}
        <div>
            <label class="block text-[11px] text-slate-600 mb-1">Faculty / Department</label>
            <select name="faculty_id" id="faculty_id" class="w-full rounded-lg border px-3 py-2 text-sm">
                <option value="">— Select —</option>
                @foreach($faculties as $f)
                    <option value="{{ $f->id }}"
                            @selected(optional($selectedFaculty)->id == $f->id)>
                        {{ $f->code }} — {{ $f->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Year --}}
        <div>
            <label class="block text-[11px] text-slate-600 mb-1">Year</label>
            <select name="year" class="w-full rounded-lg border px-3 py-2 text-sm">
                <option value="">— Year —</option>
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected((int)$selectedYear === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- Semester --}}
        <div>
            <label class="block text-[11px] text-slate-600 mb-1">Semester</label>
            <select name="semester" class="w-full rounded-lg border px-3 py-2 text-sm">
                <option value="">— Sem —</option>
                @foreach($semesters as $s)
                    <option value="{{ $s }}" @selected((int)$selectedSem === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        {{-- Section --}}
        <div>
            <label class="block text-[11px] text-slate-600 mb-1">Section</label>
            <select name="section_id"
                    class="w-full rounded-lg border px-3 py-2 text-sm"
                    {{ $selectedFaculty ? '' : 'disabled' }}>
                <option value="">— Select —</option>
                @foreach($sections as $sec)
                    <option value="{{ $sec->id }}"
                            @selected(optional($selectedSection)->id == $sec->id)>
                        {{ $sec->name }} {{ $sec->code ? '(' . $sec->code . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Button --}}
        <div class="flex items-end">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 w-full justify-center">
                Load Students
            </button>
        </div>
    </form>

    {{-- Auto-submit on faculty change --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const facultySelect = document.getElementById('faculty_id');
            if (facultySelect) {
                facultySelect.addEventListener('change', function () {
                    const form = this.form;
                    if (!form) return;

                    const sectionInput = form.querySelector('select[name="section_id"]');
                    if (sectionInput) sectionInput.value = '';

                    form.submit(); // reload with selected faculty_id
                });
            }
        });
    </script>

    {{-- HELP TEXT --}}
    <div class="mb-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[11px] text-slate-700">
        <ul class="list-disc list-inside space-y-1">
            <li>Each section can have <b>exactly one CR</b> and <b>one VCR</b> per Year & Semester.</li>
            <li>CR/VCR will get login access and can mark “Taught / Not taught” for their section.</li>
            <li>Use this page to change CR/VCR when year/semester changes.</li>
        </ul>
    </div>

    {{-- STUDENT TABLE + ROLE FORM --}}
    @if($selectedSection && $students->count())
        @php
            // resolve current CR/VCR names (for this year+sem)
            $crStudentCurrent  = $currentCrId ? $students->firstWhere('id', $currentCrId) : null;
            $vcrStudentCurrent = $currentVcrId ? $students->firstWhere('id', $currentVcrId) : null;
        @endphp

        {{-- Current CR/VCR summary (before list) --}}
        @if($crStudentCurrent || $vcrStudentCurrent)
            <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <div class="font-semibold text-xs text-emerald-800">
                        Current CR / VCR –
                        {{ $selectedFaculty->code ?? '' }} /
                        {{ $selectedSection->name ?? '' }},
                        Year {{ $selectedYear }}, Sem {{ $selectedSem }}
                    </div>
                    <div class="mt-1 space-y-1">
                        @if($crStudentCurrent)
                            <div>
                                <span class="font-semibold">CR:</span>
                                {{ $crStudentCurrent->name }} (Roll: {{ $crStudentCurrent->symbol_no }})
                            </div>
                        @endif
                        @if($vcrStudentCurrent)
                            <div>
                                <span class="font-semibold">VCR:</span>
                                {{ $vcrStudentCurrent->name }} (Roll: {{ $vcrStudentCurrent->symbol_no }})
                            </div>
                        @endif
                    </div>
                </div>
                <div class="text-[10px] text-emerald-800">
                    To change CR / VCR, select different students below and click
                    <span class="font-semibold">Save CR / VCR</span>.
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.cr_roles.save') }}" id="crVcrForm">
            @csrf
            <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
            <input type="hidden" name="faculty_id" value="{{ $selectedFaculty->id }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="semester" value="{{ $selectedSem }}">

            {{-- these will be filled by modal (optional) --}}
            <input type="hidden" name="cr_password" id="cr_password_hidden">
            <input type="hidden" name="vcr_password" id="vcr_password_hidden">

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Students – {{ $selectedFaculty->code ?? '' }} / {{ $selectedSection->name ?? '' }}
                            (Year {{ $selectedYear }}, Sem {{ $selectedSem }})
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Select one CR and one VCR using the radio buttons below.
                        </p>
                    </div>
                    <span class="text-[11px] text-slate-500">
                        Total students: {{ $students->count() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Roll</th>
                            <th class="px-3 py-2 text-left font-semibold">Name</th>
                            <th class="px-3 py-2 text-center font-semibold">CR</th>
                            <th class="px-3 py-2 text-center font-semibold">VCR</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($students as $s)
                            <tr>
                                <td class="px-3 py-2 align-middle font-semibold text-slate-800">
                                    {{ $s->symbol_no }}
                                </td>
                                <td class="px-3 py-2 align-middle text-slate-800">
                                    {{ $s->name }}
                                </td>
                                <td class="px-3 py-2 align-middle text-center">
                                    <input type="radio"
                                           name="cr_student_id"
                                           value="{{ $s->id }}"
                                           class="h-3 w-3"
                                           data-student-name="{{ $s->name }}"
                                           data-student-roll="{{ $s->symbol_no }}"
                                           @checked($currentCrId === $s->id)>
                                </td>
                                <td class="px-3 py-2 align-middle text-center">
                                    <input type="radio"
                                           name="vcr_student_id"
                                           value="{{ $s->id }}"
                                           class="h-3 w-3"
                                           data-student-name="{{ $s->name }}"
                                           data-student-roll="{{ $s->symbol_no }}"
                                           @checked($currentVcrId === $s->id)>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-[10px] text-slate-500">
                        When you save, old CR/VCR (if any) for this Year & Semester will be replaced.
                    </p>
                    <button type="button" id="openCrVcrModal"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                        Save CR / VCR
                    </button>
                </div>
            </div>
        </form>

        {{-- PASSWORD MODAL --}}
        <div id="crVcrModal"
             class="fixed inset-0 bg-black/40 flex items-center justify-center z-40 hidden">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 text-xs">
                <h3 class="text-sm font-semibold text-slate-900 mb-2">
                    Set / Change Password for CR & VCR
                </h3>

                <p class="text-[11px] text-slate-500 mb-3">
                    For security, CR and VCR should have their own login password.
                    If a password field is left <b>blank</b>, the existing password will not be changed.
                    If you set a new password here, the student will be forced to change it on first login.
                </p>

                <div class="space-y-3">
                    {{-- CR --}}
                    <div id="crPasswordBlock" class="hidden">
                        <div class="mb-1 text-[11px]">
                            <span class="font-semibold">CR:</span>
                            <span id="crStudentLabel" class="text-slate-700"></span>
                        </div>
                        <input type="password"
                               id="cr_password_input"
                               class="w-full rounded-lg border px-3 py-2 text-xs"
                               placeholder="New password for CR (leave blank to keep existing)">
                    </div>

                    {{-- VCR --}}
                    <div id="vcrPasswordBlock" class="hidden">
                        <div class="mb-1 text-[11px]">
                            <span class="font-semibold">VCR:</span>
                            <span id="vcrStudentLabel" class="text-slate-700"></span>
                        </div>
                        <input type="password"
                               id="vcr_password_input"
                               class="w-full rounded-lg border px-3 py-2 text-xs"
                               placeholder="New password for VCR (leave blank to keep existing)">
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" id="closeCrVcrModal"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" id="confirmCrVcrModal"
                            class="px-4 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-semibold hover:bg-emerald-700">
                        Confirm & Save
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form          = document.getElementById('crVcrForm');
                const openBtn       = document.getElementById('openCrVcrModal');
                const modal         = document.getElementById('crVcrModal');
                const closeBtn      = document.getElementById('closeCrVcrModal');
                const confirmBtn    = document.getElementById('confirmCrVcrModal');

                const crBlock       = document.getElementById('crPasswordBlock');
                const vcrBlock      = document.getElementById('vcrPasswordBlock');
                const crLabel       = document.getElementById('crStudentLabel');
                const vcrLabel      = document.getElementById('vcrStudentLabel');

                const crPwdInput    = document.getElementById('cr_password_input');
                const vcrPwdInput   = document.getElementById('vcr_password_input');
                const crPwdHidden   = document.getElementById('cr_password_hidden');
                const vcrPwdHidden  = document.getElementById('vcr_password_hidden');

                function openModal() {
                    if (!form) return;

                    const crRadio  = form.querySelector('input[name="cr_student_id"]:checked');
                    const vcrRadio = form.querySelector('input[name="vcr_student_id"]:checked');

                    if (!crRadio && !vcrRadio) {
                        alert('Please select at least CR or VCR before saving.');
                        return;
                    }

                    // Reset blocks
                    crBlock.classList.add('hidden');
                    vcrBlock.classList.add('hidden');
                    crLabel.textContent  = '';
                    vcrLabel.textContent = '';
                    crPwdInput.value     = '';
                    vcrPwdInput.value    = '';

                    if (crRadio) {
                        const name = crRadio.dataset.studentName || '';
                        const roll = crRadio.dataset.studentRoll || '';
                        crLabel.textContent = name + (roll ? ' (Roll: ' + roll + ')' : '');
                        crBlock.classList.remove('hidden');
                    }

                    if (vcrRadio) {
                        const name = vcrRadio.dataset.studentName || '';
                        const roll = vcrRadio.dataset.studentRoll || '';
                        vcrLabel.textContent = name + (roll ? ' (Roll: ' + roll + ')' : '');
                        vcrBlock.classList.remove('hidden');
                    }

                    modal.classList.remove('hidden');
                }

                function closeModal() {
                    modal.classList.add('hidden');
                }

                if (openBtn) {
                    openBtn.addEventListener('click', openModal);
                }
                if (closeBtn) {
                    closeBtn.addEventListener('click', closeModal);
                }
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function () {
                        // Copy entered passwords into hidden inputs (may be empty)
                        crPwdHidden.value  = crPwdInput.value;
                        vcrPwdHidden.value = vcrPwdInput.value;

                        form.submit();
                    });
                }
            });
        </script>
    @elseif($selectedSection)
        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[11px] text-slate-600">
            No students found for this section / year / semester.
        </div>
    @endif


</div>
@endsection

@extends('Backend.layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6">

        @php
            /** @var \App\Models\Admin|null $authAdmin */
            $canManage = isset($authAdmin) && $authAdmin->is_super_admin;
        @endphp

        @if (session('ok'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- LEFT: Faculties list --}}
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h1 class="text-2xl font-bold">Faculties</h1>

                    {{-- 🔵 Manage / Create HODs (department-wise) – only super admin --}}
                    @if($canManage)
                        <a href="{{ route('admin.departments.index') }}"
                           class="inline-flex items-center rounded-xl border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                            Manage HODs
                        </a>
                    @endif
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Code</th>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Sections</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($faculties as $f)
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="px-4 py-2 whitespace-nowrap">{{ $f->code }}</td>
                                    <td class="px-4 py-2">{{ $f->name }}</td>

                                    <td class="px-4 py-2">
                                        @php
                                            // assumes relation: $f->sections()
                                            $sections = optional($f->sections)->pluck('name')->all() ?? [];
                                        @endphp

                                        @if (!empty($sections))
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($sections as $sec)
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700">
                                                        {{ $sec }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">No sections</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        @if($canManage)
                                            {{-- Edit Faculty (JS inline form) --}}
                                            <button class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50"
                                                    onclick='editFaculty(@json($f))'>
                                                Edit
                                            </button>

                                            {{-- Delete Faculty --}}
                                            <form action="{{ route('faculties.destroy', $f) }}" method="POST"
                                                  class="inline-block ml-1">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                                                    onclick="return confirm('Delete this faculty?')">
                                                    Delete
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[11px] text-gray-400 italic">
                                                View only
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        No faculties.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- RIGHT: Faculty + Section forms --}}
            <div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

                    {{-- Wrap forms: active for super admin, blurred for others --}}
                    <div class="{{ $canManage ? '' : 'opacity-40 blur-sm pointer-events-none select-none' }}">
                        {{-- FACULTY FORM --}}
                        <h2 id="fFormTitle" class="text-lg font-semibold">Add Faculty</h2>
                        <form method="POST" id="facultyForm" action="{{ route('faculties.store') }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="_method" id="fFormMethod" value="POST">
                            <input type="hidden" name="id" id="faculty_id">

                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Code</label>
                                <input name="code" id="code" class="w-full rounded-lg border px-3 py-2" required
                                       placeholder="e.g., CSIT">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Name</label>
                                <input name="name" id="name" class="w-full rounded-lg border px-3 py-2" required
                                       placeholder="e.g., Computer Science & IT">
                            </div>

                            <div class="pt-2 flex items-center justify-between">
                                <button
                                    class="rounded-xl bg-gray-900 text-white px-4 py-2 text-sm font-semibold hover:bg-gray-800">
                                    Save
                                </button>
                                <button type="button" onclick="resetFacultyForm()"
                                        class="rounded-xl border px-4 py-2 text-sm">
                                    Clear
                                </button>
                            </div>
                        </form>

                        {{-- SECTION FORM --}}
                        <div class="mt-8 border-t pt-5">
                            <h2 class="text-lg font-semibold mb-3">Add Section</h2>
                            <form method="POST" action="{{ route('sections.store') }}" class="space-y-3">
                                @csrf

                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Faculty</label>
                                    <select name="faculty_id" class="w-full rounded-lg border px-3 py-2" required>
                                        <option value="">-- Select Faculty --</option>
                                        @foreach ($faculties as $f)
                                            <option value="{{ $f->id }}">{{ $f->code }} — {{ $f->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Section Name</label>
                                    <input name="name" class="w-full rounded-lg border px-3 py-2" required
                                           placeholder="e.g., A, B, C">
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Short Code (optional)</label>
                                    <input name="code" class="w-full rounded-lg border px-3 py-2" placeholder="e.g., A">
                                </div>

                                <div class="pt-2">
                                    <button
                                        class="rounded-xl bg-gray-900 text-white px-4 py-2 text-sm font-semibold hover:bg-gray-800">
                                        Save Section
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @unless($canManage)
                        <p class="mt-3 text-[11px] text-amber-700 font-medium">
                            You can view faculties and sections, but only the Super Admin can create or edit them.
                        </p>
                    @endunless
                </div>
            </div>
        </div>

        {{-- 🔵 DEPARTMENT FORM (unchanged layout, but locked for non-admin) --}}
        <div class="mt-8 border-t pt-5">
            <h2 class="text-lg font-semibold mb-3">Create Department</h2>

            <div class="{{ $canManage ? '' : 'opacity-40 blur-sm pointer-events-none select-none' }}">
                <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-3">
                    @csrf

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Department Code</label>
                        <input name="code" class="w-full rounded-lg border px-3 py-2" required
                               placeholder="e.g., ECE_COMP, CIVIL">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Department Name</label>
                        <input name="name" class="w-full rounded-lg border px-3 py-2" required
                               placeholder="e.g., Electronics & Computer Engineering Department">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Faculties in this Department</label>
                        <div class="max-h-40 overflow-y-auto border rounded-lg px-3 py-2 space-y-1">
                            @foreach ($faculties as $f)
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="faculty_ids[]" value="{{ $f->id }}"
                                           class="rounded border-gray-300">
                                    <span>{{ $f->code }} — {{ $f->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-[10px] text-gray-400">
                            Select one or more faculties. For example, select both EC & COMP for a combined department.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button class="rounded-xl bg-blue-600 text-white px-4 py-2 text-sm font-semibold hover:bg-blue-700">
                            Save Department
                        </button>
                    </div>
                </form>
            </div>

            @unless($canManage)
                <p class="mt-2 text-[11px] text-amber-700 font-medium">
                    Only the Super Admin can create or modify departments.
                </p>
            @endunless
        </div>
    </div>

    <script>
        function editFaculty(f) {
            document.getElementById('fFormTitle').textContent = 'Edit Faculty ' + f.code;
            document.getElementById('facultyForm').action = '/admin/faculties/' + f.id;
            document.getElementById('fFormMethod').value = 'PUT';
            document.getElementById('faculty_id').value = f.id;
            document.getElementById('code').value = f.code;
            document.getElementById('name').value = f.name;
        }

        function resetFacultyForm() {
            document.getElementById('fFormTitle').textContent = 'Add Faculty';
            document.getElementById('facultyForm').action = '{{ route('faculties.store') }}';
            document.getElementById('fFormMethod').value = 'POST';
            document.getElementById('facultyForm').reset();
        }
    </script>
@endsection

{{-- resources/views/Backend/departments/edit_roles.blade.php --}}
@extends('Backend.layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-4 space-y-4 text-xs sm:text-sm">
    <div class="flex items-center justify-between">
        <h1 class="text-base sm:text-lg font-semibold text-slate-900">
            Manage Roles – {{ $department->name }} ({{ $department->code }})
        </h1>
        <a href="{{ route('admin.departments.index') }}"
           class="text-[11px] sm:text-xs text-slate-500 hover:underline">
            ← Back to Departments
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 space-y-4">

        {{-- Info: faculties under this department --}}
        <div class="text-[11px] sm:text-xs text-slate-600">
            <span class="font-medium">Faculties in this Department:</span>
            @if($department->faculties->isEmpty())
                <span class="text-slate-400">No faculties attached. Please attach faculties when creating the department.</span>
            @else
                @foreach($department->faculties as $fac)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-slate-100 mr-1 mt-1">
                        {{ $fac->code }} — {{ $fac->name }}
                    </span>
                @endforeach
            @endif
            <p class="mt-1 text-[10px] text-slate-400">
                Only teachers from the above faculties will appear in the dropdowns.
            </p>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.departments.storeRoles', $department) }}" class="space-y-4">
            @csrf

            {{-- HOD --}}
            <div>
                <label class="block text-[11px] sm:text-xs font-medium text-slate-700">
                    Head of Department (HOD)
                </label>
                <select name="hod_id"
                        class="mt-1 block w-full border border-slate-200 rounded-md px-2 py-1.5 text-xs focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select HOD --</option>
                    @php
                        $currentHodId = optional($department->hod)->teacher_id;
                    @endphp
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}"
                            @if($currentHodId == $teacher->id) selected @endif>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Deputy HODs --}}
            <div>
                <label class="block text-[11px] sm:text-xs font-medium text-slate-700">
                    Deputy HODs (Max 2)
                </label>
                @php
                    $existingDeputies = $department->deputyHods->pluck('teacher_id')->toArray();
                @endphp

                @for($i = 0; $i < 2; $i++)
                    <select name="deputy_ids[]"
                            class="mt-1 block w-full border border-slate-200 rounded-md px-2 py-1.5 text-xs focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Deputy HOD --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}"
                                @if(isset($existingDeputies[$i]) && $existingDeputies[$i] == $teacher->id) selected @endif>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                @endfor
                <p class="mt-1 text-[10px] text-slate-400">
                    Leave blank if you don't want deputy HOD.
                </p>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="inline-flex items-center px-4 py-1.5 rounded-md text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                    Save Roles
                </button>
                <a href="{{ route('admin.departments.index') }}"
                   class="ml-2 text-xs text-slate-500 hover:underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

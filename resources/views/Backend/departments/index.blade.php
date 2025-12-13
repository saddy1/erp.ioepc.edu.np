{{-- resources/views/Backend/departments/index.blade.php --}}
@extends('Backend.layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-4 space-y-4 text-xs sm:text-sm">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-800 text-[11px] sm:text-xs">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-red-800 text-[11px] sm:text-xs">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-base sm:text-lg font-semibold text-slate-900">
            Departments & HOD Management
        </h1>

        {{-- Shortcut to faculty page where department can be created --}}
        <a href="{{ route('faculties.index') }}"
           class="inline-flex items-center rounded-xl border border-blue-300 px-3 py-1.5 text-[11px] sm:text-xs font-semibold text-blue-700 hover:bg-blue-50">
            + Create Department
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-2xl border border-slate-200 divide-y divide-slate-100">
        @forelse($departments as $dept)
            <div class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="space-y-1">
                    <div class="font-semibold text-slate-900">
                        {{ $dept->name }}
                        <span class="text-slate-400 text-[10px] sm:text-[11px]">({{ $dept->code }})</span>
                    </div>

                    <div class="text-[11px] sm:text-xs text-slate-500">
                        <span class="font-medium text-slate-700">Faculties:</span>
                        @if($dept->faculties->isEmpty())
                            <span class="text-slate-400">No faculties attached</span>
                        @else
                            <span>
                                @foreach($dept->faculties as $fac)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-slate-100 mr-1">
                                        {{ $fac->code }}
                                    </span>
                                @endforeach
                            </span>
                        @endif
                    </div>

                    <div class="text-[11px] sm:text-xs">
                        <span class="font-medium text-slate-700">HOD:</span>
                        @if($dept->hod && $dept->hod->teacher)
                            <span class="ml-1">{{ $dept->hod->teacher->name }}</span>
                        @else
                            <span class="ml-1 text-slate-400">Not assigned</span>
                        @endif
                    </div>

                    <div class="text-[11px] sm:text-xs">
                        <span class="font-medium text-slate-700">Deputy HOD(s):</span>
                        @forelse($dept->deputyHods as $role)
                            @if($role->teacher)
                                <span class="ml-1">{{ $role->teacher->name }}</span>
                            @endif
                        @empty
                            <span class="ml-1 text-slate-400">Not assigned</span>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('admin.departments.editRoles', $dept) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] sm:text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                        Manage Roles
                    </a>
                </div>
            </div>
        @empty
            <div class="p-4 text-center text-sm text-slate-400">
                No departments defined yet. Use "Create Department" to add one.
            </div>
        @endforelse
    </div>
</div>
@endsection

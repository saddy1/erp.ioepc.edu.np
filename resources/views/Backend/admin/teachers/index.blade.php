@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-3 sm:p-4 text-xs">

    {{-- Flash messages --}}
    @if(session('ok'))
        <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50/80 px-4 py-3 text-emerald-800 shadow-sm">
            {{ session('ok') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-300 bg-rose-50/90 px-4 py-3 text-rose-800 shadow-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Page header --}}
    <div class="mb-5 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-slate-900 tracking-tight">
                    Teachers
                </h1>
                <p class="text-[11px] sm:text-[12px] text-slate-500 mt-0.5">
                    Manage teacher records, departments and login details from one place.
                </p>
            </div>
        </div>
        {{-- Attractive underline / line --}}
        <div class="mt-3 h-1 w-24 rounded-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-400"></div>
    </div>

    {{-- Filters --}}
    <div class="mb-5 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-slate-50/70 p-3 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2 mb-3">
            <h2 class="text-[12px] sm:text-[13px] font-semibold text-slate-800 flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700">
                    F
                </span>
                Filter & Search
            </h2>
        </div>

        <form method="GET" action="{{ route('admin.teachers.index') }}"
              class="grid grid-cols-1 sm:grid-cols-4 gap-3 sm:gap-4 text-[11px]">

            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Faculty / Department</label>
                <select name="faculty_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/70 focus:border-indigo-500">
                    <option value="">All</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" @selected($filters['faculty_id']==$f->id)>
                            {{ $f->code ?? '' }} {{ $f->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Status</label>
                <select name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/70 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="1" @selected($filters['status']==='1')>Active</option>
                    <option value="0" @selected($filters['status']==='0')>Inactive</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-700">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/70 focus:border-indigo-500"
                       placeholder="Name / code / email">
            </div>

            <div class="flex items-end justify-end gap-2">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3.5 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/80">
                    Filter
                </button>
                <a href="{{ route('admin.teachers.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300/70">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Create form --}}
    <div class="mb-5 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-3 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2 mb-3">
            <h2 class="text-[13px] font-semibold text-slate-900 flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white">
                    +
                </span>
                Add Teacher
            </h2>
            <span class="text-[10px] uppercase tracking-wide text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                New entry
            </span>
        </div>

        <form method="POST" action="{{ route('admin.teachers.store') }}"
              class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 text-[11px]">
            @csrf

            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Code <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/80 focus:border-indigo-500"
                       placeholder="T001" required>
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/80 focus:border-indigo-500"
                       required>
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Faculty / Department <span class="text-red-500">*</span></label>
                <select name="faculty_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/80 focus:border-indigo-500"
                        required>
                    <option value="">Select</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" @selected(old('faculty_id')==$f->id)>
                            {{ $f->code ?? '' }} {{ $f->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/80 focus:border-indigo-500"
                       placeholder="optional">
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/80 focus:border-indigo-500"
                       placeholder="optional">
            </div>

            <div class="space-y-1">
                <label class="block font-medium text-slate-800">Password (for teacher login)</label>
                <input type="password" name="password"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/80 focus:border-indigo-500"
                       placeholder="min 6 chars">
            </div>

            <div class="flex items-center gap-2 sm:col-span-1">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                       {{ old('is_active', 1) ? 'checked' : '' }}>
                <label for="is_active" class="font-medium text-slate-800">Active</label>
            </div>

            <div class="sm:col-span-3 flex justify-end items-end pt-1">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/80">
                    Save Teacher
                </button>
            </div>
        </form>
    </div>

    {{-- List --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-3 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2 mb-3">
            <h2 class="text-[13px] font-semibold text-slate-900 flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white">
                    L
                </span>
                Teacher List
            </h2>
            <span class="text-[10px] text-slate-500">
                Total: <span class="font-semibold text-slate-800">{{ $teachers->total() }}</span>
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full border-collapse text-[11px]">
                <thead>
                <tr class="bg-slate-50/90 text-slate-700">
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Code</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Name</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Faculty</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Email</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Phone</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Status</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-left">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($teachers as $t)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-3 py-2 whitespace-nowrap font-semibold text-slate-800">
                            {{ $t->code }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-800">
                            {{ $t->name }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-700">
                            {{ $t->faculty->code ?? '' }} {{ $t->faculty->name ?? '' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-700">
                            {{ $t->email ?? '-' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-700">
                            {{ $t->phone ?? '-' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if($t->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-medium text-emerald-700 border border-emerald-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-medium text-slate-700 border border-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('admin.teachers.edit', $t) }}"
                                   class="inline-flex items-center rounded-lg bg-amber-500 px-2.5 py-1 text-[10px] font-semibold text-white hover:bg-amber-600">
                                    Edit
                                </a>
                                <form method="POST"
                                      action="{{ route('admin.teachers.destroy', $t) }}"
                                      onsubmit="return confirm('Delete this teacher?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center rounded-lg bg-rose-500 px-2.5 py-1 text-[10px] font-semibold text-white hover:bg-rose-600">
                                        Del
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-slate-500 text-[11px]">
                            No teachers found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex justify-between items-center text-[11px] text-slate-500">
            <div>
                Showing
                <span class="font-semibold text-slate-800">{{ $teachers->firstItem() ?? 0 }}</span>
                to
                <span class="font-semibold text-slate-800">{{ $teachers->lastItem() ?? 0 }}</span>
                of
                <span class="font-semibold text-slate-800">{{ $teachers->total() }}</span>
                results
            </div>
            <div class="text-right">
                {{ $teachers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

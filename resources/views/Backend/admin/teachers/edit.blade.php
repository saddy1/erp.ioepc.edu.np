@extends('Backend.layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-3 sm:p-4 text-xs">

    @if($errors->any())
        <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-base sm:text-lg font-semibold mb-3">Edit Teacher</h1>

    <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}"
          class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px] rounded-2xl border border-gray-200 bg-white p-3 sm:p-4 shadow-sm">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-medium">Code <span class="text-red-500">*</span></label>
            <input type="text" name="code" value="{{ old('code', $teacher->code) }}"
                   class="w-full border-gray-300 rounded-lg" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $teacher->name) }}"
                   class="w-full border-gray-300 rounded-lg" required>
        </div>

        <div>
            <label class="block mb-1 font-medium">Faculty / Department <span class="text-red-500">*</span></label>
            <select name="faculty_id" class="w-full border-gray-300 rounded-lg" required>
                @foreach($faculties as $f)
                    <option value="{{ $f->id }}" @selected(old('faculty_id', $teacher->faculty_id)==$f->id)>
                        {{ $f->code ?? '' }} {{ $f->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email', $teacher->email) }}"
                   class="w-full border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block mb-1 font-medium">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $teacher->phone) }}"
                   class="w-full border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block mb-1 font-medium">New Password (optional)</label>
            <input type="password" name="password"
                   class="w-full border-gray-300 rounded-lg"
                   placeholder="Leave blank to keep existing">
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   class="rounded border-gray-300" {{ old('is_active', $teacher->is_active) ? 'checked' : '' }}>
            <label for="is_active" class="font-medium">Active</label>
        </div>

        <div class="sm:col-span-2 flex justify-between items-center pt-2">
            <a href="{{ route('admin.teachers.index') }}"
               class="px-3 py-1.5 rounded-lg border border-gray-300 text-[11px]">
                Back
            </a>
            <button type="submit"
                    class="px-4 py-1.5 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

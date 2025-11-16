@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Employees</h1>
    <a href="{{ route('employees.create') }}" class="rounded-xl bg-gray-900 text-white px-4 py-2.5 text-sm">+ Add Employee</a>
  </div>

  @if (session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
      {{ session('success') }}
    </div>
  @endif

  <form method="GET" class="mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name/email/atten no"
           class="w-full sm:w-96 rounded-lg border px-3 py-2">
  </form>

  <div class="overflow-x-auto rounded-xl border">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">S.N</th>
          <th class="px-3 py-2 text-left">Full Name</th>
          <th class="px-3 py-2 text-left">Contact</th>
          <th class="px-3 py-2 text-left">Employee Type</th>
        
          <th class="px-3 py-2 text-left">Active</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($employees as $i => $e)
          <tr class="border-t">
            <td class="px-3 py-2">{{ $employees->firstItem() + $i }}</td>
            <td class="px-3 py-2">{{ $e->full_name }}</td>
            <td class="px-3 py-2">{{ $e->contact ?? '—' }}</td>
            <td class="px-3 py-2">{{ ucfirst($e->employee_type) }}</td>
            <td class="px-3 py-2">{{ $e->is_active ? 'Yes' : 'No' }}</td>
            <td class="px-3 py-2 text-right">
              <a href="{{ route('employees.edit',$e) }}" class="text-blue-600 hover:underline mr-3">Edit</a>
              <form action="{{ route('employees.destroy',$e) }}" method="POST" class="inline"
                    onsubmit="return confirm('Delete this employee?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $employees->links() }}
  </div>
</div>
@endsection

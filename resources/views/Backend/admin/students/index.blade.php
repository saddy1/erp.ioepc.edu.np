@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  @if(session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc list-inside text-sm">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- List + filters --}}
    <div class="lg:col-span-2">
      <div class="mb-3 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Students</h1>
      </div>

      <form class="mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
          <select name="faculty_id" class="w-full rounded-lg border px-3 py-2">
            <option value="">All Faculties</option>
            @foreach($faculties as $f)
              <option value="{{ $f->id }}" @selected(request('faculty_id')==$f->id)>{{ $f->code }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <input type="number" name="semester" min="1" max="12" class="w-full rounded-lg border px-3 py-2"
                 placeholder="Semester" value="{{ request('semester') }}">
        </div>
        <div class="sm:col-span-2">
          <input type="text" name="search" class="w-full rounded-lg border px-3 py-2"
                 placeholder="Name or Symbol" value="{{ request('search') }}">
        </div>
        <div>
          <button class="w-full rounded-xl border px-4 py-2 text-sm font-semibold">Filter</button>
        </div>
      </form>

      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Symbol</th>
              <th class="px-4 py-3 text-left font-semibold">Name</th>
              <th class="px-4 py-3 text-left font-semibold">Faculty</th>
              <th class="px-4 py-3 text-left font-semibold">Sem</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse($students as $s)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2">{{ $s->symbol_no }}</td>
                <td class="px-4 py-2">{{ $s->name }}</td>
                <td class="px-4 py-2">{{ $s->faculty?->code }}</td>
                <td class="px-4 py-2">{{ $s->semester }}</td>
                <td class="px-4 py-2 text-right">
                    <button class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50"
                            onclick='editStudent(@json($s))'>Edit</button>
                  <form action="{{ route('students.destroy',$s) }}" method="POST" class="inline-block ml-1">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                      onclick="return confirm('Delete this student?')">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No students found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $students->links() }}
      </div>
    </div>

    {{-- Create/Edit --}}
    <div>
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 id="sFormTitle" class="text-lg font-semibold">Add Student</h2>
        <form method="POST" id="studentForm" action="{{ route('students.store') }}" class="mt-4 space-y-3">
          @csrf
          <input type="hidden" name="_method" id="sFormMethod" value="POST">
          <input type="hidden" name="id" id="student_id">

          <div>
            <label class="block text-xs text-gray-600 mb-1">Name</label>
            <input name="name" id="s_name" class="w-full rounded-lg border px-3 py-2" required>
          </div>

          <div>
            <label class="block text-xs text-gray-600 mb-1">Symbol No</label>
            <input name="symbol_no" id="s_symbol_no" class="w-full rounded-lg border px-3 py-2" required>
          </div>

          <div>
            <label class="block text-xs text-gray-600 mb-1">Faculty</label>
            <select name="faculty_id" id="s_faculty_id" class="w-full rounded-lg border px-3 py-2" required>
              <option value="">-- Select --</option>
              @foreach($faculties as $f)
                <option value="{{ $f->id }}">{{ $f->code }} — {{ $f->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs text-gray-600 mb-1">Semester</label>
            <input type="number" min="1" max="12" name="semester" id="s_semester"
                   class="w-full rounded-lg border px-3 py-2" required>
          </div>

          <div class="pt-2 flex items-center justify-between">
            <button class="rounded-xl bg-gray-900 text-white px-4 py-2 text-sm font-semibold hover:bg-gray-800">Save</button>
            <button type="button" onclick="resetStudentForm()" class="rounded-xl border px-4 py-2 text-sm">Clear</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
function editStudent(s){
  document.getElementById('sFormTitle').textContent = 'Edit Student ' + s.symbol_no;
  document.getElementById('studentForm').action = '/admin/students/' + s.id;
  document.getElementById('sFormMethod').value = 'PUT';
  document.getElementById('student_id').value = s.id;
  document.getElementById('s_name').value = s.name;
  document.getElementById('s_symbol_no').value = s.symbol_no;
  document.getElementById('s_faculty_id').value = s.faculty_id;
  document.getElementById('s_semester').value = s.semester;
}
function resetStudentForm(){
  document.getElementById('sFormTitle').textContent = 'Add Student';
  document.getElementById('studentForm').action = '{{ route('students.store') }}';
  document.getElementById('sFormMethod').value = 'POST';
  document.getElementById('studentForm').reset();
}
</script>
@endsection

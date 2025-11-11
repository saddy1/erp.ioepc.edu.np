@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
  @if(session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
      {{ session('ok') }}
    </div>
  @endif
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc list-inside text-sm">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-5 gap-3">
    <div>
      <h1 class="text-2xl font-bold">Faculty – Semester Subjects</h1>
      <p class="text-sm text-gray-500">Define once, reuse later when creating routines.</p>
    </div>
  </div>

  {{-- Create/Edit form --}}
  <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm mb-6">
    <h2 id="formTitle" class="text-lg font-semibold">Add Subject</h2>
    <form method="POST" id="subjectForm" action="{{ route('faculty_subjects.store') }}"
          class="mt-3 grid sm:grid-cols-4 gap-3">
      @csrf
      <input type="hidden" name="_method" id="formMethod" value="POST">

      <div>
        <label class="block text-xs text-gray-600 mb-1">Faculty</label>
        <select name="faculty_id" id="faculty_id"
                class="w-full rounded-lg border px-3 py-2" required>
          <option value="">— Select —</option>
          @foreach($faculties as $f)
            <option value="{{ $f->id }}">{{ $f->code }} — {{ $f->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-600 mb-1">Semester</label>
        <input type="number" name="semester" id="semester"
               class="w-full rounded-lg border px-3 py-2" min="1" max="12" required>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Subject Code</label>
        <input name="subject_code" id="subject_code"
               class="w-full rounded-lg border px-3 py-2" placeholder="e.g., EX 501" required>
      </div>

      <div class="sm:col-span-4 flex items-center justify-end gap-3 mt-2">
        <button class="rounded-xl bg-gray-900 text-white px-4 py-2 text-sm font-semibold hover:bg-gray-800">
          Save
        </button>
        <button type="button" onclick="resetForm()"
                class="rounded-xl border px-4 py-2 text-sm">Clear</button>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 border-b">
        <tr>
          <th class="px-4 py-2 text-left">Faculty</th>
          <th class="px-4 py-2 text-left">Semester</th>
          <th class="px-4 py-2 text-left">Subject Code</th>
          <th class="px-4 py-2 text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr class="border-t">
            <td class="px-4 py-2">{{ $r->faculty->code }} — {{ $r->faculty->name }}</td>
            <td class="px-4 py-2">{{ $r->semester }}</td>
            <td class="px-4 py-2">{{ $r->subject_code }}</td>
            <td class="px-4 py-2 text-right space-x-1">
              <button class="rounded-lg border px-3 py-1 text-xs hover:bg-gray-50"
                      onclick='editRow(@json($r))'>Edit</button>
              <form action="{{ route('faculty_subjects.destroy',$r) }}" method="POST"
                    class="inline-block"
                    onsubmit="return confirm('Delete this subject?')">
                @csrf @method('DELETE')
                <button class="rounded-lg border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-gray-500 py-3">No subjects defined.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
function editRow(row){
  document.getElementById('formTitle').textContent = 'Edit Subject';
  document.getElementById('subjectForm').action = '/admin/faculty-subjects/' + row.id;
  document.getElementById('formMethod').value = 'PUT';
  document.getElementById('faculty_id').value = row.faculty_id;
  document.getElementById('semester').value = row.semester;
  document.getElementById('subject_code').value = row.subject_code;
}
function resetForm(){
  document.getElementById('formTitle').textContent = 'Add Subject';
  document.getElementById('subjectForm').action = '{{ route('faculty_subjects.store') }}';
  document.getElementById('formMethod').value = 'POST';
  document.getElementById('subjectForm').reset();
}
</script>
@endsection

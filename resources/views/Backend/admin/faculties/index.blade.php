@extends('Backend.layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
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
    <div class="lg:col-span-2">
      <h1 class="text-2xl font-bold mb-3">Faculties</h1>
      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Code</th>
              <th class="px-4 py-3 text-left font-semibold">Name</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse($faculties as $f)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2">{{ $f->code }}</td>
                <td class="px-4 py-2">{{ $f->name }}</td>
                <td class="px-4 py-2 text-right">
                  <button class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50"
                          onclick='editFaculty(@json($f))'>Edit</button>
                  <form action="{{ route('faculties.destroy',$f) }}" method="POST" class="inline-block ml-1">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                      onclick="return confirm('Delete this faculty?')">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No faculties.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-4">
        {{ $faculties->links() }}
      </div>
    </div>

    <div>
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 id="fFormTitle" class="text-lg font-semibold">Add Faculty</h2>
        <form method="POST" id="facultyForm" action="{{ route('faculties.store') }}" class="mt-4 space-y-3">
          @csrf
          <input type="hidden" name="_method" id="fFormMethod" value="POST">
          <input type="hidden" name="id" id="faculty_id">

          <div>
            <label class="block text-xs text-gray-600 mb-1">Code</label>
            <input name="code" id="code" class="w-full rounded-lg border px-3 py-2" required placeholder="e.g., CSIT">
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Name</label>
            <input name="name" id="name" class="w-full rounded-lg border px-3 py-2" required placeholder="e.g., Computer Science & IT">
          </div>

          <div class="pt-2 flex items-center justify-between">
            <button class="rounded-xl bg-gray-900 text-white px-4 py-2 text-sm font-semibold hover:bg-gray-800">Save</button>
            <button type="button" onclick="resetFacultyForm()" class="rounded-xl border px-4 py-2 text-sm">Clear</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function editFaculty(f){
  document.getElementById('fFormTitle').textContent = 'Edit Faculty ' + f.code;
  document.getElementById('facultyForm').action = '/admin/faculties/' + f.id;
  document.getElementById('fFormMethod').value = 'PUT';
  document.getElementById('faculty_id').value = f.id;
  document.getElementById('code').value = f.code;
  document.getElementById('name').value = f.name;
}
function resetFacultyForm(){
  document.getElementById('fFormTitle').textContent = 'Add Faculty';
  document.getElementById('facultyForm').action = '{{ route('faculties.store') }}';
  document.getElementById('fFormMethod').value = 'POST';
  document.getElementById('facultyForm').reset();
}
</script>
@endsection

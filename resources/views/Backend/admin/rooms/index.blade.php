@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  {{-- Flash + errors --}}
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
    {{-- Table --}}
    <div class="lg:col-span-2">
      <div class="mb-3 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Rooms</h1>
      </div>

      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Room</th>
              <th class="px-4 py-3 text-left font-semibold">Col1</th>
              <th class="px-4 py-3 text-left font-semibold">Col2</th>
              <th class="px-4 py-3 text-left font-semibold">Col3</th>
              <th class="px-4 py-3 text-left font-semibold">Benches</th>
              <th class="px-4 py-3 text-left font-semibold">Seats</th>
              <th class="px-4 py-3 text-left font-semibold">Fac/Room</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse($rooms as $r)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2">{{ $r->room_no }}</td>
                <td class="px-4 py-2">{{ $r->rows_col1 }}</td>
                <td class="px-4 py-2">{{ $r->rows_col2 }}</td>
                <td class="px-4 py-2">{{ $r->rows_col3 }}</td>
                <td class="px-4 py-2">{{ $r->computed_total_benches }}</td>
                <td class="px-4 py-2">{{ $r->computed_total_seats }}</td>
                <td class="px-4 py-2">{{ $r->faculties_per_room }}</td>
                <td class="px-4 py-2 text-right">
                  <button
                    class="inline-flex items-center rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50"
                    onclick='editRoom(@json($r))'>Edit</button>
                  <form action="{{ route('rooms.destroy',$r) }}" method="POST" class="inline-block ml-1">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                            onclick="return confirm('Delete this room?')">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No rooms yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $rooms->links() }}
      </div>
    </div>

    {{-- Create/Edit form --}}
    <div>
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 id="roomFormTitle" class="text-lg font-semibold">Add Room</h2>
        <form method="POST" id="roomForm" action="{{ route('rooms.store') }}" class="mt-4 space-y-3">
          @csrf
          <input type="hidden" name="_method" id="roomFormMethod" value="POST">
          <input type="hidden" name="id" id="room_id">

          <div>
            <label class="block text-xs text-gray-600 mb-1">Room No</label>
            <input name="room_no" id="room_no" class="w-full rounded-lg border px-3 py-2" required>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs text-gray-600 mb-1">Rows (Col 1)</label>
              <input type="number" min="0" name="rows_col1" id="rows_col1" class="w-full rounded-lg border px-3 py-2" required>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Rows (Col 2)</label>
              <input type="number" min="0" name="rows_col2" id="rows_col2" class="w-full rounded-lg border px-3 py-2" required>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Rows (Col 3)</label>
              <input type="number" min="0" name="rows_col3" id="rows_col3" class="w-full rounded-lg border px-3 py-2" required>
            </div>
          </div>

          <div>
            <label class="block text-xs text-gray-600 mb-1">Faculties per Room (constant)</label>
            <input type="number" min="1" max="5" name="faculties_per_room" id="faculties_per_room" class="w-full rounded-lg border px-3 py-2" required>
          </div>

          <div class="pt-2 flex items-center justify-between">
            <button class="rounded-xl bg-gray-900 text-white px-4 py-2 text-sm font-semibold hover:bg-gray-800">Save</button>
            <button type="button" onclick="resetRoomForm()"
              class="rounded-xl border px-4 py-2 text-sm">Clear</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function editRoom(r){
  document.getElementById('roomFormTitle').textContent = 'Edit Room ' + r.room_no;
  document.getElementById('roomForm').action = '/admin/rooms/' + r.id;
  document.getElementById('roomFormMethod').value = 'PUT';
  document.getElementById('room_id').value = r.id;
  document.getElementById('room_no').value = r.room_no;
  document.getElementById('rows_col1').value = r.rows_col1;
  document.getElementById('rows_col2').value = r.rows_col2;
  document.getElementById('rows_col3').value = r.rows_col3;
  document.getElementById('faculties_per_room').value = r.faculties_per_room;
}
function resetRoomForm(){
  document.getElementById('roomFormTitle').textContent = 'Add Room';
  document.getElementById('roomForm').action = '{{ route('rooms.store') }}';
  document.getElementById('roomFormMethod').value = 'POST';
  document.getElementById('roomForm').reset();
}
</script>
@endsection

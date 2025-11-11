{{-- resources/views/seatplan/create.blade.php --}}
@extends('Backend.layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">Create Seat Plan</h1>

  <form method="POST" action="{{ route('seatplan.store') }}" x-data="seatPlanForm()">
    @csrf

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm mb-1">Exam Date</label>
        <input type="date" name="exam_date" class="w-full border rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm mb-1">Semester</label>
        <input type="number" name="semester" min="1" max="12" class="w-full border rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm mb-1">Subject Code</label>
        <input type="text" name="subject_code" placeholder="e.g. MTH-301" class="w-full border rounded px-3 py-2" required>
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mt-4">
      <div>
        <label class="block text-sm mb-1">Start Time (optional)</label>
        <input type="text" name="start_time" placeholder="10:00" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm mb-1">Duration (minutes)</label>
        <input type="number" name="duration_min" min="0" class="w-full border rounded px-3 py-2">
      </div>
      <div class="md:col-span-1">
        <label class="block text-sm mb-1">Notes</label>
        <input type="text" name="notes" class="w-full border rounded px-3 py-2">
      </div>
    </div>

    <hr class="my-6">

    {{-- Room layouts (3 columns, variable rows each) --}}
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-semibold">Rooms & Bench Layout</h2>
      <button type="button" @click="addRoom()" class="px-3 py-2 bg-gray-900 text-white rounded">+ Add Room</button>
    </div>

    <template x-for="(r, idx) in rooms" :key="idx">
      <div class="mt-4 border rounded-lg p-4">
        <div class="grid md:grid-cols-5 gap-3 items-end">
          <div class="md:col-span-2">
            <label class="block text-sm mb-1">Room</label>
            <select class="w-full border rounded px-3 py-2" :name="`rooms[${idx}][room_id]`" required>
              <option value="">-- choose room --</option>
              @foreach($rooms as $rm)
                <option value="{{ $rm->id }}">{{ $rm->room_no }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">Rows (Col 1)</label>
            <input type="number" min="0" class="w-full border rounded px-3 py-2"
                   :name="`rooms[${idx}][rows_col1]`" x-model.number="r.rows_col1" required>
          </div>
          <div>
            <label class="block text-sm mb-1">Rows (Col 2)</label>
            <input type="number" min="0" class="w-full border rounded px-3 py-2"
                   :name="`rooms[${idx}][rows_col2]`" x-model.number="r.rows_col2" required>
          </div>
          <div>
            <label class="block text-sm mb-1">Rows (Col 3)</label>
            <input type="number" min="0" class="w-full border rounded px-3 py-2"
                   :name="`rooms[${idx}][rows_col3]`" x-model.number="r.rows_col3" required>
          </div>
          <div>
            <label class="block text-sm mb-1">Observers</label>
            <select class="w-full border rounded px-3 py-2"
                    :name="`rooms[${idx}][observers_required]`" x-model.number="r.observers_required" required>
              <option value="1">1</option>
              <option value="2">2</option>
            </select>
          </div>
          <div class="md:col-span-5 flex justify-end">
            <button type="button" @click="removeRoom(idx)" class="text-red-600 text-sm">Remove</button>
          </div>
        </div>
      </div>
    </template>

    <hr class="my-6">

    {{-- Allowed pairs (faculties that share the same subject and CAN sit together on a bench) --}}
    <h2 class="text-xl font-semibold mb-2">Same-Subject Faculty Pairs (allowed on same bench)</h2>
    <div class="space-y-3" x-data>
      <div class="text-sm text-slate-600">Example: CE with CSIT → select both and click “Add Pair”.</div>
      <div class="flex gap-2 items-end">
        <div>
          <label class="block text-sm mb-1">Faculty A</label>
          <select class="border rounded px-3 py-2" x-ref="fa">
            <option value="">-- select --</option>
            @foreach($faculties as $f)
              <option value="{{ $f->id }}">{{ $f->code }} — {{ $f->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1">Faculty B</label>
          <select class="border rounded px-3 py-2" x-ref="fb">
            <option value="">-- select --</option>
            @foreach($faculties as $f)
              <option value="{{ $f->id }}">{{ $f->code }} — {{ $f->name }}</option>
            @endforeach
          </select>
        </div>
        <button type="button" class="px-3 py-2 bg-emerald-600 text-white rounded"
                @click="$dispatch('addpair',{a:$refs.fa.value,b:$refs.fb.value})">Add Pair</button>
      </div>

      <div class="mt-3">
        <template x-for="(p, i) in pairs" :key="i">
          <div class="inline-flex items-center gap-2 border rounded-full px-3 py-1 mr-2 mb-2">
            <span x-text="p.label"></span>
            <input type="hidden" :name="`allowed_pairs[${i}]`" :value="p.value">
            <button type="button" @click="pairs.splice(i,1)" class="text-red-600">×</button>
          </div>
        </template>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg">Generate Seat Plan</button>
    </div>
  </form>
</div>

<script>
function seatPlanForm(){
  return {
    rooms: [],
    pairs: [],
    addRoom(){ this.rooms.push({rows_col1:0, rows_col2:0, rows_col3:0, observers_required:1}); },
    removeRoom(i){ this.rooms.splice(i,1); },
    init(){
      this.$el.addEventListener('addpair', (e)=>{
        const a = parseInt(e.detail.a||0), b = parseInt(e.detail.b||0);
        if(!a || !b || a===b) return;
        const label = `{{ $faculties->pluck('code','id')->toJson() }}`
        const map = JSON.parse(label);
        const val = `${a}:${b}`;
        if(!this.pairs.find(x=>x.value===val)){
          this.pairs.push({value: val, label: `${map[a]} + ${map[b]}`});
        }
      });
    }
  }
}
</script>
@endsection

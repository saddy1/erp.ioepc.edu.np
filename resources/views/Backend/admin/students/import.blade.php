@extends('Backend.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-4 text-sm">
  @if(session('ok'))
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
      {{ session('ok') }}
    </div>
  @endif
  @if($errors->any())
    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc list-inside text-xs">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <h1 class="text-lg font-semibold mb-4">Import Master Students (Batch / Department / Section)</h1>

  <form method="POST" action="{{ route('students.import') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

      {{-- Batch --}}
      <div>
        <label class="text-xs text-gray-700 mb-1 block">Batch <span class="text-red-500">*</span></label>
        <input type="text" name="batch" value="{{ old('batch') }}"
               class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="e.g. 2081" required>
      </div>

      {{-- Faculty --}}
      <div>
        <label class="text-xs text-gray-700 mb-1 block">Faculty / Department <span class="text-red-500">*</span></label>
        <select name="faculty_id" id="faculty_id" class="w-full rounded-lg border px-3 py-2 text-sm" required>
          <option value="">-- Select --</option>
          @foreach($faculties as $f)
            <option value="{{ $f->id }}" @selected(old('faculty_id') == $f->id)>
              {{ $f->code }} — {{ $f->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Sections --}}
      <div>
        <label class="text-xs text-gray-700 mb-1 block">Section <span class="text-red-500">*</span></label>

        <select name="section_id" id="section_id"
                class="w-full rounded-lg border px-3 py-2 text-sm"
                required disabled>
          <option value="">-- Select Faculty First --</option>

          @foreach ($faculties as $f)
            @foreach ($f->sections as $section)
              <option value="{{ $section->id }}"
                      data-faculty="{{ $f->id }}"
                      @selected(old('section_id') == $section->id)>
                {{ $section->name }} ({{ $f->code }})
              </option>
            @endforeach
          @endforeach
        </select>
      </div>

    </div>

    {{-- File upload --}}
    <div>
      <label class="text-xs text-gray-700 mb-1 block">CSV File <span class="text-red-500">*</span></label>
      <input type="file" name="file" accept=".csv,text/csv"
             class="w-full border rounded-lg px-3 py-2 text-sm"
             required>
      <p class="mt-2 text-[11px] text-gray-500">
        Expected columns (in order): <b>Roll No</b>, <b>Name</b>, <b>Contact</b>, <b>Email</b>, <b>Father Name</b>,
        <b>Mother Name</b>, <b>Gender</b>, <b>Municipal/VDC</b>, <b>Ward</b>, <b>District</b>, <b>Year</b>, <b>Part</b>.
      </p>
    </div>

    <div class="pt-2">
      <button type="submit" class="rounded-xl bg-gray-900 text-white px-6 py-2 text-sm font-semibold hover:bg-gray-800">
        Import Students
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const facultySelect  = document.getElementById('faculty_id');
  const sectionSelect  = document.getElementById('section_id');
  const allSectionOpts = Array.from(sectionSelect.querySelectorAll('option[data-faculty]'));

  function updateSections() {
    const facultyId = facultySelect.value;

    // Disable until a faculty is selected
    if (!facultyId) {
      sectionSelect.value = "";
      sectionSelect.disabled = true;
      sectionSelect.querySelector('option[value=""]').textContent = "-- Select Faculty First --";
      return;
    }

    // Enable dropdown
    sectionSelect.disabled = false;
    sectionSelect.querySelector('option[value=""]').textContent = "-- Select Section --";

    // Filter options
    allSectionOpts.forEach(opt => {
      const show = opt.dataset.faculty === facultyId;
      opt.hidden   = !show;
      opt.disabled = !show;
    });

    // If old() matches same faculty, keep selection
    const oldSelected = "{{ old('section_id') }}";
    if (oldSelected) {
      sectionSelect.value = oldSelected;
    } else {
      sectionSelect.value = "";
    }
  }

  updateSections();

  facultySelect.addEventListener('change', updateSections);
});
</script>
@endsection

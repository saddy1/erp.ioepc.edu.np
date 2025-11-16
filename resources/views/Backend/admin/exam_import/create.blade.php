@extends('Backend.layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
  {{-- Success Message with Details --}}
  @if(session('ok'))
    @php
      $message = session('ok');
      
      // Check if no students were imported
      $noImports = str_contains($message, 'No new students were imported');
      
      if (!$noImports) {
        // Parse the message to extract details
        preg_match('/Successfully imported (\d+) student/', $message, $countMatch);
        preg_match('/Exam: (.+)/', $message, $examMatch);
        preg_match('/Semester: (\d+) \| Batch: (.+)/', $message, $semesterMatch);
        
        $importedCount = $countMatch[1] ?? 0;
        $examTitle = $examMatch[1] ?? 'N/A';
        $semester = $semesterMatch[1] ?? 'N/A';
        $batch = $semesterMatch[2] ?? 'N/A';
        
        // Extract student list
        preg_match('/IMPORTED STUDENTS:\n((?:  • .+\n?)+)/', $message, $studentList);
        $students = isset($studentList[1]) ? array_filter(explode("\n", trim($studentList[1]))) : [];
      }
    @endphp

    @if($noImports)
      {{-- No new imports message --}}
      <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <div class="flex-1">
            <h3 class="text-amber-800 font-semibold text-sm mb-1">No New Students Imported</h3>
            <p class="text-amber-700 text-xs">{{ $message }}</p>
          </div>
        </div>
      </div>
    @else
      {{-- Success with imported students --}}
      <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4">
        <div class="flex items-start gap-3 mb-3">
          <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <div class="flex-1">
            <h3 class="text-emerald-800 font-semibold text-base">Import Completed Successfully!</h3>
            <div class="mt-2 space-y-1">
              <p class="text-emerald-700 text-sm">
                <span class="font-semibold">Exam:</span> {{ $examTitle }}
              </p>
              <p class="text-emerald-700 text-sm">
                <span class="font-semibold">Semester:</span> {{ $semester }} 
                <span class="mx-2">|</span>
                <span class="font-semibold">Batch:</span> {{ $batch }}
              </p>
            </div>
          </div>
        </div>

        {{-- Count Summary --}}
        <div class="bg-white rounded-lg border border-emerald-200 px-4 py-3 mb-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Total Students Imported</span>
            <span class="text-2xl font-bold text-emerald-600">{{ $importedCount }}</span>
          </div>
        </div>

        {{-- Student List (Collapsible) --}}
        <div class="bg-white rounded-lg border border-emerald-200 overflow-hidden">
          <button type="button" 
                  class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-emerald-50 transition-colors"
                  onclick="toggleStudentList()">
            <span class="text-sm font-semibold text-emerald-700">
              📋 View Imported Students ({{ $importedCount }})
            </span>
            <svg class="w-5 h-5 text-emerald-600 transform transition-transform" id="student-list-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div id="student-list" class="border-t border-emerald-100" style="display: none;">
            <div class="px-4 py-3 bg-emerald-50 max-h-96 overflow-y-auto">
              <div class="space-y-2">
                @foreach($students as $student)
                  @php
                    // Parse: • 2025001 — John Doe (Campus: PUR081BAG009)
                    preg_match('/•\s*(.+?)\s*—\s*(.+?)\s*\(Campus:\s*(.+?)\)/', $student, $parts);
                    $examRoll = $parts[1] ?? '';
                    $name = $parts[2] ?? '';
                    $campusRoll = $parts[3] ?? '';
                  @endphp
                  <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-emerald-100 hover:border-emerald-200 transition-colors">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="font-semibold text-gray-900 text-sm">{{ $name }}</span>
                      </div>
                      <div class="mt-1 flex items-center gap-3 text-xs text-gray-600">
                        <span class="inline-flex items-center gap-1">
                          <span class="font-medium text-gray-500">Exam Roll:</span>
                          <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $examRoll }}</span>
                        </span>
                        <span class="text-gray-300">•</span>
                        <span class="inline-flex items-center gap-1">
                          <span class="font-medium text-gray-500">Campus Roll:</span>
                          <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $campusRoll }}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endif

  {{-- Error Messages --}}
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <h4 class="font-semibold text-red-800 text-sm mb-2">Import Failed</h4>
          <ul class="space-y-1 text-sm text-red-800">
            @foreach($errors->all() as $e)
              <li class="flex items-start gap-2">
                <span class="text-red-500 flex-shrink-0">•</span>
                <span>{{ $e }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  <h1 class="text-2xl font-bold mb-5">Import Students for Exam</h1>

  <form method="GET" class="grid sm:grid-cols-3 gap-3 mb-6">
    <div>
      <label class="text-xs text-gray-600 font-medium">Exam (status = 0)</label>
      <select name="exam_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent" onchange="this.form.submit()">
        <option value="">— Select Exam —</option>
        @foreach($exams as $e)
          <option value="{{ $e->id }}" @selected(optional($exam)->id==$e->id)>
            {{ $e->exam_title }} ({{ strtoupper($e->semester) }})
          </option>
        @endforeach
      </select>
    </div>
    @if($exam)
      <div>
        <label class="text-xs text-gray-600 font-medium">Allowed Semesters ({{ $exam->semester }})</label>
        <input type="text" disabled value="{{ implode(', ', $allowedSems) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50">
      </div>
    @endif
  </form>

  @if($exam)
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold mb-4 text-gray-900">Import Configuration</h2>
    
    <form method="POST" action="{{ route('exam.import.store') }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <input type="hidden" name="exam_id" value="{{ $exam->id }}">

      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="text-xs text-gray-700 font-medium mb-1.5 block">Semester <span class="text-red-500">*</span></label>
          <select name="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent" required>
            <option value="">— Select Semester —</option>
            @foreach($allowedSems as $s)
              <option value="{{ $s }}">Semester {{ $s }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="text-xs text-gray-700 font-medium mb-1.5 block">Faculty / Program</label>
          <select name="faculty_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            <option value="">Auto from Roll (e.g., BAG)</option>
            @foreach($faculties as $f)
              <option value="{{ $f->id }}">{{ $f->code }} — {{ $f->name }}</option>
            @endforeach
          </select>
          <p class="text-[11px] text-gray-500 mt-1.5">
            If not set, program code from Campus Roll (e.g., <b>BAG</b>) will be used to find Faculty.code.
          </p>
        </div>

        <div class="flex items-end">
          <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="assume_practical_from_columns" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
            <span class="text-gray-700">Auto-assign Practical if <b>P</b> column exists</span>
          </label>
        </div>
      </div>

      <div>
        <label class="text-xs text-gray-700 font-medium mb-1.5 block">CSV File <span class="text-red-500">*</span></label>
        <input type="file" name="file" accept=".csv,text/csv" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800" required>
        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-xs text-blue-800 font-medium mb-1">📄 CSV Format Requirements:</p>
          <ul class="text-[11px] text-blue-700 space-y-0.5 ml-4 list-disc">
            <li>Required columns: <b>Campus Roll No</b>, <b>Exam RollNo</b>, <b>Token No</b>, <b>Name</b>, <b>Amount</b></li>
            <li>Subject columns format: <b>Subject Name (SUBCODE)</b> followed by <b>TH</b> and optionally <b>P</b> columns</li>
            <li>Example: <b>Engineering Mathematics II (ENSH151)</b> with <b>TH</b> and <b>P</b> columns below</li>
            <li>Use <b>1</b> to indicate student is taking that exam component</li>
          </ul>
        </div>
      </div>

      <div class="pt-2 flex items-center gap-3">
        <button type="submit" class="bg-gray-900 text-white rounded-xl px-6 py-2.5 text-sm font-semibold hover:bg-gray-800 transition-colors">
          Import Students
        </button>
        <span class="text-xs text-gray-500">All subjects must be configured for the selected semester before importing.</span>
      </div>
    </form>
  </div>
  @endif
</div>

<script>
function toggleStudentList() {
  const list = document.getElementById('student-list');
  const icon = document.getElementById('student-list-icon');
  
  if (list.style.display === 'none') {
    list.style.display = 'block';
    icon.style.transform = 'rotate(0deg)';
  } else {
    list.style.display = 'none';
    icon.style.transform = 'rotate(-90deg)';
  }
}
</script>
@endsection
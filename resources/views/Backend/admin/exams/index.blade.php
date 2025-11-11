@extends('Backend.layouts.app')

@section('content')
@php
  // Define the time conversion function once at the top
  if (!function_exists('convertTo12Hour')) {
    function convertTo12Hour($time) {
      $time = substr($time, 0, 5);
      list($h, $m) = explode(':', $time);
      $h = (int)$h;
      $period = $h >= 12 ? 'PM' : 'AM';
      $h = $h % 12 ?: 12;
      return sprintf('%d:%s %s', $h, $m, $period);
    }
  }
@endphp

<div class="max-w-7xl mx-auto p-4 sm:p-6">
  @if(session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc list-inside text-sm">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
      <h1 class="text-xl sm:text-2xl font-bold">Exam Details</h1>
      <div class="flex gap-2 text-xs">
        <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-800 font-medium">
          <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1"></span>Scheduled
        </span>
        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 font-medium">
          <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>Completed
        </span>
      </div>
    </div>
    <button onclick="openModal()" class="bg-gray-900 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-gray-800 transition shadow-sm inline-flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add Exam
    </button>
  </div>

  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
            <th class="px-4 py-3 text-left font-semibold">Semester</th>
            <th class="px-4 py-3 text-left font-semibold">Batch</th>
            <th class="px-4 py-3 text-left font-semibold">Exam Title</th>
            <th class="px-4 py-3 text-left font-semibold">Time</th>
            <th class="px-4 py-3 text-left font-semibold">First Exam (BS)</th>
            <th class="px-4 py-3 text-right font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($exams as $e)
            @php
              $isCompleted = $e->status == 1;
            @endphp
            <tr class="hover:bg-gray-50 transition {{ $isCompleted ? 'bg-green-50/30' : '' }}">
              <td class="px-4 py-2.5">
                @if($isCompleted)
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Completed
                  </span>
                @else
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    Scheduled
                  </span>
                @endif
              </td>
              <td class="px-4 py-2.5 capitalize font-medium text-gray-900">{{ $e->semester }}</td>
              <td class="px-4 py-2.5 capitalize text-gray-700">{{ $e->batch }}</td>
              <td class="px-4 py-2.5 font-medium text-gray-900">{{ $e->exam_title }}</td>
              <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                {{ convertTo12Hour($e->start_time) }} – {{ convertTo12Hour($e->end_time) }}
              </td>
              <td class="px-4 py-2.5 text-gray-700">{{ $e->first_exam_date_bs }}</td>
              <td class="px-4 py-2.5">
                <div class="flex items-center justify-end gap-2">
                  @if(!$isCompleted)
                    <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium hover:bg-gray-50 transition inline-flex items-center gap-1.5"
                            onclick='confirmEdit(@json($e))'>
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                      Edit
                    </button>

                    <form action="{{ route('exams.update-status', $e) }}" method="POST" class="inline-block" onsubmit="return confirmStatusChange(event, '{{ $e->exam_title }}')">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="status" value="1">
                      <button type="submit" class="rounded-lg border border-green-300 bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 hover:bg-green-100 transition inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Complete
                      </button>
                    </form>

                    <form action="{{ route('exams.destroy', $e) }}" method="POST" class="inline-block" onsubmit="return confirmDelete(event, '{{ $e->exam_title }}')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 transition inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                      </button>
                    </form>
                  @else
                    <span class="text-sm text-gray-500 italic">Completed (No actions)</span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No exams yet. Click "Add Exam" to create your first exam.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-4">{{ $exams->links() }}</div>
</div>

{{-- Modal --}}
<div id="examModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
    <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
      <h2 id="modalTitle" class="text-xl font-bold text-gray-900">Add Exam</h2>
      <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <form method="POST" id="examForm" action="{{ route('exams.store') }}" class="p-6 space-y-4">
      @csrf
      <input type="hidden" name="_method" id="formMethod" value="POST">
      <input type="hidden" name="id" id="exam_id">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Semester <span class="text-red-500">*</span></label>
        <select name="semester" id="semester" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
          <option value="">— Select Semester —</option>
          <option value="odd">Odd</option>
          <option value="even">Even</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Batch <span class="text-red-500">*</span></label>
        <select name="batch" id="batch" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
          <option value="">— Select Batch —</option>
          <option value="new">New</option>
          <option value="old">Old</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam Title <span class="text-red-500">*</span></label>
        <input name="exam_title" id="exam_title" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="e.g., New Course Regular">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Time <span class="text-red-500">*</span></label>
          <input type="time" name="start_time" id="start_time" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">End Time <span class="text-red-500">*</span></label>
          <input type="time" name="end_time" id="end_time" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">First Exam Date (BS) <span class="text-red-500">*</span></label>
        <input type="text" name="first_exam_date_bs" id="first_exam_date_bs" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="YYYY-MM-DD" required>
        <p class="text-xs text-gray-500 mt-1">Format: YYYY-MM-DD (BS)</p>
      </div>

      <div class="flex gap-3 pt-4 border-t border-gray-200">
        <button type="submit" class="flex-1 rounded-xl bg-gray-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-gray-800 transition shadow-sm">
          <span id="submitBtnText">Save Exam</span>
        </button>
        <button type="button" onclick="closeModal()" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Modal functions
function openModal() {
  document.getElementById('examModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('examModal').classList.add('hidden');
  document.body.style.overflow = '';
  resetExamForm();
}

// Close modal on backdrop click
document.getElementById('examModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeModal();
  }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeModal();
  }
});

// Confirmation for editing
function confirmEdit(exam) {
  const message = `Are you sure you want to edit "${exam.exam_title}"?`;
  if (confirm(message)) {
    editExam(exam);
  }
}

// Edit exam function
function editExam(e) {
  openModal();
  document.getElementById('modalTitle').textContent = 'Edit Exam';
  document.getElementById('submitBtnText').textContent = 'Update Exam';
  document.getElementById('examForm').action = '/admin/exams/' + e.id;
  document.getElementById('formMethod').value = 'PUT';
  document.getElementById('exam_id').value = e.id;

  document.getElementById('semester').value = e.semester;
  document.getElementById('batch').value = e.batch;
  document.getElementById('exam_title').value = e.exam_title;
  document.getElementById('start_time').value = e.start_time;
  document.getElementById('end_time').value = e.end_time;
  document.getElementById('first_exam_date_bs').value = e.first_exam_date_bs;
}

// Reset form
function resetExamForm() {
  document.getElementById('modalTitle').textContent = 'Add Exam';
  document.getElementById('submitBtnText').textContent = 'Save Exam';
  document.getElementById('examForm').action = '{{ route('exams.store') }}';
  document.getElementById('formMethod').value = 'POST';
  document.getElementById('exam_id').value = '';
  document.getElementById('examForm').reset();
}

// Confirmation for status change
function confirmStatusChange(event, examTitle) {
  event.preventDefault();
  const message = `Mark "${examTitle}" as COMPLETED?\n\nNote: Once marked as completed, you cannot edit or delete this exam.`;
  if (confirm(message)) {
    event.target.submit();
  }
  return false;
}

// Confirmation for deletion
function confirmDelete(event, examTitle) {
  event.preventDefault();
  const message = `Are you sure you want to DELETE "${examTitle}"?\n\nThis action cannot be undone!`;
  if (confirm(message)) {
    event.target.submit();
  }
  return false;
}
</script>
@endsection
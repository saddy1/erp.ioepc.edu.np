@extends('Backend.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 sm:p-6">
  @if(session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <h1 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6">Create Routine</h1>

  {{-- Exam selector (status=0 only) --}}
  <form method="GET" action="{{ route('routine.builder.create') }}" class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1">Exam (status = 0)</label>
      <select name="exam_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
        <option value="">— Select Exam —</option>
        @foreach($exams as $e)
          <option value="{{ $e->id }}" @selected(optional($exam)->id == $e->id)>{{ $e->exam_title }}</option>
        @endforeach
      </select>
    </div>

    @if($exam)
      @php
        function convertTo12Hour($time) {
          $time = substr($time, 0, 5);
          list($h, $m) = explode(':', $time);
          $h = (int)$h;
          $period = $h >= 12 ? 'PM' : 'AM';
          $h = $h % 12 ?: 12;
          return sprintf('%d:%s %s', $h, $m, $period);
        }
      @endphp
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Start Time</label>
        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" value="{{ convertTo12Hour($exam->start_time) }}" readonly>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">End Time</label>
        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" value="{{ convertTo12Hour($exam->end_time) }}" readonly>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Batch</label>
        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" value="{{ $exam->batch == 'new' ? 'New (1)' : 'Old (2)' }}" readonly>
      </div>
    @endif
  </form>

  @if($exam)
  {{-- Semester picker (from exam odd/even) --}}
  <form method="GET" action="{{ route('routine.builder.create') }}" class="mb-6 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="exam_id" value="{{ $exam->id }}">
    <div class="flex-1 min-w-[200px]">
      <label class="block text-xs font-medium text-gray-600 mb-1">Semester</label>
      <select name="semester" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
        <option value="">— Select Semester —</option>
        @foreach($allowedSems as $s)
          <option value="{{ $s }}" @selected($selectedSemester==$s)>Semester {{ $s }}</option>
        @endforeach
      </select>
    </div>
    <button class="border border-gray-300 rounded-lg px-5 py-2 text-sm bg-white hover:bg-gray-50 font-medium transition">Load</button>
  </form>
  @endif

  {{-- Current Routine (for the selected sem) --}}
  @if($exam && $selectedSemester)
    @php
      // Build matrix[date][faculty_id] = subject_code (BS date strings)
      $dates = $slots->pluck('exam_date')->unique()->values();
      $matrix = [];
      $subjectNames = []; // Store subject names for tooltip
      foreach ($slots as $slot) {
        $d = $slot->exam_date;
        foreach ($slot->subjects as $sub) {
          $matrix[$d][$sub->faculty_id] = $sub->subject_code;
          $subjectNames[$sub->subject_code] = $sub->subject?->name ?? $sub->subject_code;
        }
      }
      // meta by date for time
      $metaByDate = [];
      foreach ($dates as $d) {
        $first = $slots->firstWhere('exam_date', $d);
        if ($first) {
          $start = convertTo12Hour($first->start_time);
          $end = convertTo12Hour($first->end_time);
          $metaByDate[$d] = $start . ' – ' . $end;
        } else {
          $metaByDate[$d] = '';
        }
      }
      
      // Generate color palette for subjects
      $allSubjects = [];
      foreach($matrix as $date => $facData) {
        foreach($facData as $code) {
          if(!in_array($code, $allSubjects)) $allSubjects[] = $code;
        }
      }
      $colors = ['bg-blue-100 text-blue-800', 'bg-green-100 text-green-800', 'bg-purple-100 text-purple-800', 
                 'bg-pink-100 text-pink-800', 'bg-yellow-100 text-yellow-800', 'bg-indigo-100 text-indigo-800',
                 'bg-orange-100 text-orange-800', 'bg-teal-100 text-teal-800', 'bg-cyan-100 text-cyan-800',
                 'bg-lime-100 text-lime-800', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-800'];
      $subjectColors = [];
      foreach($allSubjects as $idx => $code) {
        $subjectColors[$code] = $colors[$idx % count($colors)];
      }
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-6">
      <div class="px-4 sm:px-5 py-3 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div class="font-semibold text-gray-900">Current Routine — Semester {{ $selectedSemester }}</div>
          <div class="text-xs sm:text-sm text-gray-600 flex flex-wrap gap-2 sm:gap-3">
            <span>
              <span class="text-gray-500">Exam:</span>
              <span class="font-medium">{{ $exam->exam_title }}</span>
            </span>
            <span>
              <span class="text-gray-500">Batch:</span>
              <span class="font-medium">{{ $batchNum == 1 ? 'New' : 'Old' }}</span>
            </span>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-xs sm:text-sm">
          <thead>
            <tr class="bg-gray-50 border-b">
              <th class="px-3 sm:px-4 py-2 text-left font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10 min-w-[140px] sm:min-w-[160px]">
                <div>Date (BS)</div>
                <div class="text-xs font-normal text-gray-500">Time</div>
              </th>
              @foreach($faculties as $f)
                <th class="px-2 sm:px-3 py-2 text-left font-semibold text-gray-700 whitespace-nowrap min-w-[100px] sm:min-w-[120px]">
                  {{ $f->code }}
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse($dates as $d)
              <tr class="border-b hover:bg-gray-50 transition">
                <td class="px-3 sm:px-4 py-3 font-medium sticky left-0 bg-white z-10 border-r">
                  <div class="text-gray-900">{{ $d }}</div>
                  <div class="text-[10px] sm:text-xs text-gray-500 mt-0.5">{{ $metaByDate[$d] ?? '' }}</div>
                </td>
                @foreach($faculties as $f)
                  <td class="px-2 sm:px-3 py-3">
                    @php
                      $code = $matrix[$d][$f->id] ?? null;
                    @endphp
                    @if($code)
                      <div class="inline-block rounded-lg px-2 py-1.5 {{ $subjectColors[$code] ?? 'bg-gray-100 text-gray-800' }} text-xs sm:text-sm font-semibold">
                        {{ $code }}
                      </div>
                      <div class="text-[10px] text-gray-600 mt-1 leading-tight">{{ $subjectNames[$code] ?? '' }}</div>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @empty
              <tr>
                <td class="px-4 py-6 text-center text-gray-500 text-sm" colspan="{{ 1 + $faculties->count() }}">
                  No routine slots yet. Start adding slots below.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif

  {{-- Builder: only remaining subjects appear --}}
  @if($exam && $selectedSemester)
  <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
    <h2 class="text-lg font-semibold mb-4 text-gray-900">Add New Slots</h2>
    <form method="POST" action="{{ route('routine.builder.store') }}">
      @csrf
      <input type="hidden" name="exam_id" value="{{ $exam->id }}">
      <input type="hidden" name="semester" value="{{ $selectedSemester }}">

      <div id="daysWrap" class="space-y-4 mb-4"></div>

      <div class="flex flex-col sm:flex-row gap-3">
        <button type="button" id="addDayBtn" class="border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition">
          + Add Slot (Suggest +3 BS days)
        </button>
        <button type="submit" class="bg-gray-900 text-white rounded-xl px-5 py-2.5 text-sm font-semibold hover:bg-gray-800 transition shadow-sm">
          Save Routine
        </button>
      </div>
    </form>
  </div>
  @endif
</div>

@if($exam && $selectedSemester)
<script>
/* ---------- BS DATE HELPERS (dd/mm/yyyy) ---------- */
const BS_MONTH_DAYS_DEFAULT = [31,31,32,31,31,30,30,30,29,29,30,30];
function parseBs(str){ const m=/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec((str||'').trim()); if(!m) return null; const d=+m[1],mo=+m[2],y=+m[3]; if(mo<1||mo>12||d<1||d>32) return null; return {d, m:mo, y}; }
function formatBs(o){ return `${String(o.d).padStart(2,'0')}/${String(o.m).padStart(2,'0')}/${o.y}`; }
function monthDaysBs(y,m){ return BS_MONTH_DAYS_DEFAULT[m-1] || 30; }
function addDaysBs(str,n){ let p=parseBs(str); if(!p) return ''; while(n-- > 0){ const md=monthDaysBs(p.y,p.m); if(p.d<md) p.d++; else { p.d=1; p.m===12 ? (p.m=1,p.y++) : p.m++; } } return formatBs(p); }

/* ---------- MASK dd/mm/yyyy ---------- */
(function(){
  function toMasked(value){ let d=(value||'').replace(/\D/g,'').slice(0,8); const day=d.slice(0,2), mo=d.slice(2,4), y=d.slice(4,8);
    if(d.length===0) return ''; if(d.length<2) return d; if(d.length===2) return day+'/';
    if(d.length<4) return day+'/'+mo; if(d.length===4) return day+'/'+mo+'/'; return day+'/'+mo+'/'+y;
  }
  function attachMask(el){ el.addEventListener('keypress',e=>{ if(!/[0-9]/.test(e.key)) e.preventDefault(); });
    el.addEventListener('input',()=>{ const masked=toMasked(el.value); if(el.value!==masked) el.value=masked; const len=el.value.length; el.setSelectionRange(len,len); });
    el.addEventListener('blur',()=>{ el.value=toMasked(el.value); });
  }
  document.addEventListener('DOMContentLoaded',()=>{ document.querySelectorAll('.date-mask').forEach(attachMask); });
  window.__attachDateMask=attachMask;
})();

/* ---------- PAGE LOGIC with "hide chosen in later slots" ---------- */
const FACULTIES = @json($faculties);
const PRESETS   = @json($presets);
const wrap  = document.getElementById('daysWrap');
const addBtn = document.getElementById('addDayBtn');

function getChosenBefore(slotIndex){
  const map = new Map();
  const slots = Array.from(wrap.querySelectorAll('.slot'));
  for(let i=0;i<slotIndex;i++){
    const radios = slots[i].querySelectorAll('input[type="radio"]:checked');
    radios.forEach(r=>{
      const fid = r.name.match(/\[subjects]\[(\d+)]$/)?.[1];
      if(!fid) return;
      if(!map.has(fid)) map.set(fid, new Set());
      map.get(fid).add(r.value);
    });
  }
  return map;
}

function refreshFollowingSlots(fromIndex){
  const slots = Array.from(wrap.querySelectorAll('.slot'));
  for(let i=fromIndex+1;i<slots.length;i++){
    const chosen = getChosenBefore(i);
    const groups = slots[i].querySelectorAll('[data-faculty]');
    groups.forEach(g=>{
      const fid = g.getAttribute('data-faculty');
      const taken = chosen.get(fid) || new Set();
      const radios = g.querySelectorAll('input[type="radio"]');
      radios.forEach(r=>{
        const hide = taken.has(r.value);
        const label = r.closest('label');
        if(hide){
          r.checked = false;
          r.disabled = true;
          if(label) label.style.display = 'none';
        }else{
          r.disabled = false;
          if(label) label.style.display = '';
        }
      });
      const anyVisible = Array.from(radios).some(r=>!r.disabled);
      const note = g.querySelector('[data-empty-note]');
      if(!anyVisible){
        if(!note){
          const div = document.createElement('div');
          div.setAttribute('data-empty-note','1');
          div.className = 'text-xs text-gray-400 mt-1 italic';
          div.textContent = 'No subjects available (already chosen in previous days)';
          g.appendChild(div);
        }
      }else if(note){
        note.remove();
      }
    });
  }
}

function hookSlotChange(slotEl, slotIndex){
  slotEl.addEventListener('change', (e)=>{
    if(e.target && e.target.matches('input[type="radio"]')){
      refreshFollowingSlots(slotIndex);
    }
  });
}

function buildSlot(index){
  const chosen = getChosenBefore(index);
  const suggested = (function(){
    if(index===0) return '';
    const last = wrap.querySelector('.slot:last-of-type input.bs-date')?.value || '';
    return last ? addDaysBs(last,3) : '';
  })();

  const div = document.createElement('div');
  div.className = "slot border border-gray-200 rounded-xl p-4 bg-gradient-to-br from-white to-gray-50 shadow-sm";

  let html = `
    <div class="mb-4">
      <label class="block text-sm font-semibold text-gray-700 mb-2">
        <span class="inline-block bg-gray-900 text-white px-2 py-0.5 rounded text-xs mr-2">Day ${index+1}</span>
        Date (BS — dd/mm/yyyy)
      </label>
      <input type="text" name="days[${index}][date]" class="bs-date date-mask w-full sm:w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
             placeholder="dd/mm/yyyy" value="${suggested}" required>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
  `;

  FACULTIES.forEach(f=>{
    const subs = PRESETS[f.id] || [];
    const taken = chosen.get(String(f.id)) || new Set();
    const available = subs.filter(s => !taken.has(s.code));
    if(available.length===0){
      html += `
        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50" data-faculty="${f.id}">
          <div class="text-xs font-semibold text-gray-700 mb-2 pb-2 border-b border-gray-200">${f.code}</div>
          <div class="text-xs text-gray-400 italic" data-empty-note="1">No subjects available</div>
        </div>`;
    }else{
      html += `
        <div class="border border-gray-200 rounded-lg p-3 bg-white hover:shadow-md transition" data-faculty="${f.id}">
          <div class="text-xs font-semibold text-gray-700 mb-2 pb-2 border-b border-gray-200">${f.code}</div>
          <div class="space-y-1.5">
          ${available.map(s=>`
            <label class="flex items-start gap-2 text-xs hover:bg-gray-50 p-1.5 rounded cursor-pointer transition">
              <input type="radio" name="days[${index}][subjects][${f.id}]" value="${s.code}" class="mt-0.5 text-blue-600 focus:ring-blue-500">
              <span class="flex-1">
                <span class="font-medium text-gray-900">${s.code}</span>
                ${s.name ? `<span class="text-gray-600 block text-[10px] leading-tight mt-0.5">${s.name}</span>` : ''}
              </span>
            </label>
          `).join('')}
          </div>
        </div>`;
    }
  });

  html += `</div>`;
  div.innerHTML = html;
  wrap.appendChild(div);

  const bsInput = div.querySelector('input.bs-date');
  if (bsInput && window.__attachDateMask) window.__attachDateMask(bsInput);
  hookSlotChange(div, index);
}

function addSlot(){
  const idx = wrap.querySelectorAll('.slot').length;
  buildSlot(idx);
  refreshFollowingSlots(idx-1);
}

addBtn?.addEventListener('click', addSlot);
</script>

@endif
@endsection
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Routine – CR / VCR Panel</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-100 text-slate-900">
  <div class="max-w-5xl mx-auto p-4 sm:p-6 text-sm">
    {{-- Flash --}}
    @if(session('ok'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('ok') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-semibold text-slate-900">
                Welcome, {{ $student->name }}
            </h1>
            <p class="text-[11px] text-slate-500 mt-1">
                Student Dashboard &middot;
                @if($student->faculty)
                    {{ $student->faculty->code }} &mdash; {{ $student->faculty->name }}
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[11px] font-semibold hover:bg-slate-800">
                Logout
            </button>
        </form>
    </div>

    {{-- Must change password notice --}}
    @if($student->must_change_password)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-900">
            <span class="font-semibold">Security notice:</span>
            You have been assigned a new password by campus. Please go to the password change page and set your own password.
            (We’ll link this to the actual change-password page later.)
        </div>
    @endif

    {{-- Student Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-700 mb-2">Profile</h2>
            <dl class="text-[11px] text-slate-700 space-y-1">
                <div class="flex justify-between">
                    <dt class="font-medium">Name</dt>
                    <dd>{{ $student->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Roll / Symbol No.</dt>
                    <dd>{{ $student->symbol_no }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Email</dt>
                    <dd>{{ $student->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Contact</dt>
                    <dd>{{ $student->contact }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <h2 class="text-xs font-semibold text-slate-700 mb-2">Academic Info</h2>
            <dl class="text-[11px] text-slate-700 space-y-1">
                <div class="flex justify-between">
                    <dt class="font-medium">Faculty</dt>
                    <dd>
                        @if($student->faculty)
                            {{ $student->faculty->code }} &mdash; {{ $student->faculty->name }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Section</dt>
                    <dd>{{ optional($student->section)->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Batch</dt>
                    <dd>{{ $student->batch }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Year / Semester</dt>
                    <dd>Year {{ $student->year }} &middot; Sem {{ $student->semester }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium">Role</dt>
                    <dd>
                        @if($student->isCr())
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-semibold">
                                CR
                            </span>
                        @elseif($student->isVcr())
                            <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 text-[10px] font-semibold">
                                VCR
                            </span>
                        @else
                            <span class="text-[10px] text-slate-500">Regular Student</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Quick actions / placeholders for next features --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <h3 class="text-xs font-semibold text-slate-700 mb-1">Today’s Routine</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                View your routine for today and upcoming days. We’ll add the full routine view and taught/not-taught
                toggle here in the next step.
            </p>
            {{-- later we can link to route like student.routine --}}
            <span class="inline-flex items-center text-[11px] text-slate-600">
                Coming soon…
            </span>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <h3 class="text-xs font-semibold text-slate-700 mb-1">Class Taught Status</h3>
            <p class="text-[11px] text-slate-500">
                As CR / VCR, you will be able to mark whether each class was taught or not, so campus chief can monitor.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <h3 class="text-xs font-semibold text-slate-700 mb-1">Announcements</h3>
            <p class="text-[11px] text-slate-500">
                Important notices from campus can be shown here later.
            </p>
        </div>
    </div>
</div>
</body>
</html>

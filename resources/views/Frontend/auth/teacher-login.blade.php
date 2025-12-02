<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-100 text-sm">
    <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
        <h1 class="text-lg font-semibold mb-4 text-center">Teacher Login</h1>

        @if(session('error'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                <ul class="list-disc list-inside text-xs">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('teacher.login') }}" class="space-y-3">
            @csrf

            <div>
                <label class="block text-[11px] text-slate-600 mb-1">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-[11px] text-slate-600 mb-1">Password</label>
                <input type="password"
                       name="password"
                       required
                       class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                Login
            </button>
        </form>
    </div>
</body>
</html>

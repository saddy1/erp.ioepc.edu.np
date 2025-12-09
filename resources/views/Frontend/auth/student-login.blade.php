<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Portal')</title>

    @vite('resources/css/app.css')
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

</head>

<body class="antialiased bg-gray-50 text-gray-900">

    <header class="fixed inset-x-0 top-0 h-16 bg-white shadow-lg z-50">
        <div class="h-full px-4 md:px-6 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('assets/ioepc_logo.png') }}" class="h-10 w-auto" alt="Logo">
                <span class="text-lg md:text-xl font-bold text-blue-900"> IOE Purwanchal Campus</span>
            </a>
        </div>
    </header>

    <div class="min-h-screen flex flex-col">

        <div class="min-h-screen flex items-center justify-center bg-gradient-to-r 
                from-slate-100 via-blue-100 to-slate-200 p-4">

            <div class="w-full max-w-md bg-white p-6 rounded-2xl shadow-lg border border-slate-200">

                <!-- ERROR / SUCCESS MESSAGES -->
                @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
                @endif

                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
                @endif

                @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Heading -->
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-blue-900">CR / VCR Login</h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Use your email and password to access the dashboard.
                    </p>
                </div>

                <!-- Login Form -->
                <form method="POST" action="{{ route('student.login') }}" class="space-y-4">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full rounded-lg border border-slate-300 focus:border-blue-500 
                                   focus:ring focus:ring-blue-200 p-2 text-sm"
                            placeholder="admin@example.com" />
                        @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-700">Password</label>

                        <div class="relative w-full">
                            <input type="password" id="passwordField" name="password" required
                                class="w-full rounded-lg border border-slate-300 focus:border-blue-500 
                                       focus:ring focus:ring-blue-200 p-2 pr-12 text-sm"
                                placeholder="••••••••" />

                            <!-- Eye Icon -->
                            <span id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer 
                                       text-slate-500 hover:text-slate-700">
                                <i id="togglePasswordIcon" class="fa-solid fa-eye"></i>
                            </span>
                        </div>

                        @error('password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="w-full bg-blue-700 text-white py-2 rounded-lg hover:bg-blue-800 
                               transition shadow-md">
                        Sign In
                    </button>

                </form>

            </div>
        </div>

        <footer
            class="fixed bottom-0 left-0 w-full bg-white/10 backdrop-blur-lg border-t 
                   border-white/20 py-4 text-center text-black">
            <p class="text-sm">© {{ date('Y') }} IOE Purwanchal Campus. All Rights Reserved.</p>
            <p class="text-sm font-medium">
                Designed by <a href="https://sadanandpaneru.com.np"
                    class="text-red-600 underline">Sadanand Paneru</a>
            </p>
        </footer>
    </div>

    <!-- Toggle Password Script -->
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const field = document.getElementById('passwordField');
            const icon = document.getElementById('togglePasswordIcon');

            const isPassword = field.type === 'password';
            field.type = isPassword ? 'text' : 'password';

            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    </script>

</body>

</html>

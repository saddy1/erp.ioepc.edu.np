<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Admin Dashboard ')</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="{{asset('bs-datepicker.js')}}" ></script>
    @vite('resources/css/app.css')

</head>

<body class="antialiased bg-gray-50 text-gray-900">

    <!-- HEADER (fixed to screen sides) -->
    <header class="fixed inset-x-0 top-0 h-16 bg-white shadow-lg z-50">
        <div class="h-full px-4 md:px-6 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('assets/ioepc_logo.png') }}" class="h-15 w-auto" alt="Logo">
                <span class="text-lg md:text-xl font-bold text-blue-900"> IOEPC</span>
            </a>

            <div class="flex items-center gap-2">
                @if (session('admin_id'))
                    <!-- Mobile sidebar toggle -->
                    <button id="sidebarToggle"
                        class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100"
                        title="Menu">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <!-- Notifications (optional) -->
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hidden sm:inline-flex">
                        <i class="fas fa-bell text-lg"></i>
                        <span
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center">3</span>
                    </button>

                    <!-- Profile -->
                    <div class="relative">
                        <button id="profileDropdownBtn"
                            class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-gray-100">
                            <div
                                class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">
                                {{ strtoupper(mb_substr($admin->name, 0, 1)) }}
                            </div>
                            <span class="hidden md:inline-block font-medium">{{ $admin->name }}</span>
                            <i class="fa-solid fa-chevron-down text-sm text-gray-500"></i>
                        </button>

                        <div id="profileDropdownMenu"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-100 z-50">
                            <div class="px-4 py-2 text-sm text-gray-600 border-b">
                                Admin
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('student.login.form') }}"
                        class="px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition">
                        Login
                    </a>
                @endif
            </div>
        </div>
    </header>

    @if (session('admin_id'))
        <!-- SIDEBAR BACKDROP (mobile) -->
        <div id="sidebarBackdrop" class="hidden fixed inset-0 bg-black/30 z-40 lg:hidden"></div>

        <!-- SIDEBAR (fixed, aligned to screen sides; never under header) -->
        <aside id="sidebar"
            class="fixed left-0 top-16 w-64 h-[calc(100vh-4rem)] bg-gray-900 text-white z-50
                  transform -translate-x-full transition-transform duration-300
                  overflow-y-auto lg:translate-x-0">
            <nav class="py-4">
                <ul class="space-y-1 px-3 text-sm">
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800">
                            <i class="fas fa-tachometer-alt w-5 text-gray-300"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                  
                    <li>
                        <a href="{{ route('rooms.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800">
                            <i class="fas fa-door-open w-5 text-gray-300"></i><span>Rooms</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faculties.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800">
                            <i class="fas fa-university w-5 text-gray-300"></i><span>Faculties</span>
                        </a>
                    </li>
                    
                    
                         <li>
                        <a href="{{ route('faculty_subjects.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800">
                            <i class="fas fa-book w-5 text-gray-300"></i><span>Faculty Subjects</span>
                        </a>
                    </li>
                        <li>
                            <a href="{{ route('sections.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800">
                                <i class="fas fa-users w-5 text-gray-300"></i><span>Sections</span>
                            </a>

                        </li>
                    <li>
                        <a href="{{ route('students.import.form') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800">
                            <i class="fas fa-user-graduate w-5 text-gray-300"></i><span>Students</span>
                        </a>
                    </li>
                    <li>
                        
                        
               
                   

                  


                </ul>
            </nav>
        </aside>
    @endif

    <!-- MAIN (never sits under header; only shifts when sidebar is present) -->
    <main class="pt-16 lg:ml-64 min-h-screen px-4 md:px-6">
        {{-- @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">
                {{ session('error') }}
            </div>
        @endif --}}

        @yield('content')
    </main>

    <script>
        // Profile dropdown
        const profileBtn = document.getElementById('profileDropdownBtn');
        const profileMenu = document.getElementById('profileDropdownMenu');
        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', () => profileMenu.classList.add('hidden'));
        }

        // Sidebar toggle (mobile)
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
        }

        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
        }

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', openSidebar);
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }
        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });

    </script>
    <script>
  (function () {
    function toMasked(value) {
      let d = (value || '').replace(/\D/g, '').slice(0, 8); // YYYYMMDD
      const y = d.slice(0,4), m = d.slice(4,6), day = d.slice(6,8);

      if (d.length === 0) return '';
      if (d.length < 4)   return d;
      if (d.length === 4) return y + '-';
      if (d.length < 6)   return y + '-' + m;
      if (d.length === 6) return y + '-' + m + '-';
      return y + '-' + m + '-' + day;
    }

    function attachMask(el) {
      el.addEventListener('keypress', e => { if (!/[0-9]/.test(e.key)) e.preventDefault(); });
      el.addEventListener('input', () => {
        const masked = toMasked(el.value);
        if (el.value !== masked) el.value = masked;
        const len = el.value.length;
        el.setSelectionRange(len, len);
      });
      el.addEventListener('blur', () => { el.value = toMasked(el.value); });
    }

    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.date-mask').forEach(attachMask);
    });
  })();
</script>
</body>

</html>

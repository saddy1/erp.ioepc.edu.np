<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>IOE Purwanchal Campus</title>
    @vite('resources/css/app.css')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>

    <style>
        .bg-cover-custom {
            background-image: url('{{ asset("assets/ERC_Background.jpg") }}'); /* change image */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-900">

<!-- FULL SCREEN HERO -->
<section class="relative w-full h-screen bg-cover-custom flex items-center justify-center">

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50"></div>

    <!-- Content -->
    <div class="relative z-10 text-center px-5">

        <!-- Title -->
        <h1 class="text-white font-bold text-3xl sm:text-4xl lg:text-5xl drop-shadow-lg">
            IOE Purwanchal Campus
        </h1>
        <p class="text-gray-200 mt-2 text-sm sm:text-md lg:text-lg">
            Welcome to the Official Portal
        </p>

        <!-- Avatar Row -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-6">

            <!-- Student Login -->
            <a href="{{ route('student.login.form') }}"
               class="group w-60 sm:w-64 bg-white/20 backdrop-blur-lg hover:bg-white/30
                      text-white py-5 px-4 rounded-2xl shadow-xl flex items-center gap-4
                      border border-white/30 transition duration-300">

                <div class="w-14 h-14 rounded-full bg-blue-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>

                <div class="text-left">
                    <h3 class="text-lg font-semibold">Student Login</h3>
                    <p class="text-[12px] opacity-80">Access your dashboard</p>
                </div>
            </a>

            <!-- Teacher Login -->
            <a href="{{ route('teacher.login.form') }}"
               class="group w-60 sm:w-64 bg-white/20 backdrop-blur-lg hover:bg-white/30
                      text-white py-5 px-4 rounded-2xl shadow-xl flex items-center gap-4
                      border border-white/30 transition duration-300">

                <div class="w-14 h-14 rounded-full bg-green-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>

                <div class="text-left">
                    <h3 class="text-lg font-semibold">Teacher Login</h3>
                    <p class="text-[12px] opacity-80">View classes & attendance</p>
                </div>
            </a>

        </div>
    </div>
</section>
 <footer
        class="fixed bottom-0 left-0 w-full bg-white/10 backdrop-blur-lg border-t border-white/20 py-4 text-center text-white">
          <p class="text-sm">© {{ date('Y') }} IOE Purwanchal Campus. All Rights Reserved.</p>
        <p class="text-sm font-medium">
            Designed by <a href="https://sadanandpaneru.com.np" class="text-red-600 underline">Sadanand Paneru</a>
        </p>
    </footer>
</body>
</html>

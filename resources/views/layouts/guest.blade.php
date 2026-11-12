<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0D9488">

    <title>{{ config('app.name', 'STOKKU ERP') }} — Masuk Warung Adhi Jaya</title>

    <!-- Instant Dark/Light Theme Initialization -->
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('stokku_theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}

            window.toggleStokkuTheme = function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try {
                    localStorage.setItem('stokku_theme', isDark ? 'dark' : 'light');
                } catch (e) {}
                return isDark;
            };
        })();
    </script>

    <!-- Fonts: Inter & Material Symbols Outlined -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    <!-- Vite Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- PWA Configuration -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0f766e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="STOKKU ERP">
    <link rel="apple-touch-icon" href="{{ asset('logo-192.png') }}">
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
          navigator.serviceWorker.register("{{ asset('sw.js') }}");
        });
      }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100 antialiased selection:bg-[#0D9488] selection:text-white min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 transition-colors duration-200">
    {{ $slot }}
</body>
</html>

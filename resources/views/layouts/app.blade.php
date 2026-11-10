<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" id="theme-color-meta" content="#F8FAFC">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="STOKKU ERP">

    <title>{{ config('app.name', 'STOKKU ERP') }} — Warung Sembako &amp; Sayur</title>

    <!-- Instant Dark/Light Theme & Dynamic Status Bar (theme-color) Initialization -->
    <script>
        (function () {
            var isDashboardPage = {{ request()->routeIs('dashboard') ? 'true' : 'false' }}
                || window.location.pathname === '/dashboard'
                || window.location.pathname === '/';

            try {
                var savedTheme = localStorage.getItem('stokku_theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}

            window.updateStokkuThemeColor = function () {
                var meta = document.getElementById('theme-color-meta');
                if (!meta) return;

                var isDark = document.documentElement.classList.contains('dark');
                var scrollTop = window.scrollY || window.pageYOffset || 0;

                if (isDashboardPage && scrollTop < 100) {
                    meta.setAttribute('content', '#0D9488');
                } else {
                    meta.setAttribute('content', isDark ? '#020617' : '#F8FAFC');
                }
            };

            window.toggleStokkuTheme = function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try {
                    localStorage.setItem('stokku_theme', isDark ? 'dark' : 'light');
                } catch (e) {}
                window.updateStokkuThemeColor();
                window.dispatchEvent(new CustomEvent('stokku-theme-changed', { detail: { dark: isDark } }));
                return isDark;
            };

            window.updateStokkuThemeColor();
            window.addEventListener('scroll', window.updateStokkuThemeColor, { passive: true });
            window.addEventListener('DOMContentLoaded', window.updateStokkuThemeColor);
        })();
    </script>

    <!-- PWA Manifest & Icon -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/stokku-icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/stokku-icon.svg') }}">

    <!-- Fonts: Inter & Material Symbols Outlined -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100 antialiased selection:bg-[#0D9488] selection:text-white transition-colors duration-200">

    <!-- Dual-Mode Navigation (Desktop Sidebar + Header | Mobile Floating Pill Bar + AI Falah Button) -->
    @include('layouts.navigation')

    <!-- Main Content Shell -->
    <div class="md:pl-64 lg:pl-72 min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 transition-colors duration-200">
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-5 md:px-6 lg:px-8 pt-3 md:pt-20 pb-28 md:pb-12">
            @isset($header)
                <div class="mb-4">
                    {{ $header }}
                </div>
            @endisset

            {{ $slot }}
        </main>
    </div>

    <!-- Asisten AI "Falah" Modal Drawer -->
    @include('partials.chatbot')

    <!-- Register PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }
    </script>
</body>
</html>

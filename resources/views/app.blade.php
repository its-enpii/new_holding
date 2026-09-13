<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4338ca">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Holding">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <title inertia>{{ config('app.name', 'Holding') }}</title>
    <script>
        (function () {
            try {
                var mode = localStorage.getItem('holding-theme') || 'system';
                var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (error) {}
        })();
    </script>
    @routes
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface antialiased transition-colors duration-200">
    @inertia
</body>
</html>

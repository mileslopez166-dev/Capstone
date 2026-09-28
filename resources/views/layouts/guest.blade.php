<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            (() => {
                let preferences = {};
                try { preferences = JSON.parse(localStorage.getItem('pgaals-comfort')) || {}; } catch {}
                const theme = ['light', 'dark', 'system'].includes(preferences.theme) ? preferences.theme : 'light';
                const dark = theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>

        <title>{{ config('app.name', 'AI-PGAALS') }}</title>

        <!-- Fonts -->
        <x-local-fonts />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-background">
        {{ $slot }}
    </body>
</html>

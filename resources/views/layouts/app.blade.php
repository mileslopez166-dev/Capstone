<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            (() => {
                let motion;
                try { motion = JSON.parse(localStorage.getItem('pgaals-comfort'))?.motion; } catch {}
                document.documentElement.dataset.reducedMotion = String(motion === 'reduce' || matchMedia('(prefers-reduced-motion: reduce)').matches);
            })();
        </script>

        <title>{{ config('app.name', 'AI-PGAALS') }}</title>

        <!-- Fonts -->
        <x-local-fonts />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $campusRole = auth()->user()?->role;
        $hasCampusTheme = $campusRole === 'student';
        $workspaceTheme = in_array($campusRole, ['teacher', 'admin'], true) ? 'staff-theme '.$campusRole.'-theme' : '';
    @endphp
    <body class="min-h-screen bg-surface text-on-surface font-body selection:bg-primary-container selection:text-on-primary-container {{ $hasCampusTheme ? 'campus-theme campus-'.$campusRole : $workspaceTheme }}">
        {{ $slot }}
    </body>
</html>

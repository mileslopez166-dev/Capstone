<meta name="csrf-token" content="{{ csrf_token() }}">
<x-favicon />
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
<x-local-fonts />
@vite(['resources/css/app.css', 'resources/js/app.js'])

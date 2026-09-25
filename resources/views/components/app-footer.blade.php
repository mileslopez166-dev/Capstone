<footer class="app-footer" aria-label="Site footer">
    <div class="app-footer-brand">
        <x-application-logo />
        <div><strong>AI-PGAALS</strong><p>Literacy &amp; Numeracy</p></div>
    </div>
    <p class="app-footer-copyright">&copy; {{ now()->year }} AI-PGAALS</p>
    <nav aria-label="Footer navigation">
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}">Dashboard</a>
        <a href="{{ route('support.developing') }}">Support</a>
    </nav>
</footer>

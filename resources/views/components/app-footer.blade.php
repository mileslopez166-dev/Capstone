<footer class="app-footer" aria-label="Site footer">
    <div class="app-footer-brand">
        <x-application-logo />
        <p class="app-footer-copyright">&copy; {{ now()->year }} AI-PGAALS</p>
    </div>
    <nav aria-label="Footer navigation">
        <a href="{{ route('support.developing') }}">Support</a>
    </nav>
</footer>

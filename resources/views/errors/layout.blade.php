@php
    $code = (string) $code;
    $visual = $visual ?? 'system';
    $tone = $tone ?? 'blue';
    $icon = $icon ?? 'school';
    $eyebrow = $eyebrow ?? 'AI-PGAALS system notice';
    $status = $status ?? null;
    $primary = $primary ?? ['label' => 'Return Home', 'href' => url('/'), 'icon' => 'home'];
    $secondary = $secondary ?? null;
@endphp
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
            const motion = preferences.motion;
            const theme = ['light', 'dark', 'system'].includes(preferences.theme) ? preferences.theme : 'light';
            const dark = theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.reducedMotion = String(motion === 'reduce' || matchMedia('(prefers-reduced-motion: reduce)').matches);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    <title>{{ $code }} | {{ $title }} | {{ config('app.name', 'AI-PGAALS') }}</title>
    <x-local-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --error-bg: #eef6fb;
            --error-panel: rgba(255, 255, 255, .88);
            --error-panel-solid: #ffffff;
            --error-ink: #17314a;
            --error-muted: #5b6b7a;
            --error-line: #bdd5e5;
            --error-primary: #0864a3;
            --error-primary-strong: #06588c;
            --error-green: #08765c;
            --error-yellow: #d69a14;
            --error-red: #b31b25;
            --error-shadow: 0 24px 70px rgba(20, 93, 124, .18);
        }

        html.dark {
            --error-bg: #07131f;
            --error-panel: rgba(13, 31, 49, .9);
            --error-panel-solid: #0d1f31;
            --error-ink: #e8f2ff;
            --error-muted: #a7b8ca;
            --error-line: #29435f;
            --error-primary: #6ab8ff;
            --error-primary-strong: #8bc8ff;
            --error-green: #78e1b9;
            --error-yellow: #ffd36b;
            --error-red: #ff9aa2;
            --error-shadow: 0 24px 70px rgba(0, 0, 0, .34);
        }

        .error-page {
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            overflow-x: hidden;
            background:
                linear-gradient(90deg, rgba(8, 100, 163, .08) 1px, transparent 1px),
                linear-gradient(rgba(8, 118, 92, .07) 1px, transparent 1px),
                radial-gradient(circle at 50% 100%, rgba(255, 235, 59, .22), transparent 34rem),
                linear-gradient(135deg, #f8fcff 0%, var(--error-bg) 56%, #e5f1e9 100%);
            background-size: 46px 46px, 46px 46px, auto, auto;
            color: var(--error-ink);
            font-family: 'Be Vietnam Pro', ui-sans-serif, system-ui, sans-serif;
        }

        html.dark .error-page {
            background:
                linear-gradient(90deg, rgba(106, 184, 255, .08) 1px, transparent 1px),
                linear-gradient(rgba(120, 225, 185, .07) 1px, transparent 1px),
                linear-gradient(135deg, #07131f 0%, #0b1d2f 56%, #102719 100%);
            background-size: 46px 46px, 46px 46px, auto;
        }

        .error-shell {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            flex-direction: column;
        }

        .error-header,
        .error-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px clamp(18px, 4vw, 44px);
            border-color: color-mix(in srgb, var(--error-line) 82%, transparent);
            background: color-mix(in srgb, var(--error-panel-solid) 86%, transparent);
            backdrop-filter: blur(14px);
        }

        .error-header { border-bottom: 1px solid var(--error-line); }
        .error-footer { border-top: 1px solid var(--error-line); color: var(--error-muted); font-size: 12px; }

        .error-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--error-primary);
            text-decoration: none;
        }

        .error-brand svg {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
        }

        .error-brand strong {
            display: block;
            font: 800 21px/1.2 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }

        .error-brand small {
            display: block;
            margin-top: 3px;
            color: var(--error-muted);
            font-size: 11px;
            font-weight: 600;
        }

        .error-header-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--error-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .error-header-status::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 2px;
            background: var(--error-green);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--error-green) 16%, transparent);
        }

        .error-main {
            display: grid;
            flex: 1;
            place-items: center;
            padding: clamp(24px, 5vw, 64px) clamp(16px, 4vw, 44px);
        }

        .error-card {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.02fr) minmax(300px, .98fr);
            gap: clamp(24px, 5vw, 54px);
            width: min(1120px, 100%);
            overflow: hidden;
            border: 1px solid var(--error-line);
            border-top: 4px solid var(--tone, var(--error-primary));
            border-radius: 8px;
            background: var(--error-panel);
            box-shadow: var(--error-shadow);
            backdrop-filter: blur(20px);
        }

        .error-card::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(120deg, color-mix(in srgb, var(--tone, var(--error-primary)) 13%, transparent), transparent 36%),
                linear-gradient(90deg, transparent, rgba(255, 255, 255, .22), transparent);
            opacity: .9;
        }

        .error-copy,
        .error-visual {
            position: relative;
            z-index: 1;
        }

        .error-copy {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 520px;
            padding: clamp(28px, 5vw, 56px);
        }

        .error-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            margin-bottom: 18px;
            padding: 8px 10px;
            border: 1px solid color-mix(in srgb, var(--tone, var(--error-primary)) 38%, var(--error-line));
            border-radius: 6px;
            background: color-mix(in srgb, var(--tone, var(--error-primary)) 10%, transparent);
            color: var(--tone, var(--error-primary));
            font-size: 12px;
            font-weight: 800;
        }

        .error-code {
            font: 800 clamp(76px, 14vw, 148px)/.9 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--error-ink);
            letter-spacing: 0;
            text-shadow: 0 12px 30px color-mix(in srgb, var(--tone, var(--error-primary)) 18%, transparent);
            animation: error-float 7s ease-in-out infinite;
        }

        .error-title {
            margin: 22px 0 12px;
            font: 800 clamp(28px, 5vw, 44px)/1.16 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            letter-spacing: 0;
        }

        .error-message {
            max-width: 560px;
            color: var(--error-muted);
            font-size: clamp(15px, 2vw, 17px);
            line-height: 1.75;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .error-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 46px;
            padding: 12px 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }

        .error-action .material-symbols-outlined { font-size: 18px; }

        .error-action-primary {
            background: var(--tone, var(--error-primary));
            color: #ffffff;
        }

        html.dark .error-action-primary { color: #06131f; }

        .error-action-secondary {
            background: color-mix(in srgb, var(--error-panel-solid) 76%, transparent);
            border-color: var(--error-line);
            color: var(--error-ink);
        }

        .error-action:hover {
            transform: translateY(-2px);
        }

        .error-action:focus-visible,
        .error-brand:focus-visible {
            outline: 3px solid var(--tone, var(--error-primary));
            outline-offset: 4px;
        }

        .error-visual {
            display: grid;
            min-height: 520px;
            place-items: center;
            padding: clamp(28px, 5vw, 48px);
            isolation: isolate;
        }

        .error-visual::before {
            content: "";
            position: absolute;
            inset: 36px;
            z-index: -1;
            border: 1px solid color-mix(in srgb, var(--tone, var(--error-primary)) 30%, transparent);
            border-radius: 8px;
            background:
                linear-gradient(90deg, color-mix(in srgb, var(--tone, var(--error-primary)) 12%, transparent) 1px, transparent 1px),
                linear-gradient(color-mix(in srgb, var(--tone, var(--error-primary)) 10%, transparent) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: .9;
            animation: error-grid 18s linear infinite;
        }

        .error-code-ghost {
            position: absolute;
            right: clamp(18px, 4vw, 56px);
            top: clamp(18px, 4vw, 52px);
            color: color-mix(in srgb, var(--tone, var(--error-primary)) 14%, transparent);
            font: 800 clamp(90px, 18vw, 170px)/.8 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            pointer-events: none;
        }

        .error-diagram {
            position: relative;
            width: min(320px, 82vw);
            aspect-ratio: 1;
        }

        .error-core {
            position: absolute;
            inset: 24%;
            display: grid;
            place-items: center;
            border: 1px solid color-mix(in srgb, var(--tone, var(--error-primary)) 56%, var(--error-line));
            border-radius: 8px;
            background: color-mix(in srgb, var(--error-panel-solid) 76%, transparent);
            box-shadow: 0 18px 42px color-mix(in srgb, var(--tone, var(--error-primary)) 18%, transparent);
            animation: error-core-pulse 4s ease-in-out infinite;
        }

        .error-core .material-symbols-outlined {
            color: var(--tone, var(--error-primary));
            font-size: clamp(46px, 8vw, 66px);
            font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 48;
        }

        .error-ring {
            position: absolute;
            inset: 13%;
            border: 2px solid color-mix(in srgb, var(--tone, var(--error-primary)) 36%, transparent);
            border-right-color: transparent;
            border-radius: 8px;
            animation: error-turn 16s linear infinite;
        }

        .error-ring:nth-child(2) {
            inset: 5%;
            border-color: color-mix(in srgb, var(--error-green) 24%, transparent);
            border-left-color: transparent;
            animation-duration: 22s;
            animation-direction: reverse;
        }

        .error-node {
            position: absolute;
            width: 14px;
            height: 14px;
            border: 2px solid color-mix(in srgb, var(--tone, var(--error-primary)) 68%, transparent);
            border-radius: 4px;
            background: var(--error-panel-solid);
            animation: error-node 4.5s ease-in-out infinite;
        }

        .error-node:nth-of-type(1) { left: 16%; top: 20%; }
        .error-node:nth-of-type(2) { right: 10%; top: 34%; animation-delay: .6s; }
        .error-node:nth-of-type(3) { left: 27%; bottom: 9%; animation-delay: 1.1s; }
        .error-node:nth-of-type(4) { right: 24%; bottom: 20%; animation-delay: 1.5s; }

        .error-scan {
            position: absolute;
            left: 12%;
            right: 12%;
            top: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--tone, var(--error-primary)), transparent);
            box-shadow: 0 0 16px color-mix(in srgb, var(--tone, var(--error-primary)) 46%, transparent);
            animation: error-scan 3.8s ease-in-out infinite;
        }

        .error-bars {
            position: absolute;
            left: 10%;
            right: 10%;
            bottom: 10%;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 9px;
            align-items: end;
            height: 52px;
        }

        .error-bars span {
            display: block;
            min-height: 16px;
            border-radius: 4px;
            background: color-mix(in srgb, var(--tone, var(--error-primary)) 54%, var(--error-panel-solid));
            animation: error-bars 1.8s ease-in-out infinite;
        }

        .error-bars span:nth-child(2) { height: 38px; animation-delay: .15s; }
        .error-bars span:nth-child(3) { height: 24px; animation-delay: .3s; }
        .error-bars span:nth-child(4) { height: 48px; animation-delay: .45s; }
        .error-bars span:nth-child(5) { height: 30px; animation-delay: .6s; }

        .error-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            margin-top: 22px;
            color: var(--error-muted);
            font-size: 12px;
            font-weight: 800;
        }

        .error-status span {
            width: 7px;
            height: 7px;
            border-radius: 2px;
            background: var(--tone, var(--error-primary));
            animation: error-node 1.8s ease-in-out infinite;
        }

        .error-status span:nth-child(2) { animation-delay: .2s; }
        .error-status span:nth-child(3) { animation-delay: .4s; }

        .error-page[data-tone="green"] { --tone: var(--error-green); }
        .error-page[data-tone="yellow"] { --tone: var(--error-yellow); }
        .error-page[data-tone="red"] { --tone: var(--error-red); }
        .error-page[data-tone="blue"] { --tone: var(--error-primary); }

        .error-visual--shield .error-core { animation-name: error-shield; }
        .error-visual--timer .error-ring { animation-duration: 6s; }
        .error-visual--rate .error-bars span { animation-duration: 1.15s; }
        .error-visual--maintenance .error-ring { animation-duration: 5.5s; }

        @keyframes error-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes error-grid {
            from { background-position: 0 0, 0 0; }
            to { background-position: 56px 56px, 56px 56px; }
        }

        @keyframes error-core-pulse {
            0%, 100% { transform: translateY(0); box-shadow: 0 18px 42px color-mix(in srgb, var(--tone, var(--error-primary)) 18%, transparent); }
            50% { transform: translateY(-4px); box-shadow: 0 22px 52px color-mix(in srgb, var(--tone, var(--error-primary)) 28%, transparent); }
        }

        @keyframes error-shield {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.025); }
        }

        @keyframes error-turn {
            to { transform: rotate(360deg); }
        }

        @keyframes error-node {
            0%, 100% { opacity: .65; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-5px); }
        }

        @keyframes error-scan {
            0%, 100% { transform: translateY(0); opacity: .2; }
            50% { transform: translateY(180px); opacity: 1; }
        }

        @keyframes error-bars {
            0%, 100% { transform: scaleY(.62); opacity: .62; }
            50% { transform: scaleY(1); opacity: 1; }
        }

        @media (max-width: 880px) {
            .error-card { grid-template-columns: minmax(0, 1fr); }
            .error-copy { min-height: auto; padding-bottom: 16px; }
            .error-visual { min-height: 360px; padding-top: 6px; }
            .error-visual::before { inset: 10px 24px 30px; }
            .error-header-status { display: none; }
        }

        @media (max-width: 520px) {
            .error-header { padding: 16px 18px; }
            .error-brand small { display: none; }
            .error-card { gap: 4px; }
            .error-copy { padding: 26px 22px 10px; }
            .error-actions { flex-direction: column; }
            .error-action { width: 100%; }
            .error-visual { min-height: 300px; padding: 10px 16px 26px; }
            .error-footer { justify-content: center; text-align: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            .error-page *,
            .error-page *::before,
            .error-page *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition: none !important;
            }
        }

        html[data-reduced-motion="true"] .error-page *,
        html[data-reduced-motion="true"] .error-page *::before,
        html[data-reduced-motion="true"] .error-page *::after {
            animation: none !important;
            transition: none !important;
        }
    </style>
</head>
<body class="error-page" data-tone="{{ $tone }}">
    <div class="error-shell">
        <header class="error-header">
            <a class="error-brand" href="{{ url('/') }}" aria-label="AI-PGAALS home">
                <x-application-logo />
                <span><strong>AI-PGAALS</strong><small>Literacy &amp; Numeracy</small></span>
            </a>
            <span class="error-header-status">Learning platform</span>
        </header>

        <main class="error-main" id="main-content">
            <section class="error-card" aria-labelledby="error-title">
                <div class="error-copy">
                    <div class="error-eyebrow">
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
                        <span>{{ $eyebrow }}</span>
                    </div>
                    <div class="error-code" aria-hidden="true">{{ $code }}</div>
                    <h1 class="error-title" id="error-title">{{ $title }}</h1>
                    <p class="error-message">{{ $message }}</p>
                    @if ($status)
                        <div class="error-status" aria-label="{{ $status }}">
                            <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
                            {{ $status }}
                        </div>
                    @endif
                    <div class="error-actions" aria-label="Error page actions">
                        @include('errors.partials.action', ['action' => $primary, 'variant' => 'primary'])
                        @if ($secondary)
                            @include('errors.partials.action', ['action' => $secondary, 'variant' => 'secondary'])
                        @endif
                    </div>
                </div>

                <div class="error-visual error-visual--{{ $visual }}" aria-hidden="true">
                    <div class="error-code-ghost">{{ $code }}</div>
                    <div class="error-diagram">
                        <span class="error-ring"></span>
                        <span class="error-ring"></span>
                        <span class="error-node"></span>
                        <span class="error-node"></span>
                        <span class="error-node"></span>
                        <span class="error-node"></span>
                        <span class="error-scan"></span>
                        <div class="error-core"><span class="material-symbols-outlined">{{ $icon }}</span></div>
                        <div class="error-bars"><span></span><span></span><span></span><span></span><span></span></div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="error-footer">
            <span>AI-PGAALS</span>
            <span>smartlearn6.site</span>
        </footer>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-error-action]');
            if (!trigger) return;

            const action = trigger.dataset.errorAction;
            if (action === 'back') {
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = trigger.dataset.fallback || '/';
                }
            }

            if (action === 'reload') {
                window.location.reload();
            }
        });
    </script>
</body>
</html>

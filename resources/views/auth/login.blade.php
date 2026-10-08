<x-guest-layout>
    <div class="login-page">
        <header class="login-header">
            <a class="login-brand" href="{{ route('login') }}"><x-application-logo /><span>AI-PGAALS</span></a>
            <span>Grade 6 Learning Community</span>
        </header>

        <main class="login-main">
            <section class="login-form-panel" aria-labelledby="login-title" x-data="{ showPassword: false, submitting: false, showInstallGuide: false }" @keydown.escape.window="if (showInstallGuide) { showInstallGuide = false; $nextTick(() => $refs.installGuideButton.focus()) }" x-effect="document.body.classList.toggle('overflow-hidden', showInstallGuide)">
                <div class="login-heading">
                    <span><span class="material-symbols-outlined" aria-hidden="true">school</span>Your learning journey continues</span>
                    <h1 id="login-title">Welcome back</h1>
                    <p>Sign in to your AI-PGAALS account.</p>
                </div>

                <x-auth-session-status class="mb-5 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="login-form" @submit="submitting = true">
                    @csrf
                    <div class="login-field">
                        <label for="email">Email address</label>
                        <div class="login-field-wrap">
                            <input class="auth-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@example.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @if ($errors->has('email')) aria-describedby="email-error" @endif>
                            <span class="material-symbols-outlined" aria-hidden="true">mail</span>
                        </div>
                        <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-2 text-sm text-error" />
                    </div>

                    <div class="login-field">
                        <label for="password">Password</label>
                        <div class="login-field-wrap">
                            <input class="auth-input" id="password" name="password" type="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" placeholder="Enter your password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if ($errors->has('password')) aria-describedby="password-error" @endif>
                            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
                            <button class="login-password-toggle" type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" :title="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword">
                                <span class="material-symbols-outlined" x-text="showPassword ? 'visibility_off' : 'visibility'" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                        <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2 text-sm text-error" />
                    </div>

                    <div class="login-options">
                        <label for="remember"><input id="remember" name="remember" type="checkbox" @checked(old('remember'))>Remember me</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>
                    <button class="login-submit" type="submit" :disabled="submitting">
                        <span x-text="submitting ? 'Signing in...' : 'Sign In'">Sign In</span>
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </button>
                </form>
                <p class="login-register">New here? <a href="{{ route('register') }}">Create an account</a></p>
                <button class="login-app-download" type="button" x-ref="installGuideButton" @click="showInstallGuide = true; $nextTick(() => $refs.installGuideClose.focus())" aria-haspopup="dialog" aria-controls="android-install-guide" :aria-expanded="showInstallGuide">
                    <span class="material-symbols-outlined" aria-hidden="true">android</span>
                    <span class="login-app-download-copy">
                        <strong>Download the Android app</strong>
                        <small>Read the safe installation guide first</small>
                    </span>
                    <span class="material-symbols-outlined login-app-download-action" aria-hidden="true">arrow_forward</span>
                </button>

                <div id="android-install-guide" class="login-install-overlay" x-show="showInstallGuide" x-cloak x-transition.opacity @click.self="showInstallGuide = false; $nextTick(() => $refs.installGuideButton.focus())">
                    <section class="login-install-dialog" role="dialog" aria-modal="true" aria-labelledby="install-guide-title">
                        <header class="login-install-heading">
                            <span class="login-install-icon material-symbols-outlined" aria-hidden="true">verified_user</span>
                            <div>
                                <p>Android installation guide</p>
                                <h2 id="install-guide-title">Install AI-PGAALS safely</h2>
                            </div>
                            <button class="login-install-close" type="button" x-ref="installGuideClose" @click="showInstallGuide = false; $nextTick(() => $refs.installGuideButton.focus())" aria-label="Close installation guide" title="Close">
                                <span class="material-symbols-outlined" aria-hidden="true">close</span>
                            </button>
                        </header>

                        <div class="login-install-body">
                            <p class="login-install-intro">Android may show a warning because this app is downloaded from the AI-PGAALS website instead of Google Play.</p>

                            <div class="login-install-warning" role="note">
                                <span class="material-symbols-outlined" aria-hidden="true">security</span>
                                <p><strong>Keep Google Play Protect turned on.</strong> Do not pause app scanning or disable Play Protect to install this app.</p>
                            </div>

                            <ol class="login-install-steps">
                                <li><span>1</span><p>Tap <strong>Download APK</strong> below, then open the downloaded file.</p></li>
                                <li><span>2</span><p>If Android asks, open <strong>Settings</strong> and temporarily allow <strong>Install unknown apps</strong> for your browser or file manager.</p></li>
                                <li><span>3</span><p>Return to the installer and confirm <strong>Install</strong>.</p></li>
                                <li><span>4</span><p>After installation, turn off <strong>Allow from this source</strong> again.</p></li>
                            </ol>

                            <p class="login-install-blocked"><span class="material-symbols-outlined" aria-hidden="true">info</span>If Play Protect blocks the APK instead of showing the normal source warning, continue using the website and contact your teacher or administrator. Do not disable device protection.</p>
                        </div>

                        <footer class="login-install-actions">
                            <button type="button" @click="showInstallGuide = false; $nextTick(() => $refs.installGuideButton.focus())">Continue in browser</button>
                            <a href="{{ asset('downloads/AI-PGAALS-Android.apk') }}" download="AI-PGAALS-Android.apk" @click="showInstallGuide = false">
                                <span class="material-symbols-outlined" aria-hidden="true">download</span>
                                Download APK
                            </a>
                        </footer>
                    </section>
                </div>
            </section>
        </main>
        <footer class="login-footer">AI-PGAALS &middot; Literacy &amp; Numeracy</footer>
    </div>
</x-guest-layout>

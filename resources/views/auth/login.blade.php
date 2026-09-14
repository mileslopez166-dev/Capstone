<x-guest-layout>
    <div class="login-page">
        <header class="login-header">
            <a class="login-brand" href="{{ route('login') }}"><x-application-logo /><span>AI-PGAALS</span></a>
            <span>Grade 6 Learning Community</span>
        </header>

        <main class="login-main">
            <section class="login-form-panel" aria-labelledby="login-title" x-data="{ showPassword: false, submitting: false }">
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
            </section>
        </main>
        <footer class="login-footer">AI-PGAALS &middot; Literacy &amp; Numeracy</footer>
    </div>
</x-guest-layout>

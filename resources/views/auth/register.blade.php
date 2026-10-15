<x-guest-layout>
    <div class="login-page register-page">
        <canvas class="login-garden-canvas" aria-hidden="true"></canvas>
        <div class="login-playground" aria-hidden="true">
            <canvas class="login-playground-canvas"></canvas>
        </div>
        <div class="login-day-scene" aria-hidden="true">
            <img class="login-day-landscape" src="{{ asset('images/campus/pixel-campus-morning.png') }}" alt="" fetchpriority="high">
        </div>
        <div class="login-live-scene" aria-hidden="true">
            <span class="login-bird login-bird-one"></span>
            <span class="login-bird login-bird-two"></span>
            <span class="login-bird login-bird-three"></span>
        </div>
        <div class="login-night-scene" aria-hidden="true">
            <img class="login-night-landscape" src="{{ asset('images/campus/pixel-campus-night.png') }}" alt="" fetchpriority="high">
            <span class="login-night-star login-night-star-one"></span>
            <span class="login-night-star login-night-star-two"></span>
            <span class="login-night-star login-night-star-three"></span>
        </div>
        <div class="login-landmark-layer" aria-hidden="true">
            <img class="login-campus-marker" src="{{ asset('images/campus/bcs-stone-landmark.png') }}" alt="">
        </div>

        <header class="login-header">
            <a class="login-brand" href="{{ route('login') }}"><x-application-logo /><span>AI-PGAALS</span></a>
            <div class="login-header-actions">
                <span>Grade 6 Learning Community</span>
                <x-comfort-controls />
                <button type="button" class="login-motion-toggle" aria-label="Pause background animation" title="Pause background animation" aria-pressed="false" data-no-click-sound>
                    <span class="material-symbols-outlined" aria-hidden="true">pause</span>
                </button>
            </div>
        </header>

        <main class="login-main register-main">
            <section
                class="login-form-panel register-form-panel"
                aria-labelledby="register-title"
                x-data="{ role: '{{ old('role', 'student') }}', showPassword: false, showConfirmPassword: false, submitting: false }"
            >
                <div class="login-heading register-heading">
                    <span><span class="material-symbols-outlined" aria-hidden="true">school</span>Begin your learning journey</span>
                    <h1 id="register-title">Create account</h1>
                    <p>Register for your AI-PGAALS learning community.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="login-form register-form" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="role" x-model="role">

                    <fieldset class="register-role">
                        <legend>I am registering as</legend>
                        <div class="register-role-options">
                            <button type="button" :class="{ 'is-active': role === 'student' }" :aria-pressed="role === 'student'" @click="role = 'student'">
                                <span class="material-symbols-outlined" aria-hidden="true">school</span>
                                Student
                            </button>
                            <button type="button" :class="{ 'is-active': role === 'teacher' }" :aria-pressed="role === 'teacher'" @click="role = 'teacher'">
                                <span class="material-symbols-outlined" aria-hidden="true">co_present</span>
                                Teacher
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('role')" class="mt-2 text-sm text-error" />
                    </fieldset>

                    <div class="register-grid">
                        <div class="login-field">
                            <label for="first_name">First name</label>
                            <div class="login-field-wrap">
                                <input class="auth-input" id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required autofocus autocomplete="given-name" pattern="[^0-9]*" title="First name must not contain numbers." placeholder="First name" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}" @if ($errors->has('first_name')) aria-describedby="first-name-error" @endif>
                                <span class="material-symbols-outlined" aria-hidden="true">person</span>
                            </div>
                            <x-input-error id="first-name-error" :messages="$errors->get('first_name')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field">
                            <label for="middle_name">Middle name <small>(optional)</small></label>
                            <div class="login-field-wrap">
                                <input class="auth-input" id="middle_name" name="middle_name" type="text" value="{{ old('middle_name') }}" autocomplete="additional-name" pattern="[^0-9]*" title="Middle name must not contain numbers." placeholder="Middle name">
                                <span class="material-symbols-outlined" aria-hidden="true">person</span>
                            </div>
                            <x-input-error id="middle-name-error" :messages="$errors->get('middle_name')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field">
                            <label for="last_name">Last name</label>
                            <div class="login-field-wrap">
                                <input class="auth-input" id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required autocomplete="family-name" pattern="[^0-9]*" title="Last name must not contain numbers." placeholder="Last name" aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}" @if ($errors->has('last_name')) aria-describedby="last-name-error" @endif>
                                <span class="material-symbols-outlined" aria-hidden="true">person</span>
                            </div>
                            <x-input-error id="last-name-error" :messages="$errors->get('last_name')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field" x-show="role === 'student'" x-transition>
                            <label for="gender">Avatar style</label>
                            <div class="login-field-wrap">
                                <select class="auth-input register-select" id="gender" name="gender" x-bind:disabled="role !== 'student'" x-bind:required="role === 'student'" aria-invalid="{{ $errors->has('gender') ? 'true' : 'false' }}" @if ($errors->has('gender')) aria-describedby="gender-error" @endif>
                                    <option value="" disabled @selected(! old('gender'))>Choose avatar style</option>
                                    <option value="male" @selected(old('gender') === 'male')>Nova Finch - Boys</option>
                                    <option value="female" @selected(old('gender') === 'female')>Lyra Vale - Girls</option>
                                </select>
                                <span class="material-symbols-outlined" aria-hidden="true">face</span>
                            </div>
                            <x-input-error id="gender-error" :messages="$errors->get('gender')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field">
                            <label for="email">Email address</label>
                            <div class="login-field-wrap">
                                <input class="auth-input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@example.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @if ($errors->has('email')) aria-describedby="email-error" @endif>
                                <span class="material-symbols-outlined" aria-hidden="true">mail</span>
                            </div>
                            <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field">
                            <label for="section" x-text="role === 'teacher' ? 'Assigned section' : 'Your section'">Your section</label>
                            <div class="login-field-wrap">
                                <select class="auth-input register-select" id="section" name="section" required aria-invalid="{{ $errors->has('section') ? 'true' : 'false' }}" @if ($errors->has('section')) aria-describedby="section-error" @endif>
                                    <option value="" disabled @selected(! old('section'))>Select section</option>
                                    @foreach (\App\Support\SectionCatalog::labels() as $section)
                                        <option value="{{ $section }}" @selected(old('section') === $section)>{{ $section }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined" aria-hidden="true">class</span>
                            </div>
                            <x-input-error id="section-error" :messages="$errors->get('section')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field">
                            <label for="password">Password</label>
                            <div class="login-field-wrap">
                                <input class="auth-input" id="password" name="password" type="password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" placeholder="Create a password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if ($errors->has('password')) aria-describedby="password-error" @endif>
                                <span class="material-symbols-outlined" aria-hidden="true">lock</span>
                                <button class="login-password-toggle" type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" :title="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword">
                                    <span class="material-symbols-outlined" x-text="showPassword ? 'visibility_off' : 'visibility'" aria-hidden="true">visibility</span>
                                </button>
                            </div>
                            <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="login-field">
                            <label for="password_confirmation">Confirm password</label>
                            <div class="login-field-wrap">
                                <input class="auth-input" id="password_confirmation" name="password_confirmation" type="password" :type="showConfirmPassword ? 'text' : 'password'" required autocomplete="new-password" placeholder="Repeat your password">
                                <span class="material-symbols-outlined" aria-hidden="true">verified_user</span>
                                <button class="login-password-toggle" type="button" @click="showConfirmPassword = !showConfirmPassword" :aria-label="showConfirmPassword ? 'Hide confirmed password' : 'Show confirmed password'" :title="showConfirmPassword ? 'Hide confirmed password' : 'Show confirmed password'" :aria-pressed="showConfirmPassword">
                                    <span class="material-symbols-outlined" x-text="showConfirmPassword ? 'visibility_off' : 'visibility'" aria-hidden="true">visibility</span>
                                </button>
                            </div>
                            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-error" />
                        </div>
                    </div>

                    <div class="register-approval" role="note">
                        <span class="material-symbols-outlined" aria-hidden="true">pending_actions</span>
                        <p x-text="role === 'teacher' ? 'An administrator will review your teacher account before you can sign in.' : 'A teacher assigned to your section or an administrator will review your student account.'"></p>
                    </div>

                    <button class="login-submit" type="submit" :disabled="submitting">
                        <span x-text="submitting ? 'Creating account...' : 'Create account'">Create account</span>
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </button>
                </form>

                <p class="login-register register-login">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
            </section>
        </main>

        <footer class="login-footer">AI-PGAALS &middot; Literacy &amp; Numeracy</footer>
    </div>
</x-guest-layout>

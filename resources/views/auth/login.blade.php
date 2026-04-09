<x-guest-layout>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-10">
        <div class="absolute left-[-10%] top-[-10%] h-[40%] w-[40%] rounded-full bg-primary-container/20 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] h-[40%] w-[40%] rounded-full bg-secondary-container/20 blur-[120px]"></div>

        <main class="relative z-10 w-full max-w-md">
            <div class="mb-12 text-center">
                <div class="mb-6 inline-flex rotate-[-3deg] items-center justify-center rounded-lg bg-white p-3 shadow-xl">
                    <span class="material-symbols-outlined text-4xl text-primary">school</span>
                </div>
                <h1 class="mb-2 font-headline text-4xl font-extrabold tracking-tight text-on-background">AI-PGAALS</h1>
                <p class="font-medium text-on-surface-variant">Welcome back to your learning journey.</p>
            </div>

            <div class="glass-panel rounded-xl border border-white/40 p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]" x-data="{ showPassword: false, role: '{{ old('role', 'student') }}' }">
                <x-auth-session-status class="mb-5 rounded-lg bg-secondary-container/40 px-4 py-3 text-sm font-medium text-on-secondary-container" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <input type="hidden" name="role" x-model="role">

                    <div class="mb-4 grid grid-cols-2 gap-4 rounded-lg bg-surface-container-low p-1">
                        <button
                            class="flex items-center justify-center gap-2 rounded-lg py-3 px-4 text-sm transition-all active:scale-95"
                            type="button"
                            :class="role === 'student' ? 'bg-surface-container-lowest font-bold text-primary shadow-sm' : 'font-medium text-on-surface-variant hover:bg-surface-container-high/50'"
                            @click="role = 'student'"
                        >
                            <span class="material-symbols-outlined text-xl">face</span>
                            <span>Student</span>
                        </button>
                        <button
                            class="flex items-center justify-center gap-2 rounded-lg py-3 px-4 text-sm transition-all active:scale-95"
                            type="button"
                            :class="role === 'teacher' ? 'bg-surface-container-lowest font-bold text-primary shadow-sm' : 'font-medium text-on-surface-variant hover:bg-surface-container-high/50'"
                            @click="role = 'teacher'"
                        >
                            <span class="material-symbols-outlined text-xl">workspace_premium</span>
                            <span>Teacher</span>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('role')" class="mt-2 text-sm text-error" />

                    <div class="space-y-2">
                        <label class="ml-1 block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="email">Email Address</label>
                        <div class="relative">
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="name@example.com"
                                class="auth-input"
                            >
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">alternate_email</span>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-error" />
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between px-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="password">Password</label>
                            @if (Route::has('password.request'))
                                <a class="text-xs font-bold text-primary transition-colors hover:text-primary-dim" href="{{ route('password.request') }}">
                                    Forgot?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="auth-input"
                            >
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                            <button
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-outline transition-colors hover:text-on-surface"
                                type="button"
                                @click="showPassword = !showPassword"
                                x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <span class="material-symbols-outlined text-xl" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-error" />
                    </div>

                    <div class="flex items-center gap-3 px-1">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="h-5 w-5 rounded border-outline-variant bg-surface-container-low text-primary focus:ring-primary-container"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label class="cursor-pointer text-sm font-medium text-on-surface-variant" for="remember">Keep me logged in</label>
                    </div>

                    <button class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-primary to-primary-container py-5 text-lg font-bold text-on-primary shadow-lg transition-all hover:shadow-primary/20 active:scale-[0.98]" type="submit">
                        <span>Sign In to Dashboard</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>

                <div class="mt-10 border-t border-outline-variant/10 pt-8 text-center">
                    <p class="text-sm font-medium text-on-surface-variant">
                        New to the ecosystem?
                        <a class="inline-block font-bold text-primary transition-all hover:text-primary-dim hover:underline" href="{{ route('register') }}">
                            Create an account
                        </a>
                    </p>
                </div>
            </div>

            <div class="mt-12 flex items-center justify-center gap-8 opacity-40 grayscale contrast-125">
                <img class="h-6" alt="Minimalist educational technology logo" src="https://lh3.googleusercontent.com/aida-public/AB6AXuACBJzZLtnpC82h3M_Qx8p1RwtP-n1ZrJdImoR2W3ijea4e3m75v9xcw03Q1iXXAg3-oQ6XMHf3S31tm53bsVHjaIoGE3GAjTRhrOIpgo_exRgneVvw78dzYb9kJq5hUg2LxBP5el9Q_32gG25Viybg6Y2I4n3ngvDErN8vsvX2162NXjT9J-NcbQ2QQpXfcUeoBHXRovfGOHObrZx3kyiCYJQ3wxK6unPkd4x-zmozsEpgTNlFTIilnBpaGZqk7nQFbV26CwRr7RGQ">
                <img class="h-6" alt="Minimalist company logo for a digital learning platform" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAWn7ZtH1uOnxQzwrtJqW6gOq0I64eLfNKnrMV7mxYcmRmNXHpKMyitmTeX-kObEmjFeSlMefycY0GQNDYG4E-1OmQHDxHdS5jJeplLnQIRRl784wNLfP51pjCw1ANGQpbKgV38SylJCoHuu1sQxWP0o23UKqZ3tREuo11jXbtv-_Waf_7Ibhb2xr-MH5D0SSG18Z-pUOzMkBIaDv0x2UMlDly2ioIWsFnksmGG1opoJTfGhkAn9O4pzc_ipHFEeBKWCktqTWEAKHxX">
                <img class="h-6" alt="Modern tech brand mark for school software" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBvSNeD6EeUD_4n6uGZXsZWmR0jqXBzZNKyKrpqPDq0AiiMDUqqifa-BM2Dyws7g-jfc1q7Udu-a9vP54UAMoiYIGQYFRdzlcdxxRX6mSdhn_dNra_wvvPOB790VL4oR3SA8nNut2osRWdU_BSI42OCRrzGOalGaDzHA4-1iDfKM0vnmJfNCge4QuumSn3Y_kX1zb1CSYSglAysg1-TG6rjwJRZJnRi7ulctSx-x0Rsw4Zhp5DSqXJhbMZkhUjJuBeebpKqwTq3k5Fp">
            </div>
        </main>

        <div class="fixed bottom-8 left-8 hidden md:block">
            <div class="flex items-center gap-4 rounded-lg border border-white/20 bg-surface-container-lowest/50 p-4 backdrop-blur-sm">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-secondary-container">
                    <span class="material-symbols-outlined text-on-secondary-container">verified_user</span>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Secure Access</p>
                    <p class="text-sm font-medium">Enterprise Grade Encryption</p>
                </div>
            </div>
        </div>

        <div class="fixed bottom-8 right-8 hidden items-center gap-2 md:flex">
            <span class="h-2 w-2 animate-pulse rounded-full bg-secondary"></span>
            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">System Status: Optimal</p>
        </div>
    </div>
</x-guest-layout>

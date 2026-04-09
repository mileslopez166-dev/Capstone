<x-app-layout>
    @php
        $user = $user ?? Auth::user();
        $isStudent = $user->isStudent();
        $firstName = str($user->name)->before(' ')->title();
        $initials = str($user->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($part) => str($part)->substr(0, 1)->upper())
            ->implode('');
    @endphp

    <div class="min-h-screen bg-background font-body text-on-surface">
        @if ($isStudent)
            <nav class="sticky top-0 z-50 bg-white/80 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl">
                <div class="mx-auto flex w-full items-center justify-between px-6 py-4">
                    <div class="text-2xl font-extrabold italic text-blue-600">AI-PGAALS</div>

                    <div class="hidden items-center gap-8 md:flex">
                        <a class="font-medium font-headline text-slate-500 transition-colors hover:text-blue-500" href="{{ route('student.dashboard') }}">Home</a>
                        <a class="font-medium font-headline text-slate-500 transition-colors hover:text-blue-500" href="{{ route('student.activities') }}">Activities</a>
                        <a class="font-medium font-headline text-slate-500 transition-colors hover:text-blue-500" href="{{ route('student.rewards') }}">Rewards</a>
                        <a class="border-b-4 border-blue-500 font-bold font-headline text-blue-700 transition-colors hover:text-blue-500" href="{{ route('profile.edit') }}">Profile</a>
                    </div>

                    <div class="flex items-center gap-4">
                        <button class="text-on-surface-variant transition-colors hover:text-primary" type="button">
                            <span class="material-symbols-outlined">notifications</span>
                        </button>
                        <a class="text-on-surface-variant transition-colors hover:text-primary" href="{{ route('profile.edit') }}">
                            <span class="material-symbols-outlined">account_circle</span>
                        </a>
                    </div>
                </div>
            </nav>
        @endif

        <main class="mx-auto max-w-6xl px-4 py-8 pb-32 md:px-6">
            <section class="mb-8 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="overflow-hidden rounded-lg bg-gradient-to-br from-primary to-primary-container p-8 text-on-primary shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.25em] text-white/70">{{ $isStudent ? 'Student Profile' : 'Account Center' }}</p>
                    <h1 class="mt-4 font-headline text-4xl font-extrabold tracking-tight md:text-5xl">{{ $isStudent ? "Keep growing, {$firstName}" : $user->name }}</h1>
                    <p class="mt-4 max-w-md text-lg text-white/85">
                        {{ $isStudent ? 'Manage your identity, secure your account, and stay ready for every new mission.' : 'Update your account details and security settings in one place.' }}
                    </p>

                    <div class="mt-8 flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/15 text-2xl font-black text-white shadow-inner">
                            {{ $initials }}
                        </div>
                        <div>
                            <p class="font-headline text-xl font-bold">{{ $user->name }}</p>
                            <p class="text-sm font-medium text-white/75">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Account Type</p>
                        <p class="mt-3 font-headline text-2xl font-bold text-primary">{{ ucfirst($user->role ?? 'student') }}</p>
                    </div>
                    <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Profile Status</p>
                        <p class="mt-3 font-headline text-2xl font-bold text-secondary">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</p>
                    </div>
                    <div class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Security</p>
                        <p class="mt-3 font-headline text-2xl font-bold text-tertiary">Active</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="space-y-6">
                    <div class="rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div class="mb-6">
                            <h2 class="font-headline text-2xl font-bold text-on-surface">Profile Information</h2>
                            <p class="mt-2 text-on-surface-variant">Update your display name and email address.</p>
                        </div>

                        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                            @csrf
                        </form>

                        <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                            @csrf
                            @method('patch')

                            <div>
                                <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="name">Full Name</label>
                                <input id="name" name="name" type="text" class="w-full rounded-xl border-none bg-surface-container-low px-4 py-3.5 text-on-surface placeholder:text-outline-variant focus:ring-2 focus:ring-primary/20" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                                <x-input-error class="mt-2 text-sm text-error" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="email">Email Address</label>
                                <input id="email" name="email" type="email" class="w-full rounded-xl border-none bg-surface-container-low px-4 py-3.5 text-on-surface placeholder:text-outline-variant focus:ring-2 focus:ring-primary/20" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                <x-input-error class="mt-2 text-sm text-error" :messages="$errors->get('email')" />

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="mt-3 rounded-xl bg-tertiary-container/40 p-4 text-sm text-on-tertiary-container">
                                        <p>Your email address is unverified.</p>
                                        <button form="send-verification" class="mt-2 font-bold underline" type="submit">Click here to re-send the verification email.</button>

                                        @if (session('status') === 'verification-link-sent')
                                            <p class="mt-2 font-bold">A new verification link has been sent to your email address.</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-4">
                                <button class="rounded-xl bg-gradient-to-r from-primary to-primary-container px-6 py-3 font-bold text-on-primary shadow-lg transition-transform hover:scale-[1.02] active:scale-[0.98]" type="submit">Save Changes</button>

                                @if (session('status') === 'profile-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm font-medium text-secondary"
                                    >Saved.</p>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="rounded-lg bg-surface-container-lowest p-8 shadow-[0_20px_40px_rgba(0,94,159,0.06)]">
                        <div class="mb-6">
                            <h2 class="font-headline text-2xl font-bold text-on-surface">Update Password</h2>
                            <p class="mt-2 text-on-surface-variant">Choose a strong password to keep your account secure.</p>
                        </div>

                        <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                            @csrf
                            @method('put')

                            <div>
                                <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="update_password_current_password">Current Password</label>
                                <input id="update_password_current_password" name="current_password" type="password" class="w-full rounded-xl border-none bg-surface-container-low px-4 py-3.5 text-on-surface focus:ring-2 focus:ring-primary/20" autocomplete="current-password">
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2 text-sm text-error" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="update_password_password">New Password</label>
                                <input id="update_password_password" name="password" type="password" class="w-full rounded-xl border-none bg-surface-container-low px-4 py-3.5 text-on-surface focus:ring-2 focus:ring-primary/20" autocomplete="new-password">
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2 text-sm text-error" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="update_password_password_confirmation">Confirm Password</label>
                                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full rounded-xl border-none bg-surface-container-low px-4 py-3.5 text-on-surface focus:ring-2 focus:ring-primary/20" autocomplete="new-password">
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2 text-sm text-error" />
                            </div>

                            <div class="flex items-center gap-4">
                                <button class="rounded-xl bg-surface-container-highest px-6 py-3 font-bold text-on-surface transition-colors hover:bg-surface-variant active:scale-[0.98]" type="submit">Update Password</button>

                                @if (session('status') === 'password-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm font-medium text-secondary"
                                    >Saved.</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </section>

                <aside class="space-y-6">
                    <div class="rounded-lg bg-surface-container-low p-8">
                        <h2 class="font-headline text-2xl font-bold text-on-surface">Account Summary</h2>
                        <div class="mt-6 space-y-4">
                            <div class="rounded-xl bg-surface-container-lowest p-4">
                                <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Full Name</p>
                                <p class="mt-2 font-headline text-xl font-bold">{{ $user->name }}</p>
                            </div>
                            <div class="rounded-xl bg-surface-container-lowest p-4">
                                <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Primary Email</p>
                                <p class="mt-2 text-sm font-medium text-on-surface">{{ $user->email }}</p>
                            </div>
                            <div class="rounded-xl bg-surface-container-lowest p-4">
                                <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Role Access</p>
                                <p class="mt-2 text-sm font-bold text-primary">{{ ucfirst($user->role ?? 'student') }}</p>
                            </div>
                        </div>

                        <form method="post" action="{{ route('logout') }}" class="mt-6">
                            @csrf
                            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-surface-container-highest px-6 py-3 font-bold text-on-surface transition-colors hover:bg-surface-variant active:scale-[0.98]" type="submit">
                                <span class="material-symbols-outlined text-base">logout</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>

                    <div class="rounded-lg border border-error/20 bg-error-container/10 p-8">
                        <h2 class="font-headline text-2xl font-bold text-error">Danger Zone</h2>
                        <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">Deleting your account permanently removes your access. Enter your password only if you really want to continue.</p>

                        <form method="post" action="{{ route('profile.destroy') }}" class="mt-6 space-y-4">
                            @csrf
                            @method('delete')

                            <div>
                                <label class="mb-2 block text-sm font-bold text-on-surface-variant" for="delete_password">Confirm Password</label>
                                <input id="delete_password" name="password" type="password" class="w-full rounded-xl border-none bg-surface-container-lowest px-4 py-3.5 text-on-surface focus:ring-2 focus:ring-error/20" placeholder="Password">
                                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 text-sm text-error" />
                            </div>

                            <button class="w-full rounded-xl bg-error px-6 py-3 font-bold text-on-error shadow-lg transition-transform hover:scale-[1.01] active:scale-[0.98]" type="submit">
                                Delete Account
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </main>

        @if ($isStudent)
            <nav class="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around rounded-t-[2.5rem] bg-white/80 px-4 pb-6 pt-4 shadow-2xl backdrop-blur-2xl md:hidden dark:bg-slate-900/80">
                <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.dashboard') }}">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-[10px] font-bold lowercase">Home</span>
                </a>
                <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.activities') }}">
                    <span class="material-symbols-outlined">rocket_launch</span>
                    <span class="text-[10px] font-bold lowercase">Activities</span>
                </a>
                <a class="flex flex-col items-center justify-center px-4 py-2 text-slate-400 transition-transform hover:scale-105 dark:text-slate-500" href="{{ route('student.rewards') }}">
                    <span class="material-symbols-outlined">backpack</span>
                    <span class="text-[10px] font-bold lowercase">Rewards</span>
                </a>
                <div class="flex scale-110 flex-col items-center justify-center rounded-[2rem] bg-blue-100 px-6 py-2 text-blue-700 shadow-inner dark:bg-blue-900/40 dark:text-blue-300">
                    <span class="material-symbols-outlined">face</span>
                    <span class="text-[10px] font-bold lowercase">Profile</span>
                </div>
            </nav>
        @endif
    </div>
</x-app-layout>

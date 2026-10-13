<x-guest-layout>
    <div class="relative flex app-safe-screen items-center justify-center overflow-x-hidden bg-background px-4 py-6 sm:px-6 sm:py-10">
        <div class="absolute left-20 top-20 -z-10 h-32 w-32 rounded-full bg-primary/5 blur-2xl"></div>
        <div class="absolute bottom-40 right-40 -z-10 h-48 w-48 rounded-full bg-secondary/5 blur-3xl"></div>
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top_left,rgba(68,165,255,0.15),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(145,247,142,0.15),transparent_30%),radial-gradient(circle_at_top_right,rgba(255,235,59,0.10),transparent_28%)]"></div>

        <main class="glass-panel w-full max-w-[620px] overflow-hidden rounded-xl border border-white/40 shadow-2xl">
            <section class="flex flex-col justify-center p-5 sm:p-8 md:p-12 lg:p-16" x-data="{ role: '{{ old('role', 'student') }}', showPassword: false, showConfirmPassword: false }">
                <div class="mb-8 sm:mb-10">
                    <h2 class="mb-2 font-headline text-3xl font-bold text-on-surface">Create Account</h2>
                    <p class="text-on-surface-variant">Fill in your details to get started with AI-PGAALS.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    <input type="hidden" name="role" x-model="role">

                    <div class="space-y-3">
                        <span class="px-1 text-sm font-semibold text-on-surface-variant">I am a...</span>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                            <button
                                type="button"
                                class="flex flex-col items-center justify-center rounded-xl border-2 p-4 transition-all"
                                :class="role === 'student' ? 'border-primary bg-primary text-white shadow-[0_10px_25px_-5px_rgba(0,94,159,0.3)]' : 'border-primary/20 bg-primary/5 text-primary hover:border-primary/40 hover:bg-primary/10'"
                                @click="role = 'student'"
                            >
                                <span class="material-symbols-outlined mb-1">school</span>
                                <span class="text-sm font-bold">Student</span>
                            </button>
                            <button
                                type="button"
                                class="flex flex-col items-center justify-center rounded-xl border-2 p-4 transition-all"
                                :class="role === 'teacher' ? 'border-primary bg-primary text-white shadow-[0_10px_25px_-5px_rgba(0,94,159,0.3)]' : 'border-primary/20 bg-primary/5 text-primary hover:border-primary/40 hover:bg-primary/10'"
                                @click="role = 'teacher'"
                            >
                                <span class="material-symbols-outlined mb-1">co_present</span>
                                <span class="text-sm font-bold">Teacher</span>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('role')" class="mt-2 text-sm text-error" />
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-4">
                            <div class="group">
                                <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="first_name">First Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">person</span>
                                    <input
                                        id="first_name"
                                        name="first_name"
                                        type="text"
                                        value="{{ old('first_name') }}"
                                        required
                                        autofocus
                                        autocomplete="given-name"
                                        pattern="[^0-9]*"
                                        title="First name must not contain numbers."
                                        placeholder="John"
                                        class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-4 placeholder:text-outline-variant transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2 text-sm text-error" />
                            </div>

                            <div class="group">
                                <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="middle_name">Middle Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">person</span>
                                    <input
                                        id="middle_name"
                                        name="middle_name"
                                        type="text"
                                        value="{{ old('middle_name') }}"
                                        autocomplete="additional-name"
                                        pattern="[^0-9]*"
                                        title="Middle name must not contain numbers."
                                        placeholder="A."
                                        class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-4 placeholder:text-outline-variant transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2 text-sm text-error" />
                            </div>

                            <div class="group">
                                <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="last_name">Last Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">person</span>
                                    <input
                                        id="last_name"
                                        name="last_name"
                                        type="text"
                                        value="{{ old('last_name') }}"
                                        required
                                        autocomplete="family-name"
                                        pattern="[^0-9]*"
                                        title="Last name must not contain numbers."
                                        placeholder="Doe"
                                        class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-4 placeholder:text-outline-variant transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2 text-sm text-error" />
                            </div>
                            <div class="group" x-show="role === 'student'" x-transition>
                                <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="gender">Avatar Style</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">face</span>
                                    <select
                                        id="gender"
                                        name="gender"
                                        x-bind:disabled="role !== 'student'"
                                        x-bind:required="role === 'student'"
                                        class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-10 transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                    >
                                        <option value="" disabled @selected(! old('gender'))>Choose avatar style</option>
                                        <option value="male" @selected(old('gender') === 'male')>Nova Finch - Boys</option>
                                        <option value="female" @selected(old('gender') === 'female')>Lyra Vale - Girls</option>
                                    </select>
                                </div>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2 text-sm text-error" />
                            </div>
                        </div>

                        <div class="group">
                            <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="email">Email Address</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">alternate_email</span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="username"
                                    placeholder="name@example.com"
                                    class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-4 placeholder:text-outline-variant transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                >
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="group" x-show="role === 'teacher'" x-transition>
                            <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="section">Assigned Section</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">class</span>
                                <select
                                    id="section"
                                    name="section"
                                    x-bind:disabled="role !== 'teacher'"
                                    x-bind:required="role === 'teacher'"
                                    class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-10 transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                >
                                    <option value="" disabled selected>Select assigned section</option>
                                    @foreach (\App\Support\SectionCatalog::labels() as $section)
                                        <option value="{{ $section }}" @selected(old('section') === $section)>{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('section')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="rounded-xl border border-primary/10 bg-primary/5 p-4 text-sm text-on-surface-variant" x-show="role === 'teacher' || role === 'student'" x-transition>
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-primary">pending_actions</span>
                                <div>
                                    <p class="font-semibold text-on-surface" x-text="role === 'teacher' ? 'Teacher accounts require administrator approval.' : 'Student accounts require administrator approval.'"></p>
                                    <p class="mt-1" x-text="role === 'teacher' ? 'After registration, your request will be reviewed in the admin token request queue before you can sign in.' : 'After registration, your request will be reviewed first. Your section will be assigned by a teacher or administrator.'"></p>
                                </div>
                            </div>
                        </div>

                        <div class="group">
                            <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="password">Password</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">lock</span>
                                <input
                                    id="password"
                                    name="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="********"
                                    class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-12 placeholder:text-outline-variant transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                >
                                <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface" type="button" @click="showPassword = !showPassword">
                                    <span class="material-symbols-outlined text-xl" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-error" />
                        </div>

                        <div class="group">
                            <label class="mb-1.5 block px-1 text-sm font-semibold text-on-surface-variant" for="password_confirmation">Confirm Password</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline-variant transition-colors group-focus-within:text-primary">verified_user</span>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    x-bind:type="showConfirmPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Re-enter your password"
                                    class="w-full rounded-xl border-none bg-surface-container-low py-3.5 pl-12 pr-12 placeholder:text-outline-variant transition-all focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/20"
                                >
                                <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface" type="button" @click="showConfirmPassword = !showConfirmPassword">
                                    <span class="material-symbols-outlined text-xl" x-text="showConfirmPassword ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-error" />
                        </div>
                    </div>

                    <div class="pt-2">
                        <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-primary to-primary-dim py-4 font-bold text-white shadow-lg shadow-primary/20 transition-all hover:shadow-primary/40 active:scale-[0.98]" type="submit">
                            Create Account
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </button>
                    </div>
                </form>

                <div class="mt-10 text-center">
                    <p class="font-medium text-on-surface-variant">
                        Already have an account?
                        <a class="ml-1 font-bold text-primary hover:underline" href="{{ route('login') }}">Log in</a>
                    </p>
                </div>

                <p class="mt-8 text-center text-[11px] leading-tight text-outline">
                    By clicking "Create Account", you agree to AI-PGAALS's <br>
                    <a class="underline" href="#">Terms of Service</a> and <a class="underline" href="#">Privacy Policy</a>.
                </p>
            </section>
        </main>
    </div>
</x-guest-layout>

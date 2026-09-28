<x-app-layout>
    @php
        $teacher = Auth::user();
        $teacherName = $teacher->name;
        $teacherInitials = collect(explode(' ', $teacherName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $sections = ['Section A', 'Section B', 'Section C'];
    @endphp

    <div class="min-h-screen bg-background">
        <div class="flex min-h-screen">
            <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="students" />

            <main class="min-h-screen flex-1 lg:ml-72">
                <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search student accounts..." />

                <div class="mx-auto max-w-4xl space-y-8 p-5 sm:p-8">
                    <section class="teacher-workspace-heading flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <h1 class="font-headline text-3xl font-extrabold text-on-surface">Add Student</h1>
                        <a class="ui-button ui-button-secondary" href="{{ route('students.index') }}">
                            <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                            Student List
                        </a>
                    </section>

                    <section id="add-student" class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,0,0,0.03)] sm:p-8">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-container/20 text-primary">
                                <span class="material-symbols-outlined">person_add</span>
                            </div>
                            <div>
                                <h2 class="font-headline text-xl font-extrabold text-on-surface">Add Student Account</h2>
                                <p class="mt-1 text-sm text-on-surface-variant">Grade 6 student enrollment.</p>
                            </div>
                        </div>

                        <form class="grid grid-cols-1 gap-5 md:grid-cols-2" method="POST" action="{{ route('students.store') }}">
                            @csrf

                            <div class="space-y-2 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-first-name">First Name</label>
                                <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-first-name" name="first_name" type="text" value="{{ old('first_name') }}" required>
                                @error('first_name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-middle-name">Middle Name</label>
                                <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-middle-name" name="middle_name" type="text" value="{{ old('middle_name') }}">
                                @error('middle_name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-last-name">Last Name</label>
                                <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-last-name" name="last_name" type="text" value="{{ old('last_name') }}" required>
                                @error('last_name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-email">Email Address</label>
                                <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-email" name="email" type="email" value="{{ old('email') }}" required>
                                @error('email')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-section">Section</label>
                                <select class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-section" name="section">
                                    <option value="">{{ $teacher->section ? $teacher->section.' (my section)' : 'No assigned section' }}</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section }}" @selected(old('section') === $section)>{{ $section }}</option>
                                    @endforeach
                                </select>
                                @error('section')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-gender">Avatar Style</label>
                                <select class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-gender" name="gender" required>
                                    <option value="" disabled @selected(! old('gender'))>Choose avatar style</option>
                                    <option value="male" @selected(old('gender') === 'male')>Nova Finch - Boys</option>
                                    <option value="female" @selected(old('gender') === 'female')>Lyra Vale - Girls</option>
                                </select>
                                @error('gender')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-password">Password</label>
                                <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-password" name="password" type="password" autocomplete="new-password" required>
                                @error('password')<p class="text-sm text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="student-password-confirmation">Confirm Password</label>
                                <input class="w-full rounded-sm border-none bg-surface-container-low px-4 py-3 text-on-surface shadow-inner focus:ring-2 focus:ring-primary" id="student-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 md:col-span-2">
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 font-bold text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim sm:w-auto" type="submit">
                                    <span class="material-symbols-outlined text-lg">person_add</span>
                                    Create Student
                                </button>
                                <a class="ui-button ui-button-secondary" href="{{ route('students.index') }}">Cancel</a>
                            </div>
                        </form>
                    </section>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>

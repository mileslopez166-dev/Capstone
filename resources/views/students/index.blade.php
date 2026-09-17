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

                <div class="mx-auto max-w-7xl space-y-8 p-5 sm:p-8">
                    <section class="teacher-workspace-heading flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 class="font-headline text-4xl font-extrabold tracking-tight text-on-surface">Students</h1>
                            <p class="mt-2 text-on-surface-variant">This list is connected to the database and shows real student accounts in the system.</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="rounded-lg bg-surface-container-low px-5 py-4">
                                <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Student Accounts</p>
                                <p class="mt-2 font-headline text-3xl font-black text-primary">{{ $students->count() }}</p>
                            </div>
                            <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-bold text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim" href="#add-student">
                                <span class="material-symbols-outlined text-lg">person_add</span>
                                Add Student
                            </a>
                        </div>
                    </section>

                    @if (session('status'))
                        <div class="rounded-lg border border-secondary/20 bg-secondary-container/30 px-4 py-3 text-sm font-medium text-on-surface">
                            {{ session('status') }}
                        </div>
                    @endif

                    <section id="add-student" class="rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,0,0,0.03)] sm:p-8">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-container/20 text-primary">
                                <span class="material-symbols-outlined">person_add</span>
                            </div>
                            <div>
                                <h2 class="font-headline text-xl font-extrabold text-on-surface">Add Student Account</h2>
                                <p class="mt-1 text-sm text-on-surface-variant">Create an approved Grade 6 student account and save it directly to the database.</p>
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
                                    <option value="">Use my assigned section{{ $teacher->section ? ' - '.$teacher->section : '' }}</option>
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

                            <div class="md:col-span-2">
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 font-bold text-on-primary shadow-lg shadow-primary/20 transition-colors hover:bg-primary-dim sm:w-auto" type="submit">
                                    <span class="material-symbols-outlined text-lg">person_add</span>
                                    Create Student
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="overflow-hidden rounded-lg bg-surface-container-lowest shadow-[0_20px_40px_rgba(0,0,0,0.03)]">
                        <div class="border-b border-outline-variant/10 p-8">
                            <h4 class="font-headline text-xl font-extrabold text-on-surface">Student Roster</h4>
                            <p class="mt-2 text-sm font-medium text-on-surface-variant">Real-time student accounts with progress from your assessment submissions.</p>
                        </div>

                        @if ($students->isEmpty())
                            <div class="flex min-h-[20rem] flex-col items-center justify-center p-8 text-center">
                                <span class="material-symbols-outlined text-6xl text-outline-variant">school</span>
                                <h5 class="mt-4 font-headline text-2xl font-bold text-on-surface">No student accounts yet</h5>
                                <p class="mt-2 max-w-xl text-sm text-on-surface-variant">As soon as students register in the system, they will appear here automatically.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-left">
                                    <thead>
                                        <tr class="bg-surface-container-low/50">
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Student Name</th>
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Email</th>
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Status</th>
                                            <th class="px-8 py-4 text-right text-[10px] font-black uppercase tracking-widest text-on-surface-variant">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/5">
                                        @foreach ($students as $student)
                                            @php
                                                $initials = collect(explode(' ', $student->name))
                                                    ->filter()
                                                    ->take(2)
                                                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                                    ->implode('');
                                            @endphp
                                            <tr class="group transition-colors hover:bg-surface-container-low/30">
                                                <td class="px-8 py-5">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container/20 font-bold text-primary">{{ $initials }}</div>
                                                        <div>
                                                            <a class="font-bold text-on-surface transition-colors hover:text-primary" href="{{ route('students.show', $student) }}">{{ $student->name }}</a>
                                                            <p class="text-xs text-on-surface-variant">Student Account</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-5 text-sm text-on-surface">{{ $student->email }}</td>
                                                <td class="px-8 py-5">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-high px-3 py-1 text-[10px] font-black uppercase text-on-surface-variant">
                                                            <span class="h-2.5 w-2.5 rounded-full {{ $student->is_online ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                            {{ $student->is_online ? 'Online' : 'Offline' }}
                                                        </span>
                                                        <span class="rounded-full bg-surface-container-high px-3 py-1 text-[10px] font-black uppercase text-on-surface-variant">{{ $student->average_accuracy === null ? 'No Progress Yet' : $student->average_accuracy.'% Avg' }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-5 text-right">
                                                    <a class="inline-flex p-2 text-on-surface-variant transition-colors hover:text-primary" href="{{ route('students.show', $student) }}">
                                                        <span class="material-symbols-outlined">open_in_new</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>

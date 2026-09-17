<x-app-layout>
    @php
        $isStudent = Auth::user()->isStudent();
        $isTeacher = Auth::user()->isTeacher();
        $teacherInitials = collect(explode(' ', Auth::user()->name))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    @endphp
    <div class="min-h-screen bg-surface text-on-surface">
        @if ($isStudent)
            <x-student-nav active="support" />
        @elseif ($isTeacher)
            <x-teacher-sidebar :teacher-name="Auth::user()->name" :teacher-initials="$teacherInitials" active="support" />
            <div class="lg:ml-72"><x-teacher-topbar :teacher-name="Auth::user()->name" :teacher-initials="$teacherInitials" /></div>
        @endif
        <main class="{{ $isStudent || $isTeacher ? 'px-4 py-8 pb-32 sm:px-8 lg:ml-72 lg:px-12' : 'px-6 py-8 sm:px-8 lg:px-12' }}">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-4xl items-center justify-center">
            <section class="w-full rounded-DEFAULT bg-surface-container-lowest p-8 text-center shadow-[0_24px_70px_rgba(0,94,159,0.08)] sm:p-12">
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary-container/30 text-primary">
                    <span class="material-symbols-outlined text-4xl">construction</span>
                </div>
                <p class="text-sm font-bold uppercase tracking-[0.25em] text-primary">Support</p>
                <h1 class="mt-3 font-headline text-3xl font-black tracking-tight text-on-surface sm:text-4xl">This system is currently developing</h1>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-on-surface-variant sm:text-base">
                    The support center is being prepared. Please check back once this section is available.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-bold uppercase tracking-wide text-on-primary transition-colors hover:bg-primary-dim" href="{{ route(Auth::user()->dashboardRouteName()) }}">
                        <span class="material-symbols-outlined text-[18px]">dashboard</span>
                        Back to Dashboard
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-lg bg-surface-container-low px-5 py-3 text-sm font-bold uppercase tracking-wide text-on-surface-variant transition-colors hover:bg-surface-container" href="{{ url()->previous() }}">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        Go Back
                    </a>
                </div>
            </section>
        </div>
        </main>
    </div>
</x-app-layout>

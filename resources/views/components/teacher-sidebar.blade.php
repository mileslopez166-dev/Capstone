@props([
    'teacherName',
    'teacherInitials',
    'active' => 'dashboard',
])

<aside class="campus-sidebar fixed inset-y-0 left-0 z-40 hidden w-72 flex-col bg-slate-50 py-8 lg:flex" aria-label="Teacher navigation">
    <div class="campus-sidebar-brand">
        <a class="teacher-brand" href="{{ route('teacher.dashboard') }}"><x-application-logo /><span><strong>AI-PGAALS</strong><small>Teacher Workspace</small></span></a>
    </div>

    <div class="campus-sidebar-identity">
        <div class="flex items-center gap-3">
            <div class="relative">
                <x-teacher-avatar :teacher="Auth::user()" />
                <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-slate-50 bg-secondary"></div>
            </div>
            <div class="min-w-0">
                <p class="truncate font-headline text-sm font-bold text-on-surface" title="{{ $teacherName }}">{{ $teacherName }}</p>
                <p class="text-[10px] font-medium uppercase tracking-widest text-on-surface-variant">Teacher</p>
            </div>
        </div>
    </div>

    <nav class="campus-sidebar-links flex-1" aria-label="Teacher pages">
        <x-role-navigation role="teacher" />
    </nav>

    <div class="teacher-section-label"><span class="material-symbols-outlined" aria-hidden="true">school</span>Grade 6{{ Auth::user()->section ? ' / '.Auth::user()->section : '' }}</div>

    <div class="campus-sidebar-footer mt-auto space-y-1 px-2">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="flex w-full items-center gap-3 px-4 py-3 text-left font-headline text-sm uppercase tracking-widest text-slate-500 transition-all hover:bg-slate-200" type="submit">
                <span class="material-symbols-outlined">logout</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

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
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container font-headline text-sm font-bold text-on-primary-container">
                    {{ $teacherInitials }}
                </div>
                <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-slate-50 bg-secondary"></div>
            </div>
            <div class="min-w-0">
                <p class="truncate font-headline text-sm font-bold text-on-surface" title="{{ $teacherName }}">{{ $teacherName }}</p>
                <p class="text-[10px] font-medium uppercase tracking-widest text-on-surface-variant">Teacher</p>
            </div>
        </div>
    </div>

    <nav class="campus-sidebar-links flex-1 space-y-1 px-2">
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === 'dashboard' ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === 'students' ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ route('students.index') }}">
            <span class="material-symbols-outlined">group</span>
            <span>Students</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === 'reports' ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ route('reports.index') }}">
            <span class="material-symbols-outlined">assessment</span>
            <span>Reports</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === 'assessments' ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ route('assessments.index') }}">
            <span class="material-symbols-outlined">note_add</span>
            <span>Assessments</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase transition-all {{ $active === 'practice' ? 'border-r-4 border-blue-600 font-bold text-blue-700' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ route('teacher.practice.index') }}" @if ($active === 'practice') aria-current="page" @endif>
            <span class="material-symbols-outlined" aria-hidden="true">flag</span><span>Practice</span>
        </a>
    </nav>

    <div class="teacher-section-label"><span class="material-symbols-outlined" aria-hidden="true">school</span>Grade 6{{ Auth::user()->section ? ' / '.Auth::user()->section : '' }}</div>

    <div class="campus-sidebar-footer mt-auto space-y-1 px-2">
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest {{ $active === 'profile' ? 'border-r-4 border-blue-600 font-bold text-blue-700' : 'text-slate-500' }} transition-all hover:bg-slate-200" href="{{ route('profile.edit') }}">
            <span class="material-symbols-outlined">settings</span>
            <span>Settings</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="flex w-full items-center gap-3 px-4 py-3 text-left font-headline text-sm uppercase tracking-widest text-slate-500 transition-all hover:bg-slate-200" type="submit">
                <span class="material-symbols-outlined">logout</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

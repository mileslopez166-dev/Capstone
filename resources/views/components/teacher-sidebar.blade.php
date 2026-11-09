@props([
    'teacherName',
    'teacherInitials',
    'active' => 'dashboard',
])

@php
    $teacherLinks = [
        ['label' => 'Home', 'icon' => 'home', 'url' => route('teacher.dashboard'), 'active' => request()->routeIs('teacher.dashboard', 'dashboard')],
        ['label' => 'Students', 'icon' => 'groups', 'url' => route('students.index'), 'active' => request()->routeIs('students.*')],
        ['label' => 'Approvals', 'icon' => 'how_to_reg', 'url' => route('students.index').'#enrollment-requests-title', 'active' => false],
        ['label' => 'Assessments', 'icon' => 'assignment', 'url' => route('assessments.index'), 'active' => request()->routeIs('assessments.*', 'teacher.assessments.*', 'worksheets.*')],
        ['label' => 'Performance', 'icon' => 'monitoring', 'url' => route('assessments.results'), 'active' => request()->routeIs('assessments.results', 'teacher.phil-iri.*')],
        ['label' => 'Reports', 'icon' => 'bar_chart', 'url' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
    ];
@endphp

<header class="campus-sidebar teacher-global-nav" aria-label="Teacher navigation">
    <div class="teacher-global-nav-inner">
        <a class="teacher-brand" href="{{ route('teacher.dashboard') }}" aria-label="AI-PGAALS teacher home">
            <x-application-logo />
            <span><strong>AI-PGAALS</strong><small>TEACHER WORKSPACE</small></span>
        </a>

        <nav class="teacher-primary-nav" aria-label="Main pages">
            @foreach ($teacherLinks as $link)
                <a href="{{ $link['url'] }}" @if ($link['active']) aria-current="page" @endif>
                    <span class="material-symbols-outlined" aria-hidden="true">{{ $link['icon'] }}</span>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
            <details class="teacher-nav-more">
                <summary><span>More</span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary>
                <div class="teacher-nav-more-menu">
                    <a href="{{ route('assessments.results') }}"><span class="material-symbols-outlined" aria-hidden="true">monitoring</span>Performance</a>
                    <a href="{{ route('reports.index') }}"><span class="material-symbols-outlined" aria-hidden="true">bar_chart</span>Reports</a>
                    <a href="{{ route('assessments.create') }}"><span class="material-symbols-outlined" aria-hidden="true">add_circle</span>Create assessment</a>
                    <a href="{{ route('teacher.ai-assistant.index') }}"><span class="material-symbols-outlined" aria-hidden="true">psychology</span>AI Assistant</a>
                    <a href="{{ route('teacher.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span>Practice missions</a>
                    <a href="{{ route('students.create') }}"><span class="material-symbols-outlined" aria-hidden="true">person_add</span>Add student</a>
                    <a href="{{ route('worksheets.reviews') }}"><span class="material-symbols-outlined" aria-hidden="true">rate_review</span>Worksheet reviews</a>
                    <a href="{{ route('profile.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">settings</span>Settings</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Log out</button></form>
                </div>
            </details>
        </nav>

        <div class="teacher-global-actions">
            <span class="teacher-section-chip"><span class="material-symbols-outlined" aria-hidden="true">school</span>{{ Auth::user()->section ?: 'Grade 6' }}</span>
            <x-comfort-controls panel-id="teacher-comfort-panel-current" />
            <x-notification-menu :user="Auth::user()" />
            <a class="teacher-global-profile" href="{{ route('profile.edit') }}" aria-label="Teacher profile: {{ $teacherName }}">
                <x-teacher-avatar :teacher="Auth::user()" size="sm" />
                <span><strong>{{ $teacherName }}</strong><small>Teacher</small></span>
            </a>
        </div>

        <details class="teacher-mobile-nav">
            <summary aria-label="Open teacher navigation"><span class="material-symbols-outlined" aria-hidden="true">menu</span></summary>
            <nav aria-label="Mobile teacher pages">
                @foreach ($teacherLinks as $link)
                    <a href="{{ $link['url'] }}" @if ($link['active']) aria-current="page" @endif><span class="material-symbols-outlined" aria-hidden="true">{{ $link['icon'] }}</span>{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('teacher.ai-assistant.index') }}"><span class="material-symbols-outlined" aria-hidden="true">psychology</span>AI Assistant</a>
                <a href="{{ route('teacher.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">flag</span>Practice missions</a>
                <a href="{{ route('students.create') }}"><span class="material-symbols-outlined" aria-hidden="true">person_add</span>Add student</a>
                <a href="{{ route('profile.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">settings</span>Settings</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Log out</button></form>
            </nav>
        </details>
    </div>
</header>

<aside class="campus-sidebar teacher-classic-sidebar fixed inset-y-0 left-0 z-40 hidden w-72 flex-col bg-slate-50 py-8 lg:flex" aria-label="Teacher navigation">
    <div class="campus-sidebar-brand">
        <a class="teacher-brand" href="{{ route('teacher.dashboard') }}"><x-application-logo /><span><strong>AI-PGAALS</strong><small>Teacher Workspace</small></span></a>
    </div>
    <div class="campus-sidebar-identity">
        <div class="flex items-center gap-3">
            <div class="relative"><x-teacher-avatar :teacher="Auth::user()" /><div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-slate-50 bg-secondary"></div></div>
            <div class="min-w-0"><p class="truncate font-headline text-sm font-bold text-on-surface" title="{{ $teacherName }}">{{ $teacherName }}</p><p class="text-[10px] font-medium uppercase tracking-widest text-on-surface-variant">Teacher</p></div>
        </div>
    </div>
    <nav class="campus-sidebar-links flex-1" aria-label="Teacher pages"><x-role-navigation role="teacher" /></nav>
    <div class="teacher-section-label"><span class="material-symbols-outlined" aria-hidden="true">school</span>Grade 6{{ Auth::user()->section ? ' / '.Auth::user()->section : '' }}</div>
    <div class="campus-sidebar-footer mt-auto space-y-1 px-2"><form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 px-4 py-3 text-left font-headline text-sm uppercase tracking-widest text-slate-500 transition-all hover:bg-slate-200" type="submit"><span class="material-symbols-outlined">logout</span><span>Logout</span></button></form></div>
</aside>

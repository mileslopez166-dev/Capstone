@props([
    'active' => 'home',
])

@php
    $student = Auth::user();
    $studentName = $student?->name ?? 'Student';
    $studentInitials = collect(explode(' ', $studentName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $links = [
        ['key' => 'home', 'label' => 'Dashboard', 'short' => 'Home', 'href' => route('student.dashboard'), 'icon' => 'dashboard'],
        ['key' => 'activities', 'label' => 'Activities', 'short' => 'Activities', 'href' => route('student.activities'), 'icon' => 'assignment'],
        ['key' => 'rewards', 'label' => 'Rewards', 'short' => 'Rewards', 'href' => route('student.rewards'), 'icon' => 'backpack'],
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col bg-slate-50 py-8 lg:flex">
    <div class="mb-12 px-6">
        <h1 class="font-headline text-xl font-black uppercase tracking-tighter text-blue-700">AI-PGAALS</h1>
    </div>

    <div class="mb-8 px-6">
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container font-headline text-sm font-bold text-on-primary-container">
                    {{ $studentInitials }}
                </div>
                <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-slate-50 bg-emerald-500"></div>
            </div>
            <div class="min-w-0">
                <p class="truncate font-headline text-sm font-bold text-on-surface">{{ $studentName }}</p>
                <p class="text-[10px] font-medium uppercase tracking-widest text-on-surface-variant">Student Account</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 space-y-1 px-2">
        @foreach ($links as $link)
            <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === $link['key'] ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ $link['href'] }}">
                <span class="material-symbols-outlined">{{ $link['icon'] }}</span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-auto space-y-1 px-2">
        <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === 'profile' ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ route('profile.edit') }}">
            <span class="material-symbols-outlined">settings</span>
            <span>Profile</span>
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

<header class="sticky top-0 z-30 flex items-center justify-between bg-white/80 px-5 py-5 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl sm:px-8 sm:py-6 lg:ml-72">
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <a class="font-headline text-xl font-black uppercase tracking-tighter text-blue-700 lg:hidden" href="{{ route('student.dashboard') }}">AI-PGAALS</a>
        <div class="hidden items-center rounded-full border border-outline-variant/10 bg-surface-container-low px-4 py-2 sm:flex">
            <span class="material-symbols-outlined mr-2 text-sm text-outline">search</span>
            <input class="w-64 border-none bg-transparent text-sm placeholder:text-outline/60 focus:ring-0" placeholder="Search activities..." type="text">
        </div>
    </div>

    <div class="flex items-center gap-4 sm:gap-6">
        <button class="relative text-slate-500 transition-colors hover:text-blue-600" type="button">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-error"></span>
        </button>
        <a class="flex items-center gap-3 rounded-full border border-outline-variant/10 bg-surface-container-low py-1.5 pl-2 pr-4" href="{{ route('profile.edit') }}">
            <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-primary font-headline text-xs font-bold text-white">
                {{ $studentInitials }}
            </div>
            <span class="hidden max-w-[10rem] truncate text-sm font-bold text-on-surface sm:inline">{{ $studentName }}</span>
        </a>
    </div>
</header>

<nav class="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around rounded-t-[2rem] bg-white/85 px-4 pb-6 pt-4 shadow-2xl backdrop-blur-2xl lg:hidden">
    @foreach ([...$links, ['key' => 'profile', 'label' => 'Profile', 'short' => 'Profile', 'href' => route('profile.edit'), 'icon' => 'account_circle']] as $link)
        @if ($active === $link['key'])
            <div class="flex scale-110 flex-col items-center justify-center rounded-[1.7rem] bg-blue-100 px-5 py-2 text-blue-700 shadow-inner">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">{{ $link['icon'] }}</span>
                <span class="text-[10px] font-bold">{{ $link['short'] }}</span>
            </div>
        @else
            <a class="flex flex-col items-center justify-center px-3 py-2 text-slate-400 transition-transform hover:scale-105 hover:text-blue-600" href="{{ $link['href'] }}">
                <span class="material-symbols-outlined">{{ $link['icon'] }}</span>
                <span class="text-[10px] font-bold">{{ $link['short'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
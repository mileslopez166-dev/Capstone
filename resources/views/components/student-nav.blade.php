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
    $rankedStudentIds = $student
        ? \App\Models\AssessmentSubmission::query()
            ->selectRaw('user_id, SUM(points) as total_points, COUNT(*) as completed_count')
            ->whereIn('user_id', \App\Models\User::query()
                ->select('id')
                ->where('role', 'student')
                ->where('approval_status', 'approved'))
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->orderByDesc('completed_count')
            ->pluck('user_id')
            ->values()
        : collect();
    $studentRankIndex = $student ? $rankedStudentIds->search($student->id) : false;
    $studentRank = $studentRankIndex === false ? null : $studentRankIndex + 1;
    $studentTier = match ($studentRank) {
        1 => ['label' => 'Flaming', 'class' => 'student-mini-tier-flaming bg-gradient-to-br from-red-600 via-orange-500 to-yellow-300 text-white ring-orange-200 shadow-[0_0_24px_rgba(249,115,22,0.7)]'],
        2 => ['label' => 'Diamond', 'class' => 'student-mini-tier-diamond text-white ring-cyan-100 shadow-[0_0_20px_rgba(34,211,238,0.55)]'],
        3 => ['label' => 'Platinum', 'class' => 'student-mini-tier-platinum text-white ring-slate-100 shadow-[0_0_18px_rgba(203,213,225,0.5)]'],
        4 => ['label' => 'Gold', 'class' => 'student-mini-tier-gold text-amber-950 ring-yellow-100 shadow-[0_0_18px_rgba(234,179,8,0.5)]'],
        5 => ['label' => 'Silver', 'class' => 'student-mini-tier-silver text-zinc-900 ring-zinc-100 shadow-[0_0_16px_rgba(212,212,216,0.48)]'],
        default => ['label' => 'Bronze', 'class' => 'student-mini-tier-bronze text-white ring-orange-100 shadow-[0_0_16px_rgba(251,146,60,0.48)]'],
    };
    $studentXp = max(0, (int) ($student?->assessmentSubmissions()->sum('points') ?? 0));
    $xpPerLevel = 500;
    $studentLevel = intdiv($studentXp, $xpPerLevel) + 1;
    $levelXp = $studentXp % $xpPerLevel;
    $links = [
        ['key' => 'home', 'label' => 'Dashboard', 'short' => 'Home', 'href' => route('student.dashboard'), 'icon' => 'dashboard'],
        ['key' => 'activities', 'label' => 'Activities', 'short' => 'Activities', 'href' => route('student.activities'), 'icon' => 'assignment'],
        ['key' => 'leaderboard', 'label' => 'Leaderboard', 'short' => 'Rank', 'href' => route('student.leaderboard'), 'icon' => 'leaderboard'],
        ['key' => 'rewards', 'label' => 'Rewards', 'short' => 'Rewards', 'href' => route('student.rewards'), 'icon' => 'backpack'],
    ];
@endphp

@once
    <style>
        @keyframes studentMiniFlame {
            0%, 100% { box-shadow: 0 0 14px rgba(220, 38, 38, 0.36), 0 0 28px rgba(249, 115, 22, 0.42), 0 0 42px rgba(253, 224, 71, 0.2); filter: saturate(1.04); }
            45% { box-shadow: 0 0 22px rgba(220, 38, 38, 0.58), 0 0 40px rgba(249, 115, 22, 0.55), 0 0 58px rgba(253, 224, 71, 0.26); filter: saturate(1.18); }
            70% { box-shadow: 0 0 18px rgba(185, 28, 28, 0.48), 0 0 34px rgba(245, 158, 11, 0.46), 0 0 48px rgba(253, 224, 71, 0.22); }
        }

        @keyframes studentMiniFlameTongue {
            0%, 100% { opacity: 0.72; transform: translate(-50%, 9%) scaleX(0.92) scaleY(0.9) rotate(-7deg); }
            35% { opacity: 0.96; transform: translate(-50%, -7%) scaleX(1.08) scaleY(1.2) rotate(5deg); }
            68% { opacity: 0.82; transform: translate(-50%, 1%) scaleX(0.98) scaleY(1.05) rotate(-3deg); }
        }

        @keyframes studentMiniFlameCore {
            0%, 100% { opacity: 0.78; transform: translate(-50%, 18%) scale(0.78) rotate(8deg); }
            45% { opacity: 1; transform: translate(-50%, 2%) scale(0.92) rotate(-4deg); }
        }

        .student-mini-tier-flaming {
            animation: studentMiniFlame 1.65s ease-in-out infinite;
            isolation: isolate;
            position: relative;
        }

        .student-mini-tier-flaming::before,
        .student-mini-tier-flaming::after {
            border-radius: 55% 45% 58% 42% / 62% 55% 45% 38%;
            content: "";
            left: 50%;
            pointer-events: none;
            position: absolute;
            top: -42%;
            z-index: -1;
        }

        .student-mini-tier-flaming::before {
            animation: studentMiniFlameTongue 1.15s ease-in-out infinite;
            background: radial-gradient(circle at 50% 82%, rgba(255,255,255,0.42), transparent 18%), radial-gradient(circle at 50% 70%, rgba(253,224,71,0.92), transparent 32%), radial-gradient(circle at 50% 48%, rgba(249,115,22,0.92), transparent 58%), radial-gradient(circle at 50% 18%, rgba(220,38,38,0.78), transparent 72%);
            filter: blur(0.2px);
            height: 85%;
            width: 74%;
        }

        .student-mini-tier-flaming::after {
            animation: studentMiniFlameCore 0.92s ease-in-out infinite reverse;
            background: radial-gradient(circle at 50% 82%, rgba(255,255,255,0.8), transparent 18%), radial-gradient(circle at 50% 55%, rgba(253,224,71,0.95), transparent 42%), radial-gradient(circle at 50% 18%, rgba(249,115,22,0.5), transparent 70%);
            height: 60%;
            width: 46%;
        }
    </style>
@endonce

<aside class="campus-sidebar fixed inset-y-0 left-0 z-40 hidden w-72 flex-col bg-slate-50 py-8 lg:flex" aria-label="Student navigation">
    <div class="campus-sidebar-brand">
        <x-campus-brand :href="route('student.dashboard')" />
    </div>

    <div class="campus-sidebar-identity">
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="flex h-10 w-10 items-center justify-center rounded-full font-headline text-sm font-bold ring-4 {{ $studentTier['class'] }}" title="{{ $studentTier['label'] }} tier{{ $studentRank ? ' - rank #'.$studentRank : '' }}">
                    <x-student-character :gender="$student?->gender" variant="portrait" />
                </div>
                <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-slate-50 bg-emerald-500"></div>
            </div>
            <div class="min-w-0">
                <p class="truncate font-headline text-sm font-bold text-on-surface">{{ $studentName }}</p>
                <p class="text-[10px] font-medium uppercase tracking-widest text-on-surface-variant">Student Account</p>
            </div>
        </div>
    </div>

    <nav class="campus-sidebar-links space-y-1 px-2">
        @foreach ($links as $link)
            <a class="flex items-center gap-3 px-4 py-3 font-headline text-sm uppercase tracking-widest transition-all {{ $active === $link['key'] ? 'border-r-4 border-blue-600 font-bold text-blue-700 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-200' }}" href="{{ $link['href'] }}" @if ($active === $link['key']) aria-current="page" @endif>
                <span class="material-symbols-outlined">{{ $link['icon'] }}</span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <x-student-companion :gender="$student?->gender" :page="$active" />

    <div class="campus-sidebar-footer mt-auto space-y-1 px-2">
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

<header class="campus-topbar campus-student-topbar sticky top-0 z-30 flex items-center justify-between bg-white/80 px-5 py-5 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl sm:px-8 sm:py-6 lg:ml-72">
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <a class="hidden font-headline text-xl font-black uppercase tracking-tighter text-blue-700 sm:inline lg:hidden" href="{{ route('student.dashboard') }}">AI-PGAALS</a>
        <form class="campus-search flex min-w-0 items-center rounded-full border border-outline-variant/10 bg-surface-container-low px-3 py-2 sm:px-4" method="GET" action="{{ route('student.search') }}">
            <button class="mr-2 inline-flex text-outline transition-colors hover:text-primary" type="submit" aria-label="Search student activities">
                <span class="material-symbols-outlined text-sm">search</span>
            </button>
            <input class="w-28 min-w-0 border-none bg-transparent text-sm placeholder:text-outline/60 focus:ring-0 sm:w-64" name="q" value="{{ request('q') }}" placeholder="Search activities..." type="search" aria-label="Search activities">
        </form>
    </div>

    <div class="campus-topbar-actions flex items-center gap-4 sm:gap-6">
        <x-notification-menu :user="$student" />
        <a class="campus-account-link flex items-center gap-3 rounded-full border border-outline-variant/10 bg-surface-container-low py-1.5 pl-2 pr-4" href="{{ route('profile.edit') }}" aria-label="Student profile">
            <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white font-headline text-xs font-bold ring-2 {{ $studentTier['class'] }}" title="{{ $studentTier['label'] }} tier{{ $studentRank ? ' - rank #'.$studentRank : '' }}">
                <x-student-character :gender="$student?->gender" variant="portrait" />
            </div>
            <span class="hidden max-w-[10rem] truncate text-sm font-bold text-on-surface sm:inline">{{ $studentName }}</span>
        </a>
    </div>
    <a class="campus-xp" href="{{ route('student.rewards') }}" aria-label="Level {{ $studentLevel }}, {{ number_format($studentXp) }} total XP">
        <span class="material-symbols-outlined campus-xp-star" aria-hidden="true">stars</span>
        <strong>Lv. {{ $studentLevel }}</strong>
        <span class="campus-xp-track" role="progressbar" aria-label="Progress to next level" aria-valuemin="0" aria-valuemax="{{ $xpPerLevel }}" aria-valuenow="{{ $levelXp }}"><span style="width: {{ $levelXp / $xpPerLevel * 100 }}%"></span></span>
        <span class="campus-xp-value">{{ $levelXp }} / {{ $xpPerLevel }} XP</span>
    </a>
</header>

<x-student-companion :gender="$student?->gender" :page="$active" placement="mobile" />

<nav class="campus-bottom-nav fixed bottom-0 left-0 z-50 flex w-full items-center justify-around rounded-t-[2rem] bg-white/85 px-4 pb-6 pt-4 shadow-2xl backdrop-blur-2xl lg:hidden" aria-label="Student navigation">
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

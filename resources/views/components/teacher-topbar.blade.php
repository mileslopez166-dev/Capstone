@props([
    'teacherName',
    'teacherInitials',
    'searchPlaceholder' => 'Search...',
])

<header class="campus-topbar sticky top-0 z-20 flex items-center justify-between bg-white/80 px-5 py-5 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl sm:px-8 sm:py-6">
    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
        <details class="campus-mobile-menu lg:hidden">
            <summary aria-label="Teacher navigation" title="Teacher navigation"><span class="material-symbols-outlined" aria-hidden="true">menu</span></summary>
            <nav aria-label="Teacher navigation">
                @foreach ([
                    ['route' => 'teacher.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
                    ['route' => 'students.index', 'label' => 'Students', 'icon' => 'group'],
                    ['route' => 'reports.index', 'label' => 'Reports', 'icon' => 'assessment'],
                    ['route' => 'assessments.index', 'label' => 'Assessments', 'icon' => 'note_add'],
                    ['route' => 'teacher.practice.index', 'label' => 'Practice', 'icon' => 'flag'],
                    ['route' => 'profile.edit', 'label' => 'Settings', 'icon' => 'settings'],
                ] as $link)
                    <a href="{{ route($link['route']) }}" @if (request()->routeIs($link['route'])) aria-current="page" @endif><span class="material-symbols-outlined" aria-hidden="true">{{ $link['icon'] }}</span>{{ $link['label'] }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Logout</button>
                </form>
            </nav>
        </details>
        <form class="campus-search flex min-w-0 items-center rounded-full border border-outline-variant/10 bg-surface-container-low px-3 py-2 sm:px-4" method="GET" action="{{ route('teacher.search') }}">
            <button class="mr-2 inline-flex text-outline transition-colors hover:text-primary" type="submit" aria-label="Search teacher workspace">
                <span class="material-symbols-outlined text-sm">search</span>
            </button>
            <input class="w-28 min-w-0 border-none bg-transparent text-sm placeholder:text-outline/60 focus:ring-0 sm:w-64" name="q" value="{{ request('q') }}" placeholder="{{ $searchPlaceholder }}" type="search" aria-label="Search teacher workspace">
        </form>
    </div>

    <div class="campus-topbar-actions flex items-center gap-4 sm:gap-6">
        <x-comfort-controls />
        <x-notification-menu :user="Auth::user()" />
        <a class="campus-account-link flex items-center gap-3 rounded-full border border-outline-variant/10 bg-surface-container-low py-1.5 pl-2 pr-4" href="{{ route('profile.edit') }}" aria-label="Teacher profile">
            <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-primary font-headline text-xs font-bold text-white">
                {{ $teacherInitials }}
            </div>
            <span class="hidden max-w-[10rem] truncate text-sm font-bold text-on-surface sm:inline">{{ $teacherName }}</span>
        </a>
    </div>
</header>

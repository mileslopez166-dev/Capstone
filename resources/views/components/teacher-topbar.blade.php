@props([
    'teacherName',
    'teacherInitials',
    'searchPlaceholder' => 'Search...',
])

<header class="campus-topbar sticky top-0 z-20 flex items-center justify-between bg-white/80 px-5 py-5 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl sm:px-8 sm:py-6">
    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
        <x-role-mobile-menu role="teacher" />
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
            <x-teacher-avatar :teacher="Auth::user()" size="sm" />
            <span class="hidden max-w-[10rem] truncate text-sm font-bold text-on-surface sm:inline">{{ $teacherName }}</span>
        </a>
    </div>
</header>

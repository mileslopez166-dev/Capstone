@props([
    'teacherName',
    'teacherInitials',
    'searchPlaceholder' => 'Search...',
])

<header class="sticky top-0 z-20 flex items-center justify-between bg-white/80 px-5 py-5 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl sm:px-8 sm:py-6">
    <div class="flex items-center gap-3 sm:gap-4">
        {{ $mobileTrigger ?? '' }}
        <div class="hidden items-center rounded-full border border-outline-variant/10 bg-surface-container-low px-4 py-2 sm:flex">
            <span class="material-symbols-outlined mr-2 text-sm text-outline">search</span>
            <input class="w-64 border-none bg-transparent text-sm placeholder:text-outline/60 focus:ring-0" placeholder="{{ $searchPlaceholder }}" type="text">
        </div>
    </div>

    <div class="flex items-center gap-4 sm:gap-6">
        <button class="relative text-slate-500 transition-colors hover:text-blue-600" type="button">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-error"></span>
        </button>
        <a class="flex items-center gap-3 rounded-full border border-outline-variant/10 bg-surface-container-low py-1.5 pl-2 pr-4" href="{{ route('profile.edit') }}">
            <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-primary font-headline text-xs font-bold text-white">
                {{ $teacherInitials }}
            </div>
            <span class="hidden text-sm font-bold text-on-surface sm:inline">{{ $teacherName }}</span>
        </a>
    </div>
</header>

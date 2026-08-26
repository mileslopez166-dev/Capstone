@props([
    'active' => 'home',
])

@php
    $links = [
        ['key' => 'home', 'label' => 'Home', 'href' => route('student.dashboard')],
        ['key' => 'activities', 'label' => 'Activities', 'href' => route('student.activities')],
        ['key' => 'rewards', 'label' => 'Rewards', 'href' => route('student.rewards')],
        ['key' => 'profile', 'label' => 'Profile', 'href' => route('profile.edit')],
    ];
@endphp

<nav class="sticky top-0 z-50 bg-white/80 shadow-[0_20px_40px_rgba(0,94,159,0.06)] backdrop-blur-xl">
    <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
        <a class="text-2xl font-extrabold italic text-blue-600" href="{{ route('student.dashboard') }}">AI-PGAALS</a>

        <div class="hidden items-center gap-8 md:flex">
            @foreach ($links as $link)
                <a
                    class="{{ $active === $link['key'] ? 'border-b-4 border-blue-500 font-bold text-blue-700' : 'font-medium text-slate-500 hover:text-blue-500' }} font-headline transition-colors"
                    href="{{ $link['href'] }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-4">
            <button class="text-on-surface-variant transition-colors hover:text-primary" type="button">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <a class="text-on-surface-variant transition-colors hover:text-primary" href="{{ route('profile.edit') }}">
                <span class="material-symbols-outlined">account_circle</span>
            </a>
        </div>
    </div>
</nav>

<nav class="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around rounded-t-[2rem] bg-white/85 px-4 pb-6 pt-4 shadow-2xl backdrop-blur-2xl md:hidden">
    @foreach ($links as $link)
        @php
            $icon = match ($link['key']) {
                'home' => 'home',
                'activities' => 'assignment',
                'rewards' => 'backpack',
                default => 'account_circle',
            };
        @endphp

        @if ($active === $link['key'])
            <div class="flex scale-110 flex-col items-center justify-center rounded-[1.7rem] bg-blue-100 px-5 py-2 text-blue-700 shadow-inner">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">{{ $icon }}</span>
                <span class="text-[10px] font-bold">{{ $link['label'] }}</span>
            </div>
        @else
            <a class="flex flex-col items-center justify-center px-3 py-2 text-slate-400 transition-transform hover:scale-105 hover:text-blue-600" href="{{ $link['href'] }}">
                <span class="material-symbols-outlined">{{ $icon }}</span>
                <span class="text-[10px] font-bold">{{ $link['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>

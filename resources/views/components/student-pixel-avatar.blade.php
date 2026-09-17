@props([
    'gender' => null,
    'name' => null,
    'size' => 'md',
    'showCard' => false,
    'isOnline' => false,
    'rankTier' => 'Bronze',
    'rank' => null,
    'user' => null,
])

@php
    $gender = $user?->gender ?? $gender;
    $isGirl = $user?->avatar_config
        ? \App\Support\AvatarWardrobe::resolve($user->avatar_config, $gender)['character'] === 'lyra'
        : in_array(strtolower((string) $gender), ['female', 'girl'], true);
    $characterName = $isGirl ? 'Lyra Vale' : 'Nova Finch';
    $tierLabel = is_array($rankTier) ? ($rankTier['label'] ?? 'Bronze') : ($rankTier ?? 'Bronze');
    $profileTierLabel = $tierLabel === 'Flaming' ? 'Top Rank' : $tierLabel;
    $tier = match ($tierLabel) {
        'Flaming' => ['icon' => 'workspace_premium', 'ring' => 'ring-sky-300', 'badge' => 'bg-sky-100 text-sky-900 border-sky-300'],
        'Diamond' => ['icon' => 'diamond', 'ring' => 'ring-cyan-300', 'badge' => 'bg-cyan-100 text-cyan-900 border-cyan-300'],
        'Platinum' => ['icon' => 'workspace_premium', 'ring' => 'ring-slate-300', 'badge' => 'bg-slate-100 text-slate-800 border-slate-300'],
        'Gold' => ['icon' => 'military_tech', 'ring' => 'ring-yellow-300', 'badge' => 'bg-yellow-100 text-yellow-900 border-yellow-300'],
        'Silver' => ['icon' => 'shield', 'ring' => 'ring-zinc-300', 'badge' => 'bg-zinc-100 text-zinc-800 border-zinc-300'],
        default => ['icon' => 'editor_choice', 'ring' => 'ring-orange-300', 'badge' => 'bg-orange-100 text-orange-900 border-orange-300'],
    };
    $sizeClasses = match ($size) {
        'sm' => 'h-16 w-16',
        'lg' => 'h-36 w-36 sm:h-44 sm:w-44',
        default => 'h-24 w-24',
    };
@endphp

<div {{ $attributes->class(['campus-avatar-card' => $showCard]) }}>
    @if ($showCard)
        <div class="campus-avatar-card-header">
            <div>
                <p>Your Avatar</p>
                <h3 class="font-headline">{{ $characterName }}</h3>
            </div>
            <div class="campus-avatar-badges">
                <span class="campus-avatar-status"><span class="{{ $isOnline ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>{{ $isOnline ? 'Online' : 'Offline' }}</span>
                <span class="campus-avatar-tier {{ $tier['badge'] }}">
                    <span class="material-symbols-outlined" aria-hidden="true">{{ $tier['icon'] }}</span>{{ $profileTierLabel }}
                </span>
            </div>
        </div>
    @endif

    <div class="campus-avatar-stage relative mx-auto ring-4 {{ $tier['ring'] }} {{ $showCard ? 'campus-avatar-stage-full' : $sizeClasses }}" aria-label="{{ $profileTierLabel }} avatar rank">
        <x-student-character :user="$user" :gender="$gender" :variant="$showCard ? 'full' : 'portrait'" />
    </div>

    @if ($showCard)
        <div class="campus-avatar-card-footer">
            <div class="min-w-0">
                <p>STUDENT</p>
                <span>{{ $name ?: 'Student' }}</span>
            </div>
            <div class="shrink-0 text-right">
                <p>RANK</p>
                <span>{{ $rank ? '#'.$rank : 'New' }}</span>
            </div>
        </div>
    @endif
</div>

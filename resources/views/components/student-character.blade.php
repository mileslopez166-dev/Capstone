@props(['gender' => null, 'variant' => 'full', 'user' => null])

@php
    $gender = $user?->gender ?? $gender;
    $appearance = $user?->avatar_config;
    $look = \App\Support\AvatarWardrobe::resolve($appearance, $gender);
    $isGirl = $look['character'] === 'lyra';
    $characterName = $isGirl ? 'Lyra Vale' : 'Nova Finch';
@endphp

<span {{ $attributes->class(['campus-character', 'campus-character-custom', 'campus-character-portrait' => $variant === 'portrait']) }}>
    <x-wardrobe-character :appearance="$look" :crop="$variant === 'portrait' ? 'portrait' : 'full'" :aria-label="$characterName.' avatar'" />
</span>

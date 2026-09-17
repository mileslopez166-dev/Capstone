@props(['gender' => null, 'variant' => 'full', 'user' => null])

@php
    $gender = $user?->gender ?? $gender;
    $appearance = $user?->avatar_config;
    $isGirl = $appearance
        ? \App\Support\AvatarWardrobe::resolve($appearance, $gender)['character'] === 'lyra'
        : in_array(strtolower((string) $gender), ['female', 'girl'], true);
    $character = $isGirl ? 'student-girl.png' : 'student-boy.png';
    $characterName = $isGirl ? 'Lyra Vale' : 'Nova Finch';
@endphp

<span {{ $attributes->class(['campus-character', 'campus-character-portrait' => $variant === 'portrait', 'campus-character-custom' => (bool) $appearance]) }}>
    @if ($appearance)
        <x-wardrobe-character :appearance="$appearance" :crop="$variant === 'portrait' ? 'portrait' : 'full'" />
    @else
        <img src="{{ asset('images/campus/'.$character) }}" alt="{{ $characterName }} avatar" width="1024" height="1536" draggable="false" decoding="async">
    @endif
</span>

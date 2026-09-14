@props(['gender' => null, 'variant' => 'full'])

@php
    $isGirl = in_array(strtolower((string) $gender), ['female', 'girl'], true);
    $character = $isGirl ? 'student-girl.png' : 'student-boy.png';
    $characterName = $isGirl ? 'Lyra Vale' : 'Nova Finch';
@endphp

<span {{ $attributes->class(['campus-character', 'campus-character-portrait' => $variant === 'portrait']) }}>
    <img src="{{ asset('images/campus/'.$character) }}" alt="{{ $characterName }} avatar" width="1024" height="1536" draggable="false" decoding="async">
</span>

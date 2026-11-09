@props(['tone' => 'neutral'])
<span {{ $attributes->class(['teacher-badge', 'teacher-badge-'.$tone]) }}>{{ $slot }}</span>

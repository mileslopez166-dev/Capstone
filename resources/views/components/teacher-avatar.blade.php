@props(['teacher' => null, 'size' => 'md'])
@php
    $name = $teacher?->isTeacher() ? $teacher->name : 'Teacher';
    $photo = $teacher?->isTeacher() && $teacher->isApproved() && filled($teacher->teacher_photo_path);
    $initials = str($name)->explode(' ')->filter()->take(2)->map(fn ($part) => str($part)->substr(0, 1)->upper())->implode('');
@endphp
<span {{ $attributes->class(['teacher-avatar', 'teacher-avatar-'.$size]) }}>
    @if ($photo)
        <img src="{{ route('teacher.photo.show', $teacher) }}" alt="{{ $name }} profile photo" width="128" height="128" loading="lazy" decoding="async">
    @else
        <span role="img" aria-label="{{ $name }} initials">{{ $initials }}</span>
    @endif
</span>

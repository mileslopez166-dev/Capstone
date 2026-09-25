@props(['teacher' => null])
<div class="teacher-identity">
    <x-teacher-avatar :teacher="$teacher" size="sm" />
    <span class="teacher-identity-copy"><small>Teacher</small><strong>{{ $teacher?->isTeacher() ? $teacher->name : 'Your teacher' }}</strong></span>
</div>

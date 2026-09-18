@props(['student', 'assessment'])
<aside class="assisted-session" aria-label="Current student">
    <span class="material-symbols-outlined" aria-hidden="true">supervised_user_circle</span>
    <div><p>Teacher-Assisted Assessment</p><strong>{{ $student->name }}</strong><span>{{ $student->section ?: 'No section' }} &middot; {{ $student->email }}</span></div>
    <a href="{{ route('assessments.show', $assessment) }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>Back to Assessment</a>
</aside>

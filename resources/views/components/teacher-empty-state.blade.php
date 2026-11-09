@props(['icon' => 'inbox', 'title', 'description'])

<div {{ $attributes->class(['teacher-empty-state']) }} role="status">
    <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
    <div><strong>{{ $title }}</strong><p>{{ $description }}</p></div>
</div>

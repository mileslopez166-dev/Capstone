@php
    $classes = 'error-action error-action-'.($variant ?? 'secondary');
    $icon = $action['icon'] ?? 'arrow_forward';
@endphp

@if (($action['action'] ?? null) === 'back' || ($action['action'] ?? null) === 'reload')
    <button
        type="button"
        class="{{ $classes }}"
        data-error-action="{{ $action['action'] }}"
        @if (($action['action'] ?? null) === 'back') data-fallback="{{ $action['fallback'] ?? url('/') }}" @endif
    >
        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $action['label'] }}</span>
    </button>
@else
    <a class="{{ $classes }}" href="{{ $action['href'] ?? url('/') }}">
        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $action['label'] }}</span>
    </a>
@endif

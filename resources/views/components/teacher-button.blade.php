@props(['href' => null, 'variant' => 'primary', 'icon' => null, 'type' => 'button'])

@if ($href)
    <a {{ $attributes->class(['teacher-button', 'teacher-button-'.$variant]) }} href="{{ $href }}">
        @if ($icon)<span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>@endif{{ $slot }}
    </a>
@else
    <button {{ $attributes->class(['teacher-button', 'teacher-button-'.$variant])->merge(['type' => $type]) }}>
        @if ($icon)<span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>@endif{{ $slot }}
    </button>
@endif

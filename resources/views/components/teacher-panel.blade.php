@props(['title' => null, 'eyebrow' => null, 'icon' => null])

<section {{ $attributes->class(['teacher-panel']) }}>
    @if (isset($header))
        {{ $header }}
    @elseif ($title)
        <header class="teacher-panel-header">
            <div>@if ($eyebrow)<p class="teacher-dashboard-kicker">{{ $eyebrow }}</p>@endif<h2>{{ $title }}</h2></div>
            @if ($icon)<span class="teacher-bento-icon material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>@endif
        </header>
    @endif
    {{ $slot }}
</section>

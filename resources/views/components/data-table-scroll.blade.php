@props(['label'])
<div {{ $attributes->class(['ui-data-table-scroll', 'overflow-x-auto']) }} role="region" aria-label="{{ $label }}" tabindex="0">
    {{ $slot }}
</div>

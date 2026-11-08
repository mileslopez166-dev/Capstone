@props(['page', 'showInstructions' => true])
<section class="numeracy-guide-content" aria-labelledby="numeracy-guide-title-{{ $page['part'] }}">
    <p class="numeracy-guide-part">Part {{ $page['part'] }} guide</p>
    <h3 id="numeracy-guide-title-{{ $page['part'] }}">{{ $page['reading']['title'] }}</h3>
    @foreach ($page['reading']['sections'] as $section)
        @if (isset($section['heading']) || ! empty($section['paragraphs']) || ($showInstructions && isset($section['instruction'])))
            <div class="numeracy-guide-section">
                @if (isset($section['heading']))<h4>{{ $section['heading'] }}</h4>@endif
                @foreach ($section['paragraphs'] ?? [] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                @if ($showInstructions && isset($section['instruction']))<p class="numeracy-guide-instruction">{{ $section['instruction'] }}</p>@endif
            </div>
        @endif
    @endforeach
</section>

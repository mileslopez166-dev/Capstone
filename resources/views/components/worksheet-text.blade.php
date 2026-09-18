@props(['worksheet', 'page', 'pageIndex' => 0, 'readonly' => true])
<article class="worksheet-text-page" data-book-reading-page="{{ $pageIndex }}" aria-label="Worksheet {{ $worksheet['number'] }}, Part {{ $page['part'] }}" tabindex="0">
    <h3>{{ $page['reading']['title'] }}</h3>
    @foreach ($page['reading']['sections'] as $sectionIndex => $section)
        <section class="worksheet-text-section">
            @if (isset($section['heading']))<h4>{{ $section['heading'] }}</h4>@endif
            @foreach ($section['paragraphs'] ?? [] as $paragraph)<p>{{ str_replace([' x ', ' / '], [' × ', ' ÷ '], $paragraph) }}</p>@endforeach
            @if (isset($section['instruction']))<p class="worksheet-text-instruction">{{ ($page['responses'][$sectionIndex.'-0']['fields'][0]['type'] ?? null) === 'checkbox' ? 'Check the box for every number by which the given number is divisible.' : $section['instruction'] }}</p>@endif
            @if (!empty($section['items']))
                <ol class="worksheet-problems">
                    @foreach ($section['items'] as $itemIndex => $item)
                        @php
                            $item = is_array($item) ? $item : ['text' => $item];
                            $label = $item['label'] ?? (($section['start'] ?? 1) + $itemIndex);
                            $gridKey = $sectionIndex.'-'.$itemIndex;
                        @endphp
                        <li>
                            <span class="worksheet-problem-number">{{ $label }}.</span>
                            <div class="worksheet-problem-content"><p>{{ str_replace([' x ', ' / '], [' × ', ' ÷ '], $item['text']) }}</p>
                                @if (isset($item['shape']))
                                    @php
                                        if (is_int($item['shape'])) {
                                            $count = $item['shape'];
                                            $angle = in_array($count, [4, 8, 12]) ? -M_PI / 2 + M_PI / $count : -M_PI / 2;
                                            $points = array_map(fn ($i) => [50 + 44 * cos($angle + 2 * M_PI * $i / $count), 50 + 44 * sin($angle + 2 * M_PI * $i / $count)], range(0, $count - 1));
                                        } else $points = $item['shape'];
                                    @endphp
                                    <svg class="worksheet-shape" viewBox="0 0 100 100" role="img" aria-label="Figure for item {{ $label }}"><polygon points="{{ collect($points)->map(fn ($point) => implode(',', $point))->join(' ') }}" fill="#dae7ed" stroke="#26394a" stroke-width="1.5" stroke-linejoin="round" /></svg>
                                @endif
                                @if (isset($item['clock']))
                                    <svg class="worksheet-clock" viewBox="0 0 200 200" role="img" aria-label="Analog clock for item {{ $label }}, {{ $item['text'] }}">
                                        <circle cx="100" cy="100" r="95" fill="white" stroke="#345666" stroke-width="3" />
                                        @for ($tick = 0; $tick < 60; $tick++)<line x1="100" y1="8" x2="100" y2="{{ $tick % 5 === 0 ? 17 : 12 }}" transform="rotate({{ $tick * 6 }} 100 100)" stroke="#345666" stroke-width="{{ $tick % 5 === 0 ? 2 : 1 }}" />@endfor
                                        @for ($hour = 1; $hour <= 12; $hour++)<text x="{{ 100 + 73 * sin($hour * M_PI / 6) }}" y="{{ 105 - 73 * cos($hour * M_PI / 6) }}" text-anchor="middle" font-size="16" fill="#26394a">{{ $hour }}</text>@endfor
                                        <line x1="100" y1="105" x2="100" y2="52" transform="rotate({{ ($item['clock'][0] % 12) * 30 + $item['clock'][1] / 2 }} 100 100)" stroke="#26394a" stroke-width="6" stroke-linecap="round" />
                                        <line x1="100" y1="108" x2="100" y2="28" transform="rotate({{ $item['clock'][1] * 6 }} 100 100)" stroke="#26394a" stroke-width="3" stroke-linecap="round" />
                                        <circle cx="100" cy="100" r="5" fill="#26394a" />
                                    </svg>
                                @endif
                                @if (isset($item['prism']))
                                    <svg class="worksheet-prism" viewBox="0 0 300 210" role="img" aria-label="Prism for items {{ $label }}: front horizontal edge {{ $item['prism'][0] }}, depth {{ $item['prism'][1] }}, vertical edge {{ $item['prism'][2] }}">
                                        <path d="M80 60 L210 60 L210 155 L80 155 Z M80 60 L112 30 L242 30 L210 60 M210 155 L242 125 L242 30" fill="#edf5f8" stroke="#26394a" stroke-width="2" />
                                        <path d="M80 155 L112 125 L242 125 M112 125 L112 30" fill="none" stroke="#26394a" stroke-dasharray="5 4" />
                                        <text x="145" y="184" text-anchor="middle" font-size="18">{{ $item['prism'][0] }}</text><text x="245" y="152" font-size="16">{{ $item['prism'][1] }}</text><text x="72" y="111" text-anchor="end" font-size="16">{{ $item['prism'][2] }}</text>
                                    </svg>
                                @endif
                                @if (isset($item['figure']))<img class="worksheet-thermometer-source" src="{{ route('worksheets.image', [$worksheet['number'], $page['part'], 'figure' => $item['figure']]) }}" alt="Original thermometer for item {{ $label }}" loading="lazy">@endif
                                @if (isset($item['digital']))<div class="worksheet-digital"><span class="material-symbols-outlined" aria-hidden="true">thermometer</span><span>{{ $item['digital'] }} &deg;C</span></div>@endif
                                @if (isset($item['grid']))
                                    <div class="worksheet-fraction-model" data-book-model="{{ $gridKey }}" style="--model-columns: {{ $item['grid'][1] }}" role="group" aria-label="Fraction model for item {{ $label }}">
                                        @for ($cell = 0; $cell < $item['grid'][0] * $item['grid'][1]; $cell++)<button type="button" data-model-cell="{{ $cell }}" aria-pressed="false" aria-label="Item {{ $label }}, cell {{ $cell + 1 }}" @disabled($readonly)></button>@endfor
                                    </div>
                                @endif
                                @if (!empty($item['thermometer']))
                                    <div class="worksheet-temperature" data-book-temperature="{{ $label }}" data-temperature-item="{{ $gridKey }}">
                                        <svg viewBox="0 0 120 300" role="img" aria-label="Thermometer for item {{ $label }}">
                                            <rect x="67" y="20" width="12" height="240" rx="6" fill="white" stroke="#345666" stroke-width="2" />
                                            <rect data-temperature-fill x="70" y="260" width="6" height="0" fill="#c63847" />
                                            <circle cx="73" cy="271" r="12" fill="#c63847" />
                                            @for ($t = -30; $t <= 50; $t += 5)<line x1="{{ $t % 10 === 0 ? 50 : 58 }}" x2="66" y1="{{ 260 - ($t + 30) * 3 }}" y2="{{ 260 - ($t + 30) * 3 }}" stroke="#345666" />@if ($t % 10 === 0)<text x="44" y="{{ 265 - ($t + 30) * 3 }}" text-anchor="end" font-size="15">{{ $t }}</text>@endif @endfor
                                            <text x="85" y="16" font-size="15">&deg;C</text>
                                        </svg>
                                        <label>Temperature mark <output data-temperature-output>Not marked</output><input type="range" min="-30" max="50" step="1" value="-30" data-temperature-input aria-label="Temperature mark for item {{ $label }}" @disabled($readonly)></label>
                                    </div>
                                @endif
                                <x-worksheet-response :definition="$page['responses'][$gridKey]" :item-key="$gridKey" :page-index="$pageIndex" :readonly="$readonly" />
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endforeach
</article>

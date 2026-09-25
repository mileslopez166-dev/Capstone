@props(['progress' => [], 'audience' => 'student'])
@php
    $hasData = (bool) ($progress['has_data'] ?? false);
    $tone = $progress['tone'] ?? 'empty';
    $trend = $progress['trend'] ?? 'none';
    $icon = match ($trend) {
        'improved' => 'trending_up',
        'declined' => 'support',
        'steady' => 'trending_flat',
        default => 'timeline',
    };
    $title = $audience === 'teacher' ? 'Reading Progress Over Time' : 'My Reading Progress';
@endphp

<section {{ $attributes->class(['reading-progress-card']) }} data-trend="{{ $trend }}" data-tone="{{ $tone }}">
    <div class="reading-progress-head">
        <div>
            <p>READING TRACKER</p>
            <h2>{{ $title }}</h2>
        </div>
        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
    </div>

    @if (! $hasData)
        <div class="reading-progress-empty">
            <strong>{{ $progress['headline'] ?? 'Complete a literacy assessment first' }}</strong>
            <span>{{ $progress['summary'] ?? 'Progress will appear after a scored literacy assessment.' }}</span>
        </div>
    @else
        <div class="reading-progress-status">
            <span>{{ $progress['status_label'] }}</span>
            <strong>{{ $progress['change_label'] }}</strong>
        </div>

        <h3>{{ $progress['headline'] }}</h3>
        <p>{{ $progress['summary'] }}</p>

        <dl class="reading-progress-metrics">
            <div>
                <dt>Latest</dt>
                <dd>{{ $progress['latest_score'] }}%</dd>
                <small>{{ $progress['latest_level'] ?? $progress['latest_title'] }}</small>
            </div>
            <div>
                <dt>Previous</dt>
                <dd>{{ $progress['previous_score'] === null ? '--' : $progress['previous_score'].'%' }}</dd>
                <small>{{ $progress['previous_level'] ?? 'Need another result' }}</small>
            </div>
            <div>
                <dt>Best</dt>
                <dd>{{ $progress['best_score'] }}%</dd>
                <small>{{ $progress['best_title'] }}</small>
            </div>
        </dl>

        @if ($progress['level_transition'])
            <div class="reading-progress-level">
                <span class="material-symbols-outlined" aria-hidden="true">route</span>
                <strong>{{ $progress['level_transition'] }}</strong>
            </div>
        @endif

        @if (! empty($progress['series']))
            <div class="reading-progress-series" aria-label="Recent literacy score history">
                @foreach ($progress['series'] as $point)
                    <div>
                        <span style="height: {{ max(10, min(100, $point['score'])) }}%"></span>
                        <small>{{ $point['score'] }}%</small>
                        <em>{{ $point['label'] }}</em>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</section>

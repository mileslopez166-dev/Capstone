@props(['submission'])
@php
    $prediction = $submission?->mlPrediction;
    $predictedLevel = $prediction?->prediction;
    $validPrediction = in_array($predictedLevel, array_keys(\App\Support\NumeracyProficiency::BANDS), true);
    $officialLevel = \App\Support\NumeracyProficiency::forSubmission($submission);
    $confidence = $prediction && is_numeric($prediction->confidence_score)
        ? rtrim(rtrim(number_format($prediction->confidence_score * 100, 2, '.', ''), '0'), '.').'%' : null;
@endphp

<section class="worksheet-ml-proficiency" data-worksheet-ml-proficiency aria-labelledby="worksheet-ml-proficiency-title">
    <header class="worksheet-ml-proficiency-heading">
        <span class="material-symbols-outlined" aria-hidden="true">psychology</span>
        <div>
            <p>ML prediction</p>
            <h2 id="worksheet-ml-proficiency-title">Numeracy Proficiency Level</h2>
        </div>
    </header>

    @if ($validPrediction)
        <div class="worksheet-ml-proficiency-body">
            <div class="worksheet-ml-level">
                <span>Predicted level</span>
                <strong>{{ $predictedLevel }}</strong>
                @if ($confidence)<small>{{ $confidence }} confidence</small>@endif
            </div>
            <div class="worksheet-ml-guidance">
                <p><strong>Recommended next step</strong></p>
                <p>{{ $prediction->recommendation ?: 'Continue the assigned numeracy practice and monitor the next result.' }}</p>
                <small>The official assessment score remains unchanged. The prediction supports learning guidance.</small>
            </div>
        </div>
    @else
        <div class="worksheet-ml-proficiency-body">
            <div class="worksheet-ml-level is-official">
                <span>Official score-band level</span>
                <strong>{{ $officialLevel ?? 'Not available' }}</strong>
                <small>Calculated from the saved assessment percentage</small>
            </div>
            <div class="worksheet-ml-guidance">
                <p><strong>ML prediction unavailable</strong></p>
                <p>No validated ML prediction was saved for this attempt. The displayed level is the official numeracy score-band result, not an ML prediction.</p>
            </div>
        </div>
    @endif
</section>

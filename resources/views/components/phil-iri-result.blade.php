@props(['result' => null, 'live' => false])
@if ($result || $live)
    @php
        $oral = ($result['assessment_type'] ?? '') === 'oral_reading';
        $comprehension = isset($result['comprehension_percent']) ? $result['correct_count'].' / '.$result['question_count'].' ('.$result['comprehension_percent'].'%)' : 'Not recorded';
    @endphp
    <div class="phil-result" data-phil-iri-result data-level="{{ $result['level'] ?? 'pending' }}" @if ($live) data-phil-iri-live hidden @endif>
        <div class="phil-result-heading">
            <div><p class="phil-eyebrow">Phil-IRI-based result</p><h3 data-phil-field="measure">{{ $result['measure'] ?? '' }}</h3></div>
            <strong class="phil-level" data-phil-field="label">{{ $result['label'] ?? '' }}</strong>
        </div>
        <dl class="phil-measures">
            <div><dt>Comprehension</dt><dd data-phil-field="comprehension">{{ $comprehension }}</dd><small data-phil-field="comprehension_level">{{ $result['comprehension_level'] ?? '' }}</small></div>
            <div data-phil-oral @if (!$oral) hidden @endif><dt>Word reading</dt><dd data-phil-field="word_reading">{{ isset($result['word_reading_percent']) ? $result['word_reading_percent'].'%' : 'Awaiting teacher scoring' }}</dd><small data-phil-field="word_reading_level">{{ $result['word_reading_level'] ?? '' }}</small></div>
            <div data-phil-oral @if (!$oral) hidden @endif><dt>Confirmed miscues</dt><dd data-phil-field="miscues">{{ $result['miscues'] ?? 'Not recorded' }}</dd></div>
            <div data-phil-marked @if (!$oral || !isset($result['marked_miscues'])) hidden @endif><dt>Red-marked words</dt><dd data-phil-field="marked_miscues">{{ $result['marked_miscues'] ?? '' }}</dd></div>
            <div data-phil-rate @if (!isset($result['words_per_minute'])) hidden @endif><dt>Reading rate</dt><dd data-phil-field="rate">{{ $result['words_per_minute'] ?? '' }} WPM</dd></div>
        </dl>
        <div class="phil-interpretation">
            <h4 data-phil-field="interpretation_heading">{{ ($result['assessment_type'] ?? '') === 'group_screening' ? 'Screening interpretation' : 'Comprehension interpretation' }}</h4>
            <p data-phil-field="comprehension_interpretation">{{ $result['comprehension_interpretation'] ?? '' }}</p>
        </div>
        <p class="phil-note">Passage-level result. EXP and report-card grades are separate.</p>
        <p class="phil-note" data-phil-provisional @if (!($result['word_reading_provisional'] ?? false)) hidden @endif>Provisional word-reading score from red marks. Awaiting teacher confirmation.</p>
        <p class="phil-note" data-phil-incomplete @if (($result['status'] ?? '') !== 'incomplete' || !$oral) hidden @endif>Both word reading and comprehension are required for an overall oral reading profile.</p>
        <p class="phil-note" data-phil-gst @if (($result['assessment_type'] ?? '') !== 'group_screening') hidden @endif>Grade 6 GST: 14 out of 20 meets the screening cutoff. A screening result is not an individual reading level.</p>
    </div>
@endif

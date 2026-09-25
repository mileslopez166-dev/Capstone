@props(['result' => null, 'live' => false, 'submission' => null])
@if ($result || $live)
    @php
        $oral = ($result['assessment_type'] ?? '') === 'oral_reading';
        $comprehension = isset($result['comprehension_percent']) ? $result['correct_count'].' / '.$result['question_count'].' ('.$result['comprehension_percent'].'%)' : 'Not recorded';
        $formulaRows = $result ? \App\Support\PhilIri::formulaRows($result, $submission) : [];
        $recommendation = $result['practice_recommendation'] ?? null;
        $viewer = auth()->user();
        $mission = null;
        if ($recommendation && $submission && $viewer?->isStudent()) {
            $mission = \App\Models\PracticeMission::query()
                ->where('submission_id', $submission->id)
                ->where('student_id', $viewer->id)
                ->latest()
                ->first();
        }
        $practiceUrl = null;
        $practiceLabel = $recommendation['action_label'] ?? 'Open practice missions';
        $livePracticeFallback = $viewer?->isTeacher() ? route('teacher.practice.index') : route('student.practice.index');
        if ($recommendation && $viewer?->isTeacher() && $submission?->assessment?->created_by === $viewer->id) {
            $practiceUrl = route('teacher.practice.create', $submission);
            $practiceLabel = 'Assign practice mission';
        } elseif ($recommendation && $viewer?->isStudent()) {
            $practiceUrl = $mission ? route('student.practice.show', $mission) : route('student.practice.index');
            $practiceLabel = $mission ? 'Open practice mission' : $practiceLabel;
        }
    @endphp
    <div class="phil-result" data-phil-iri-result data-level="{{ $result['level'] ?? 'pending' }}" @if ($live) data-phil-iri-live hidden @endif>
        <div class="phil-result-heading">
            <div><p class="phil-eyebrow" data-phil-field="assessment_type_label">{{ $result['assessment_type_label'] ?? 'Phil-IRI-based result' }}</p><h3 data-phil-field="measure">{{ $result['measure'] ?? '' }}</h3></div>
            <strong class="phil-level" data-phil-field="label">{{ $result['label'] ?? '' }}</strong>
        </div>
        <dl class="phil-measures">
            <div><dt>Comprehension</dt><dd data-phil-field="comprehension">{{ $comprehension }}</dd><small data-phil-field="comprehension_level">{{ $result['comprehension_level'] ?? '' }}</small></div>
            <div data-phil-oral @if (!$oral) hidden @endif><dt>Word reading</dt><dd data-phil-field="word_reading">{{ isset($result['word_reading_percent']) ? $result['word_reading_percent'].'%' : 'Reading marks not recorded' }}</dd><small data-phil-field="word_reading_level">{{ $result['word_reading_level'] ?? '' }}</small></div>
            <div data-phil-oral @if (!$oral) hidden @endif><dt>Confirmed miscues</dt><dd data-phil-field="miscues">{{ $result['miscues'] ?? 'Not recorded' }}</dd></div>
            <div data-phil-marked @if (!$oral || !isset($result['marked_miscues'])) hidden @endif><dt>Red-marked words</dt><dd data-phil-field="marked_miscues">{{ $result['marked_miscues'] ?? '' }}</dd></div>
            <div data-phil-rate @if (!isset($result['words_per_minute'])) hidden @endif><dt>Reading rate</dt><dd data-phil-field="rate">{{ $result['words_per_minute'] ?? '' }} WPM</dd></div>
        </dl>
        <div class="phil-interpretation">
            <h4 data-phil-field="interpretation_heading">{{ ($result['assessment_type'] ?? '') === 'group_screening' ? 'Screening interpretation' : 'Comprehension interpretation' }}</h4>
            <p data-phil-field="comprehension_interpretation">{{ $result['comprehension_interpretation'] ?? '' }}</p>
        </div>
        <div class="phil-recommendation" data-phil-recommendation @if (! $recommendation) hidden @endif>
            <span class="material-symbols-outlined" aria-hidden="true">flag</span>
            <div>
                <p data-phil-field="recommendation_label">{{ $recommendation['label'] ?? 'Recommended next step' }}</p>
                <h4 data-phil-field="recommendation_title">{{ $recommendation['title'] ?? '' }}</h4>
                <p data-phil-field="recommendation_summary">{{ $recommendation['summary'] ?? '' }}</p>
                <ul data-phil-recommendation-steps>
                    @foreach (($recommendation['steps'] ?? []) as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ul>
                @if ($practiceUrl || $live)
                    <a class="phil-practice-link" data-phil-recommendation-link href="{{ $practiceUrl ?? $livePracticeFallback }}">
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                        <span data-phil-field="recommendation_action">{{ $practiceLabel }}</span>
                    </a>
                @endif
            </div>
        </div>
        @if ($formulaRows || $live)
            <div class="phil-formula" data-phil-formula aria-label="Score and points formula" @if ($live && ! $formulaRows) hidden @endif>
                <h4>Score and points formula</h4>
                <dl data-phil-formula-list>
                    @foreach ($formulaRows as $row)
                        <div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endif
        <p class="phil-note">Passage-level result. EXP and report-card grades are separate.</p>
        <p class="phil-note" data-phil-provisional @if (($result['word_reading_source'] ?? null) !== 'red_marks') hidden @endif>Word-reading score calculated from the red marks recorded during the assessment.</p>
        <p class="phil-note" data-phil-incomplete @if (($result['status'] ?? '') !== 'incomplete' || !$oral) hidden @endif>Both word reading and comprehension are required for an overall oral reading profile.</p>
        <p class="phil-note" data-phil-gst @if (($result['assessment_type'] ?? '') !== 'group_screening') hidden @endif>Grade 6 GST: 14 out of 20 meets the screening cutoff. A screening result is not an individual reading level.</p>
    </div>
@endif

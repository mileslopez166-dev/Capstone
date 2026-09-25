@props(['submission', 'returnTo' => 'report', 'assessments' => null])
@php
    $plan = $submission->interventionPlan;
    $types = \App\Models\InterventionPlan::TYPES;
    $statuses = \App\Models\InterventionPlan::STATUSES;
    $level = \App\Support\PhilIri::forSubmission($submission)['level'] ?? null;
    $suggestedType = match ($level) {
        'Independent' => 'enrichment',
        'Instructional' => 'guided_practice',
        'Frustration' => 'remediation',
        default => '',
    };
    $prefix = 'intervention-'.$submission->id;
    $planErrors = $errors->getBag($prefix);
    $hasErrors = $planErrors->any();
    $saved = (int) session('intervention_saved') === $submission->id;
    $type = $hasErrors ? old('intervention.type') : ($plan?->type ?? $suggestedType);
    $status = $hasErrors ? old('intervention.status') : ($plan?->status ?? 'planned');
    $date = $hasErrors ? old('intervention.follow_up_date') : $plan?->follow_up_date?->format('Y-m-d');
    $notes = $hasErrors ? old('intervention.notes') : $plan?->notes;
    $followUpStatus = $plan ? \App\Support\InterventionFollowUp::status($plan) : null;
    $overdue = ($followUpStatus['label'] ?? null) === 'Overdue';
    $followUpId = $hasErrors ? old('intervention.follow_up_assessment_id') : $plan?->follow_up_assessment_id;
    $comparisonBasis = $hasErrors ? old('intervention.comparison_basis') : $plan?->comparison_basis;
    $options = ($assessments ?? collect())->filter(fn ($assessment) => \App\Support\InterventionFollowUp::comparable($submission, $assessment))->keyBy('id');
    if ($plan?->followUpAssessment && !$options->has($plan->follow_up_assessment_id)) $options->put($plan->follow_up_assessment_id, $plan->followUpAssessment);
    $tone = match ($plan?->status) { 'done' => 'green', 'in_progress' => 'blue', 'planned' => 'amber', default => 'gray' };
@endphp
<details id="{{ $prefix }}" class="intervention-plan" x-data x-init="if (window.location.hash === '#' + $el.id) $el.open = true" @if ($hasErrors || $saved) open @endif>
    <summary>
        <span class="intervention-heading">
            <strong>Teacher Action Plan</strong>
            <span>Attempt {{ $submission->attempt_number }}@if ($plan) &middot; {{ $types[$plan->type] }}@elseif ($suggestedType) &middot; Suggested: {{ $types[$suggestedType] }}@endif</span>
        </span>
        <span class="ui-status ui-status-{{ $tone }}">{{ $plan ? $statuses[$plan->status] : 'No plan yet' }}</span>
        @if ($followUpStatus)<span class="ui-status ui-status-{{ $followUpStatus['tone'] }}">{{ $followUpStatus['label'] }}</span>@endif
        <span class="material-symbols-outlined intervention-chevron" aria-hidden="true">expand_more</span>
    </summary>
    @if ($plan?->follow_up_date)
        <p class="intervention-follow-up {{ $overdue ? 'is-overdue' : '' }}">
            {{ $overdue ? 'Follow-up overdue:' : 'Follow-up:' }} {{ $plan->follow_up_date->format('M d, Y') }}
        </p>
    @endif
    @if ($saved)
        <p class="intervention-saved" role="status">Action plan saved.</p>
    @endif
    @if ($hasErrors)
        <div id="{{ $prefix }}-errors" class="intervention-errors" role="alert">
            @foreach ($planErrors->all() as $message)<p>{{ $message }}</p>@endforeach
        </div>
    @endif
    @if ($plan?->follow_up_linked_at)
        <x-intervention-comparison :plan="$plan" :submission="$submission" :available="($assessments ?? collect())->contains('id', $plan->follow_up_assessment_id)" />
    @endif
    <form method="POST" action="{{ route('teacher.interventions.update', $submission) }}" class="intervention-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
        <div class="intervention-fields">
            <div>
                <label for="{{ $prefix }}-type">Intervention</label>
                <select id="{{ $prefix }}-type" name="intervention[type]" required @if ($planErrors->has('intervention.type')) aria-invalid="true" aria-describedby="{{ $prefix }}-errors" @endif>
                    <option value="">Choose intervention</option>
                    @foreach ($types as $value => $label)<option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="{{ $prefix }}-status">Status</label>
                <select id="{{ $prefix }}-status" name="intervention[status]" required @if ($planErrors->has('intervention.status')) aria-invalid="true" aria-describedby="{{ $prefix }}-errors" @endif>
                    @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="{{ $prefix }}-date">Follow-up date <span>(optional)</span></label>
                <input id="{{ $prefix }}-date" name="intervention[follow_up_date]" type="date" value="{{ $date }}" @if ($planErrors->has('intervention.follow_up_date')) aria-invalid="true" aria-describedby="{{ $prefix }}-errors" @endif>
            </div>
            <div class="intervention-notes">
                <label for="{{ $prefix }}-assessment">Follow-up assessment <span>(optional)</span></label>
                <select id="{{ $prefix }}-assessment" name="intervention[follow_up_assessment_id]" @if ($planErrors->has('intervention.follow_up_assessment_id')) aria-invalid="true" aria-describedby="{{ $prefix }}-errors" @endif>
                    <option value="">No linked assessment</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->id }}" @selected((string) $followUpId === (string) $option->id)>{{ $option->title }}{{ $option->id === $submission->assessment_id ? ' (same assessment)' : '' }}{{ $option->status !== 'published' ? ' (unavailable)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="intervention-notes">
                <label for="{{ $prefix }}-basis">Comparable skills and difficulty <span>(for a different assessment)</span></label>
                <textarea id="{{ $prefix }}-basis" name="intervention[comparison_basis]" rows="2" maxlength="1000" @if ($planErrors->has('intervention.comparison_basis')) aria-invalid="true" aria-describedby="{{ $prefix }}-errors" @endif>{{ $comparisonBasis }}</textarea>
            </div>
            <div class="intervention-notes">
                <label for="{{ $prefix }}-notes">Action plan notes</label>
                <textarea id="{{ $prefix }}-notes" name="intervention[notes]" rows="4" maxlength="5000" required @if ($planErrors->has('intervention.notes')) aria-invalid="true" aria-describedby="{{ $prefix }}-errors" @endif>{{ $notes }}</textarea>
            </div>
        </div>
        <div class="intervention-actions">
            @if ($plan)<span>Updated {{ $plan->updated_at->format('M d, Y') }}</span>@endif
            <button class="ui-button" type="submit"><span class="material-symbols-outlined" aria-hidden="true">save</span>Save Action Plan</button>
        </div>
    </form>
</details>
